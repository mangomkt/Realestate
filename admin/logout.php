<?php
require_once __DIR__ . '/../includes/bootstrap.php';
unset($_SESSION['admin']);
session_regenerate_id(true);
redirect('admin/login.php');
