<?php
// Bootstrap index to route to the public front controller
// This avoids 404s when the site root points to /ShoeRetailErp instead of /ShoeRetailErp/public
$target = '/ShoeRetailErp/public/index.php';
header('Location: ' . $target, true, 302);
exit;
