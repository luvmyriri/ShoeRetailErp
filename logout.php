<?php

require_once 'includes/core_functions.php';

// Logout user and redirect to login page
logoutUser();
header('Location: login.php');
exit;
?>