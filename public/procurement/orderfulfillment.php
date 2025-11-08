<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /ShoeRetailErp/login.php'); exit; }
require_once '../../config/database.php';
require_once '../../includes/core_functions.php';

// Check if viewing specific order
$viewing_order = null;
$batch_number = isset($_GET['batch']) ? $_GET['batch'] : null;

if ($batch_number) {
    $viewing_order = dbFetchOne("SELECT * FROM v_PurchaseOrderDetails WHERE `Batch#` = ?", [$batch_number]);
}

// If no batch parameter or order not found, redirect back
if (!$viewing_order) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showModal('Error', 'Order not found. Redirecting to procurement page...', 'error', function() {
                    window.location.href = './index.php?tab=fulfillmentTab';
                });
            });
          </script>";
    exit;
}

// Handle Submit button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    try {
        $current_date = date('Y-m-d H:i:s');
        dbUpdate("UPDATE PurchaseOrders SET OrderedDate = ? WHERE BatchNo = ?", [$current_date, $batch_number]);
        logInfo('Purchase order submitted for ordering', ['batch' => $batch_number, 'date' => $current_date]);
        header('Location: ./index.php?submitted=1');
        exit;
    } catch (Exception $e) {
        logError('Failed to submit purchase order', ['error' => $e->getMessage(), 'batch' => $batch_number]);
        $errorMsg = addslashes($e->getMessage());
        echo "<script>document.addEventListener('DOMContentLoaded', function() { showModal('Error', 'Error submitting order: {$errorMsg}', 'error'); });</script>";
    }
}

// Get values from viewing order
$quantity_val = $viewing_order['Qty'];
$price_val = $viewing_order['Price Per Item'];
$total_val = $viewing_order['Total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Purchase Order - <?= htmlspecialchars($viewing_order['Batch#']) ?></title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="./css/orderfulfillment.css">
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
    .form-container { max-width: 900px; margin: auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
    input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
    .footer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }
    .btn { padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; text-align: center; }
    .btn-cancel { background: #f44336; color: white; }
    .btn-cancel:hover { background: #d32f2f; }
    .btn-submit { background: #4caf50; color: white; border: none; }
    .btn-submit:hover { background: #45a049; }
    /* Modal Styles */
    .modal { display: none; position: fixed; z-index: 1000; padding-top: 100px; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: #fff; margin: auto; padding: 20px; border-radius: 10px; width: 400px; text-align: center; }
    .modal button { margin: 10px; padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; }
    .btn-confirm { background: #4caf50; color: white; }
    .btn-cancel-modal { background: #f44336; color: white; }
  </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/modal.php'; ?>

  <div class="form-container">
    <h2>Purchase Order Details - <?= htmlspecialchars($viewing_order['Batch#']) ?></h2>

    <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
      <strong>📋 View Only Mode</strong> - This is a read-only view of the purchase order.
    </div>

    <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

    <!-- Batch Number Info -->
    <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
      <strong>Batch Number:</strong> <?= htmlspecialchars($viewing_order['Batch#']) ?>
    </div>

    <!-- Row 1: Brand / Model / Size -->
    <div class="grid">
      <div>
        <label>Brand</label>
        <input type="text" value="<?= htmlspecialchars($viewing_order['Brand']) ?>" readonly>
      </div>
      <div>
        <label>Model</label>
        <input type="text" value="<?= htmlspecialchars($viewing_order['Model']) ?>" readonly>
      </div>
      <div>
        <label>Size</label>
        <input type="text" value="<?= htmlspecialchars($viewing_order['Size'] ?? 'N/A') ?>" readonly>
      </div>
    </div>

    <!-- Row 2: Quantity / Price / Total -->
    <div class="grid">
      <div>
        <label>Quantity</label>
        <input type="number" value="<?= $quantity_val ?>" readonly>
      </div>
      <div>
        <label>Price per Item</label>
        <input type="text" value="₱<?= number_format($price_val, 2) ?>" readonly>
      </div>
      <div>
        <label>Total Amount</label>
        <input type="text" value="₱<?= number_format($total_val, 2) ?>" readonly style="font-weight:bold; background:#fffde7;">
      </div>
    </div>

    <!-- Row 3: Supplier Info -->
    <div class="grid">
      <div>
        <label>Supplier Name</label>
        <input type="text" value="<?= htmlspecialchars($viewing_order['SupplierName']) ?>" readonly>
      </div>
      <div>
        <label>Contact Number</label>
        <input type="text" value="<?= htmlspecialchars($viewing_order['Supplier Contact Number'] ?? '') ?>" readonly>
      </div>
      <div>
        <label>Email</label>
        <input type="email" value="<?= htmlspecialchars($viewing_order['Supplier Email']) ?>" readonly>
      </div>
    </div>

    <!-- Row 4: Order / Target Dates -->
    <div class="grid">
      <div>
        <label>Order Date</label>
        <input type="text" value="<?= date('F d, Y', strtotime($viewing_order['Order Date'])) ?>" readonly>
      </div>
      <div>
        <label>Target Arrival Date</label>
        <input type="text" value="<?= date('F d, Y', strtotime($viewing_order['Target Arrival Date'])) ?>" readonly>
      </div>
    </div>

    <!-- Footer Buttons -->
    <div class="footer">
      <a href="./index.php" class="btn btn-cancel">← Back to Order List</a>
      <button type="button" class="btn btn-submit" id="submitBtn">✓ Submit Order</button>
    </div>
  </div>

  <!-- Confirmation Modal -->
  <div id="confirmModal" class="modal">
    <div class="modal-content">
      <h3>Confirm Submission</h3>
      <p>Are you sure you want to submit this order? This will set the Ordered Date to today.</p>
      <form method="POST">
        <button type="submit" name="submit_order" class="btn-confirm">Yes, Submit</button>
        <button type="button" class="btn-cancel-modal" id="modalCancel">Cancel</button>
      </form>
    </div>
  </div>

  <script>
    const modal = document.getElementById('confirmModal');
    const btn = document.getElementById('submitBtn');
    const cancelBtn = document.getElementById('modalCancel');

    btn.onclick = () => { modal.style.display = 'block'; }
    cancelBtn.onclick = () => { modal.style.display = 'none'; }
    window.onclick = (e) => { if(e.target === modal) modal.style.display = 'none'; }
  </script>

</body>
</html>
