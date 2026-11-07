<?php
require_once __DIR__ . '/_layout.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p = ['title' => '', 'description' => '', 'price' => '', 'status' => 'for_sale', 'type' => 'house', 'bedrooms' => 3, 'bathrooms' => 2,
      'sqft' => '', 'address' => '', 'city' => '', 'province' => 'ON', 'postal' => '', 'mls_number' => '', 'featured' => 0,
      'video_file' => '', 'video_url' => ''];
if ($id) {
    $st = db()->prepare('SELECT * FROM properties WHERE id = ?'); $st->execute([$id]);
    $p = $st->fetch() ?: redirect('admin/properties.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => max(0, (int)preg_replace('/\D/', '', $_POST['price'] ?? '0')),
        'status' => isset(STATUSES[$_POST['status'] ?? '']) ? $_POST['status'] : 'for_sale',
        'type' => isset(TYPES[$_POST['type'] ?? '']) ? $_POST['type'] : 'house',
        'bedrooms' => max(0, (int)($_POST['bedrooms'] ?? 0)),
        'bathrooms' => max(0, (float)($_POST['bathrooms'] ?? 0)),
        'sqft' => max(0, (int)($_POST['sqft'] ?? 0)),
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'province' => trim($_POST['province'] ?? 'ON'),
        'postal' => trim($_POST['postal'] ?? ''),
        'mls_number' => trim($_POST['mls_number'] ?? ''),
        'featured' => isset($_POST['featured']) ? 1 : 0,
        'video_url' => trim($_POST['video_url'] ?? ''),
    ];
    if ($f['title'] === '') $errors[] = 'Title is required.';
    if ($f['video_url'] !== '' && !embed_url($f['video_url'])) $errors[] = 'Video URL must be a YouTube or Vimeo link (or upload a file instead).';

    if (!$errors) {
        $pdo = db();
        if ($id) {
            $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($f)));
            $pdo->prepare("UPDATE properties SET $set WHERE id = :id")->execute($f + ['id' => $id]);
        } else {
            $cols = implode(', ', array_keys($f));
            $ph = implode(', ', array_map(fn($k) => ":$k", array_keys($f)));
            $pdo->prepare("INSERT INTO properties ($cols) VALUES ($ph)")->execute($f);
            $id = (int)$pdo->lastInsertId();
        }

        // remove selected photos
        foreach ((array)($_POST['del_img'] ?? []) as $imgId) {
            $s = $pdo->prepare('SELECT filename FROM property_images WHERE id = ? AND property_id = ?');
            $s->execute([(int)$imgId, $id]);
            if ($fn = $s->fetchColumn()) {
                delete_upload($fn);
                $pdo->prepare('DELETE FROM property_images WHERE id = ?')->execute([(int)$imgId]);
            }
        }
        // add new photos
        $skipped = 0;
        if (!empty($_FILES['images']['name'][0])) {
            $sort = (int)$pdo->query("SELECT COALESCE(MAX(sort),0) FROM property_images WHERE property_id = $id")->fetchColumn();
            foreach ($_FILES['images']['name'] as $i => $_) {
                $file = ['name' => $_FILES['images']['name'][$i], 'tmp_name' => $_FILES['images']['tmp_name'][$i],
                         'size' => $_FILES['images']['size'][$i], 'error' => $_FILES['images']['error'][$i]];
                if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;
                $name = save_upload($file, 'image');
                if ($name) $pdo->prepare('INSERT INTO property_images (property_id, filename, sort) VALUES (?,?,?)')->execute([$id, $name, ++$sort]);
                else $skipped++;
            }
        }
        // video file
        $cur = $pdo->prepare('SELECT video_file FROM properties WHERE id = ?'); $cur->execute([$id]);
        $curVideo = $cur->fetchColumn() ?: null;
        if (!empty($_POST['del_video']) && $curVideo) {
            delete_upload($curVideo);
            $pdo->prepare('UPDATE properties SET video_file = NULL WHERE id = ?')->execute([$id]);
            $curVideo = null;
        }
        if (!empty($_FILES['video']['name']) && $_FILES['video']['error'] !== UPLOAD_ERR_NO_FILE) {
            $v = save_upload($_FILES['video'], 'video');
            if ($v) {
                delete_upload($curVideo);
                $pdo->prepare('UPDATE properties SET video_file = ? WHERE id = ?')->execute([$v, $id]);
            } else $skipped++;
        }
        flash('Property saved.' . ($skipped ? " $skipped file(s) were skipped (wrong type or too large; images: JPG/PNG/WebP up to 8 MB, video: MP4/WebM up to 200 MB)." : ''), $skipped ? 'info' : 'ok');
        redirect('admin/property_edit.php?id=' . $id);
    }
    $p = $f + ['video_file' => $p['video_file']];
}

