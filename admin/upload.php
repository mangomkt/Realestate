<?php
// Image upload endpoint for the page editor. Returns JSON.
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function fail(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

if (!is_admin()) fail('Please log in again.', 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST only.', 405);
if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) fail('Session expired. Reload the page.', 400);
if (empty($_FILES['file'])) fail('No file received.');

$name = save_upload($_FILES['file'], 'image');
if (!$name) fail('Could not upload. Use a JPG, PNG or WebP under 8 MB.');
echo json_encode(['url' => url('uploads/' . $name)]);
