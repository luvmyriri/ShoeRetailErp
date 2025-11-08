<?php
// Quick demo seeder: inserts 5 shoe products and inventory if they don't already exist
// Usage: visit this script in browser once, then delete/disable

require __DIR__ . '/../../config/database.php';

function db() {
  if (function_exists('getDB')) return getDB();
  global $pdo;
  if (isset($pdo)) return $pdo;
  $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
  return new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}

try {
  $dbh = db();
  // resolve UnitID for PAIR
  $unitId = null;
  $st = $dbh->query("SELECT UnitID FROM units WHERE UnitCode='PAIR' LIMIT 1");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if ($row) { $unitId = (int)$row['UnitID']; }
  if (!$unitId) {
    $dbh->exec("INSERT INTO units (UnitCode, UnitName) VALUES ('PAIR','Pair')");
    $unitId = (int)$dbh->lastInsertId();
  }

  $samples = [
    ['sku'=>'SH-1001','brand'=>'Nike','model'=>'Air Max 90','size'=>10.0,'color'=>'Black/White','cost'=>3500,'price'=>4995,'qty'=>5],
    ['sku'=>'SH-1002','brand'=>'Adidas','model'=>'Ultraboost 21','size'=>9.0,'color'=>'Core Black','cost'=>4500,'price'=>6495,'qty'=>12],
    ['sku'=>'SH-1003','brand'=>'Puma','model'=>'RS-X','size'=>11.0,'color'=>'Blue','cost'=>2800,'price'=>3995,'qty'=>30],
    ['sku'=>'SH-1004','brand'=>'New Balance','model'=>'574','size'=>8.0,'color'=>'Grey','cost'=>3000,'price'=>4295,'qty'=>9],
    ['sku'=>'SH-1005','brand'=>'Converse','model'=>'Chuck Taylor','size'=>10.5,'color'=>'White','cost'=>1800,'price'=>2695,'qty'=>50],
  ];

  // Ensure Store #1 exists
  $storeId = 1; // adjust as needed
  $st = $dbh->query("SELECT StoreID FROM stores WHERE StoreID=1 LIMIT 1");
  if (!$st->fetchColumn()) {
    $dbh->exec("INSERT INTO stores (StoreID, StoreName, Location, Status) VALUES (1,'Main Branch','HQ','Active') ON DUPLICATE KEY UPDATE StoreName=VALUES(StoreName)");
  }

  $insProd = $dbh->prepare("INSERT INTO products (SKU, Brand, Model, Size, Color, CostPrice, SellingPrice, Status, BaseUnitID, DefaultSalesUnitID) VALUES (?,?,?,?,?,?,?,?,?,?)");
  $selProd = $dbh->prepare("SELECT ProductID FROM products WHERE SKU=? LIMIT 1");
  $upInv = $dbh->prepare("INSERT INTO inventory (ProductID, StoreID, Quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE Quantity=VALUES(Quantity)");

  $created = 0; $updated = 0;
  foreach ($samples as $s) {
    $selProd->execute([$s['sku']]);
    $pid = $selProd->fetchColumn();
    if (!$pid) {
      $insProd->execute([$s['sku'],$s['brand'],$s['model'],$s['size'],$s['color'],$s['cost'],$s['price'],'Active',$unitId,$unitId]);
      $pid = (int)$dbh->lastInsertId();
      $created++;
    }
    $upInv->execute([$pid,$storeId,$s['qty']]);
    $updated++;
  }

  header('Content-Type: text/plain');
  echo "Seed complete. Products created: {$created}; inventory rows upserted: {$updated}\n";
} catch (Exception $e) {
  http_response_code(500);
  echo "Error: ".$e->getMessage();
}
