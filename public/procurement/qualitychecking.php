<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /ShoeRetailErp/login.php'); exit; }
require_once '../../config/database.php';
require_once '../../includes/core_functions.php';

// Get batch from URL (passed from index.php)
$batch = isset($_GET['batch']) ? htmlspecialchars($_GET['batch']) : '';
$order_data = null;

if ($batch) {
    $order_data = dbFetchOne("SELECT * FROM v_PurchaseOrderDetails WHERE `Batch#` = ?", [$batch]);
}

if (!$order_data) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showModal('Error', 'Invalid or missing batch number. Redirecting to procurement page...', 'error', function() {
                    window.location.href = './index.php?tab=receivingTab';
                });
            });
          </script>";
    exit;
}

// Helper function for POST data
$post = fn($key, $default = '') => isset($_POST[$key]) ? htmlspecialchars($_POST[$key]) : $default;

// Pre-fill from fetched data (readonly fields)
$brand = $order_data['Brand'] ?? '';
$model = $order_data['Model'] ?? '';
$size = $order_data['Size'] ?? ''; // Assuming Size is part of Model or add to view if separate
$quantity = $order_data['Qty'] ?? 0;
$price_per_item = $order_data['Price Per Item'] ?? 0;
$total_amount = $quantity * $price_per_item;
$supplier_name = $order_data['SupplierName'] ?? '';
$supplier_contact = $order_data['Supplier Contact Number'] ?? '';
$supplier_email = $order_data['Supplier Email'] ?? '';
$order_date = $order_data['Order Date'] ?? '';
$arrival_date = isset($_POST['arrival_date']) ? htmlspecialchars($_POST['arrival_date']) : '';


// Editable fields (from POST or defaults)
$qty_received = isset($_POST['quantity_received']) ? floatval($_POST['quantity_received']) : 0;
$qty_passed = isset($_POST['quantity_passed']) ? floatval($_POST['quantity_passed']) : 0;
$qty_failed = $qty_received - $qty_passed;
$description = $post('description');
$image_proof = $post('image_proof');

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quality Checking Form</title>
  <link rel="stylesheet" href="./css/qualitychecking.css">
  <script src="./js/qualitychecking.js"></script>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/modal.php'; ?>
<link rel="stylesheet" href="../css/style.css">

<div class="form-container">
  <h2>Receiving Order for Batch: <?= htmlspecialchars($batch) ?></h2>
  <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" /> 

  <form method="POST" action="process_order.php" onsubmit="syncTotal()" enctype="multipart/form-data">
    <!-- Hidden field for batch -->
    <input type="hidden" name="batch" value="<?= htmlspecialchars($batch) ?>">
    
    <!-- Row 1: Brand / Model / Size (Readonly) -->
    <div class="grid">
      <div>
        <label for="brand">Brand</label>
        <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($brand) ?>" readonly>
      </div>
      <div>
        <label for="model">Model</label>
        <input type="text" id="model" name="model" value="<?= htmlspecialchars($model) ?>" readonly>
      </div>
      <div>
        <label for="size">Size</label>
        <input type="text" id="size" name="size" value="<?= htmlspecialchars($size) ?>" readonly placeholder="e.g. 8 / 42 / M">
      </div>
    </div>

    <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

    <!-- Row 2: Quantity / Price per Item / Total Amount (Readonly) -->
    <div class="grid">
      <div>
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($quantity) ?>" readonly>
      </div>
      <div>
        <label for="price_per_item">Price per Item</label>
        <input type="number" id="price_per_item" name="price_per_item" value="<?= htmlspecialchars($price_per_item) ?>" readonly>
      </div>
      <div>
        <label for="total_amount">Total Amount</label>
        <input type="text" id="total_amount" readonly value="<?= number_format($total_amount, 2) ?>">
        <input type="hidden" id="total_hidden" name="total_amount" value="<?= $total_amount ?>">
      </div>
    </div>

    <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

    <!-- Row 3: Supplier Name / Contact Number / Email (Readonly) -->
    <div class="grid">
      <div>
        <label for="supplier_name">Supplier Name</label>
        <input type="text" id="supplier_name" name="supplier_name" value="<?= htmlspecialchars($supplier_name) ?>" readonly>
      </div>
      <div>
        <label for="supplier_contact">Supplier Contact Number</label>
        <input type="text" id="supplier_contact" name="supplier_contact" value="<?= htmlspecialchars($supplier_contact) ?>" readonly>
      </div>
      <div>
        <label for="supplier_email">Supplier Email</label>
        <input type="email" id="supplier_email" name="supplier_email" value="<?= htmlspecialchars($supplier_email) ?>" readonly>
      </div>
    </div>

    <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

    <!-- Row 4: Order Date / Arrival Date (Readonly) -->
    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 20px; width: 100%;">
      <div>
        <label for="order_date">Order Date</label>
        <input type="date" id="order_date" name="order_date" value="<?= htmlspecialchars($order_date) ?>" readonly>
      </div>
      <div>
        <label for="arrival_date">Arrival Date</label>
        <input type="date" id="arrival_date" name="arrival_date" value="<?= htmlspecialchars($arrival_date) ?>" required>

      </div>
    </div>

    <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

    <!-- Row 5: Quantity Received / Passed / Failed (Editable for Received/Passed, Calculated for Failed) -->
    <div class="grid">
      <div>
        <label for="quantity_received">Quantity Received</label>
        <input type="number" id="quantity_received" name="quantity_received" min="0" step="1" 
               value="<?= htmlspecialchars($qty_received) ?>" 
               oninput="updatePassedLimit()" required>
      </div>
      <div>
        <label for="quantity_passed">Quantity Passed</label>
        <input type="number" id="quantity_passed" name="quantity_passed" min="0" step="1" 
               value="<?= htmlspecialchars($qty_passed) ?>" 
               oninput="updatePassedLimit()" required>
      </div>
      <div>
        <label for="quantity_failed">Quantity Failed</label>
        <input type="number" id="quantity_failed" readonly value="<?= htmlspecialchars($qty_failed) ?>">
      </div>
    </div>

    <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

    <!-- Row 6: Image Proof / Description (Editable) -->
    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
      <!-- Description -->
      <div>
        <label for="description">Description</label>
        <textarea id="description" name="description" maxlength="10000" oninput="updateCharCount()" placeholder="Write your description here..."><?= htmlspecialchars($description) ?></textarea>
        <div class="char-counter" id="char_counter">0 / 10000</div>
      </div>

      <!-- Image Proof -->
      <div>
        <label for="image_proof">Image as Proof</label>
        <div class="image-upload-wrapper">
          <input type="file" id="image_proof" name="image_proof" accept="image/*" onchange="previewImage(event)">
          <div class="image-preview" id="image_preview">
            <span>Click or drag image here</span>
          </div>
        </div>
      </div>
    </div>

    <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

    <!-- Footer Buttons -->
    <div class="footer">
      <a href="./index.php" class="btn btn-cancel">Cancel</a>
      <button type="submit" class="btn btn-submit">Submit Order</button>
    </div>

  </form>
</div>

</body>
</html>
