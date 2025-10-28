<?php

require_once 'includes/core_functions.php';

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Logout user and redirect to login page
logoutUser();
header('Location: login.php');
exit;
?>
