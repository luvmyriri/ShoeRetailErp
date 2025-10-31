<?php
include './Connection.php';

// ✅ Get Supplier ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid supplier ID");
}

$supplierID = intval($_GET['id']);

// ✅ Fetch Supplier Data
$sql = "SELECT * FROM suppliers WHERE SupplierID = $supplierID LIMIT 1";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    die("Supplier not found!");
}

$supplier = $result->fetch_assoc();

// ✅ UPDATE Supplier (POST submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierName = $_POST['supplier_name'];
    $contactPerson = $_POST['contact_person'];
    $email = $_POST['email'];
    $contactNumber = $_POST['contact_number'];
    $address = $_POST['address'];
    $payment = $_POST['payment'];
    $status = $_POST['status'];

    $update_sql = "UPDATE suppliers SET 
                    SupplierName = '$supplierName',
                    ContactName = '$contactPerson',
                    Email = '$email',
                    Phone = '$contactNumber',
                    Address = '$address',
                    PaymentTerms = '$payment',
                    Status = '$status'
                WHERE SupplierID = $supplierID";

    if ($conn->query($update_sql)) {
        echo "<script>
                alert('Supplier updated successfully!');
                window.location.href = './index.php';
              </script>";
        exit();
    } else {
        echo "<script>alert('Error updating supplier!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Supplier</title>
<link rel="stylesheet" href="./css/addsupplier.css">
</head>
<body>

<div class="form-container">
     <h2>Edit Supplier Info</h2>
    <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

    <form method="POST" id="editForm">

        <input type="hidden" name="SupplierID" value="<?= $supplier['SupplierID'] ?>">

        <div class="grid" style="grid-template-columns:1fr 1fr;">
            <div>
                <label>Supplier Name</label>
                <input type="text" name="supplier_name" value="<?= $supplier['SupplierName'] ?>" required>
            </div>
            <div>
                <label>Contact Person</label>
                <input type="text" name="contact_person" value="<?= $supplier['ContactName'] ?>" required>
            </div>
        </div>

        <div class="grid">
            <div>
                <label>Email</label>
                <input type="email" name="email" value="<?= $supplier['Email'] ?>" required>
            </div>
            <div>
                <label>Contact Number</label>
                <input type="text" name="contact_number" value="<?= $supplier['Phone'] ?>" required>
            </div>
            <div>
                <label>Address</label>
                <input type="text" name="address" value="<?= $supplier['Address'] ?>" required>
            </div>
        </div>

        <div class="grid" style="grid-template-columns:1fr 1fr; gap:18px;">
            <div>
                <label>Payment Terms</label>
                <input type="text" name="payment" value="<?= $supplier['PaymentTerms'] ?>" required>
            </div>
            <div>
                <label>Status</label>
               <select id="status" name="status" required style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    <option value="Active" <?= $supplier['Status'] == "Active" ? "selected" : "" ?>>Active</option>
                    <option value="Inactive" <?= $supplier['Status'] == "Inactive" ? "selected" : "" ?>>Inactive</option>
                </select>
            </div>
        </div>

        <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

        <div class="footer">
            <a href="./index.php" class="btn btn-cancel">Cancel</a>
            <button type="button" class="btn btn-submit" onclick="openModal()">Update Information</button>
        </div>

    </form>
</div>

<!-- ✅ Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Update</h3>
        <p>Are you sure you want to update this supplier?</p>
        <button class="btn btn-submit" onclick="document.getElementById('editForm').submit()">Yes, Update</button>
        <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('confirmModal').style.display = "block";
}
function closeModal() {
    document.getElementById('confirmModal').style.display = "none";
}
</script>

</body>
</html>

<?php $conn->close(); ?>
