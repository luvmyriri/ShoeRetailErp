<?php
include './Connection.php';

// ✅ Get batch from URL
$batch = isset($_GET['batch']) ? htmlspecialchars($_GET['batch']) : '';
$order_data = null;

if ($batch) {
    $sql = "SELECT * FROM v_PurchaseOrderDetails WHERE `Batch#` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $batch);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order_data = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$order_data) {
    die("Invalid Batch. Go back to <a href='./index.php'>index</a>");
}

// Field values
$brand = $order_data['Brand'];
$model = $order_data['Model'];
$size = $order_data['Size'] ?? '';
$quantity = $order_data['Qty'];
$price_per_item = $order_data['Price Per Item'];
$total_amount = $quantity * $price_per_item;
$supplier_name = $order_data['SupplierName'];
$supplier_contact = $order_data['Supplier Contact Number'];
$supplier_email = $order_data['Supplier Email'];
$order_date = $order_data['Order Date'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quality Checking</title>
  <link rel="stylesheet" href="./css/qualitychecking.css">
  <script src="../js/qualitychecking.js"></script>
</head>
<body>

<div class="form-container">
  <h2>Receiving Order for Batch: <?= $batch ?></h2>
  <hr />

  <form method="POST" action="process_order.php" enctype="multipart/form-data">

    <input type="hidden" name="batch" value="<?= $batch ?>">

    <!-- Product Details -->
    <div class="grid">
      <div><label>Brand</label><input type="text" name="brand" value="<?= $brand ?>" readonly></div>
      <div><label>Model</label><input type="text" name="model" value="<?= $model ?>" readonly></div>
      <div><label>Size</label><input type="text" name="size" value="<?= $size ?>" readonly></div>
    </div>

    <div class="grid">
      <div><label>Quantity</label><input type="number" name="quantity" value="<?= $quantity ?>" readonly></div>
      <div><label>Unit Cost</label><input type="number" name="price_per_item" value="<?= $price_per_item ?>" readonly></div>
      <div><label>Total</label><input type="text" value="<?= number_format($total_amount,2) ?>" readonly></div>
    </div>

    <div class="grid">
      <div><label>Supplier</label><input type="text" value="<?= $supplier_name ?>" readonly></div>
      <div><label>Contact</label><input type="text" value="<?= $supplier_contact ?>" readonly></div>
      <div><label>Email</label><input type="email" value="<?= $supplier_email ?>" readonly></div>
    </div>

    <!-- Dates -->
    <div class="grid">
      <div><label>Ordered</label><input type="date" name="order_date" value="<?= $order_date ?>" readonly></div>
      <div><label>Arrival</label><input type="date" name="arrival_date" required></div>
    </div>

    <!-- QC Results -->
    <div class="grid">
      <div>
        <label>Quantity Received</label>
        <input type="number" name="quantity_received" min="0" max="<?= $quantity ?>" required>
      </div>
      <div>
        <label>Quantity Passed</label>
        <input type="number" name="quantity_passed" min="0" required>
      </div>
    </div>

    <!-- Description + Image -->
    <div class="grid">
      <div>
        <label>Description</label>
        <textarea name="description" maxlength="10000"></textarea>
      </div>
      <div>
        <label>Image Proof</label>
        <input type="file" name="image_proof" accept="image/*">
      </div>
    </div>

    <div class="footer">
      <a href="./index.php" class="btn btn-cancel">Cancel</a>
      <button type="submit" class="btn btn-submit">Submit</button>
    </div>

  </form>
</div>

</body>
</html>

<?php $conn->close(); ?>
