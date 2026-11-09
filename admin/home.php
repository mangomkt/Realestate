<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo = db();
    if (isset($_POST['reset'])) {
        $pdo->exec('DELETE FROM home_sections WHERE page_id = 0');
        home_seed_defaults($pdo);
        flash('Home page reset to the default layout.');
        redirect('admin/home.php');
    }
    home_save_post(0);
    flash('Home page saved.');
    redirect('admin/home.php');
}

admin_head('Home page');
?>
<p class="muted">Build your home page from sections. Drag, or use the arrows, to reorder. Use <strong>Show</strong> to hide a section without losing it.</p>
<form method="post" id="home-form" class="panel">
  <?= csrf_field() ?>
  <?php home_builder_ui(0); ?>

  <p><button class="btn" type="submit">Save home page</button>
  <a class="btn btn-ghost" href="<?= url() ?>" target="_blank">View site</a></p>
</form>
<form method="post" onsubmit="return confirm('Replace your home page with the default layout?')">
  <?= csrf_field() ?><button class="btn btn-ghost btn-sm" name="reset" value="1">Reset to default layout</button>
</form>
<?php admin_foot(); ?>
