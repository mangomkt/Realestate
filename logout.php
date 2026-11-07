<?php
require_once __DIR__ . '/includes/bootstrap.php';
unset($_SESSION['uid'], $_SESSION['seen']);
session_regenerate_id(true);
flash('You have been logged out.', 'info');
redirect('');