$images = $id ? property_images($id) : [];
admin_head($id ? 'Edit property' : 'Add property');
foreach ($errors as $er) echo '<div class="flash flash-err">' . e($er) . '</div>';
?>
<form method="post" enctype="multipart/form-data" class="panel">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="form-grid">
    <label class="full">Title<input name="title" required value="<?= e($p['title']) ?>" placeholder="Bright 3-bed family home near the lake"></label>
    <label>Price ($)<input name="price" inputmode="numeric" value="<?= e($p['price']) ?>"></label>
    <label>Status<select name="status"><?php foreach (STATUSES as $k => $v) echo '<option value="' . e($k) . '"' . ($p['status'] === $k ? ' selected' : '') . '>' . e($v) . '</option>'; ?></select></label>
    <label>Type<select name="type"><?php foreach (TYPES as $k => $v) echo '<option value="' . e($k) . '"' . ($p['type'] === $k ? ' selected' : '') . '>' . e($v) . '</option>'; ?></select></label>
    <label>MLS&reg; #<input name="mls_number" value="<?= e($p['mls_number']) ?>"></label>
    <label>Bedrooms<input type="number" min="0" name="bedrooms" value="<?= e($p['bedrooms']) ?>"></label>
    <label>Bathrooms<input type="number" min="0" step="0.5" name="bathrooms" value="<?= e($p['bathrooms']) ?>"></label>
    <label>Square feet<input type="number" min="0" name="sqft" value="<?= e($p['sqft']) ?>"></label>
    <label class="full">Street address<input name="address" value="<?= e($p['address']) ?>"></label>
    <label>City<input name="city" value="<?= e($p['city']) ?>"></label>
    <label>Province<input name="province" value="<?= e($p['province']) ?>" maxlength="30"></label>
    <label>Postal code<input name="postal" value="<?= e($p['postal']) ?>" maxlength="10"></label>
    <label class="full">Description<textarea name="description" rows="8"><?= e($p['description']) ?></textarea></label>

    <div class="full">
      <label>Add photos (JPG/PNG/WebP, up to 8 MB each; the first one is the cover)<input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple></label>
      <?php if ($images): ?>
        <div class="img-grid">
          <?php foreach ($images as $im): ?>
            <label><img src="<?= e(url('uploads/' . $im['filename'])) ?>" alt=""><span class="check" style="display:flex;gap:6px;align-items:center;margin-top:4px"><input type="checkbox" name="del_img[]" value="<?= (int)$im['id'] ?>" style="width:auto"> Remove</span></label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <label>Upload video (MP4/WebM, up to 200 MB)<input type="file" name="video" accept="video/mp4,video/webm"></label>
    <label>&hellip;or YouTube / Vimeo link<input name="video_url" value="<?= e($p['video_url']) ?>" placeholder="https://www.youtube.com/watch?v=..."></label>
    <?php if ($p['video_file']): ?>
      <label class="check full"><input type="checkbox" name="del_video" value="1"> Remove current uploaded video (<?= e($p['video_file']) ?>)</label>
    <?php endif; ?>
    <label class="check full"><input type="checkbox" name="featured" value="1" <?= $p['featured'] ? 'checked' : '' ?>> Feature on the home page</label>
  </div>
  <div class="actions"><button class="btn" type="submit">Save property</button><a class="btn btn-ghost" href="properties.php">Cancel</a></div>
</form>
<p class="small muted">Large video uploads also depend on your server's <code>upload_max_filesize</code> and <code>post_max_size</code> PHP settings (see README).</p>
<?php admin_foot(); ?>
