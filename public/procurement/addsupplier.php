<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /ShoeRetailErp/login.php'); exit; }
require_once '../../config/database.php';
require_once '../../includes/core_functions.php';

$error = '';
$errorField = '';

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_name  = trim($_POST['supplier_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $payment_term   = trim($_POST['payment_term'] ?? '');

    try {
        // Check for duplicates
        $existing = dbFetchOne(
            "SELECT SupplierID FROM Suppliers WHERE Email = ? OR Phone = ?",
            [$email, $contact_number]
        );
        
        if ($existing) {
            // Determine which field is duplicate
            $emailCheck = dbFetchOne("SELECT SupplierID FROM Suppliers WHERE Email = ?", [$email]);
            if ($emailCheck) {
                $error = 'Email already exists!';
                $errorField = 'email';
            } else {
                $error = 'Phone number already exists!';
                $errorField = 'contact_number';
            }
        } else {
            $supplierId = dbInsert(
                "INSERT INTO Suppliers (SupplierName, ContactName, Email, Phone, Address, PaymentTerms, Status) 
                 VALUES (?, ?, ?, ?, ?, ?, 'Active')",
                [$supplier_name, $contact_person, $email, $contact_number, $address, $payment_term]
            );
            
            logInfo('Supplier added', ['supplier_id' => $supplierId, 'name' => $supplier_name]);
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showModal('Success', 'Supplier added successfully!', 'success', function() {
                            window.location.href = './index.php';
                        });
                    });
                  </script>";
            exit;
        }
    } catch (Exception $e) {
        logError('Failed to add supplier', ['error' => $e->getMessage()]);
        $error = 'Database Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Supplier - Shoe Retail ERP</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="./css/addsupplier.css">

<style>
/* ✅ Confirmation Modal */
.modal {
    display: none; 
    position: fixed;
    z-index: 9999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
}
.modal-content {
    background: #fff;
    width: 400px;
    margin: 15% auto;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
}
.modal button {
    margin: 10px;
}

/* ✅ Highlight Error Field */
.input-error {
    border: 2px solid #e63946 !important;
    background: #ffe6e6;
}
</style>

</head>
<body>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/modal.php'; ?>

<div class="form-container">
     <h2>Add Supplier</h2>
    <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px; padding: 10px; background: #fee; border: 1px solid #fcc; border-radius: 5px; color: #c00;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="addSupplierForm">
        <div class="grid" style="grid-template-columns: 1fr 1fr;">
            <div>
                <label>Supplier Name</label>
                <input type="text" name="supplier_name" value="<?= htmlspecialchars($_POST['supplier_name'] ?? '') ?>" required>
            </div>
            <div>
                <label>Contact Person</label>
                <input type="text" name="contact_person" value="<?= htmlspecialchars($_POST['contact_person'] ?? '') ?>" required>
            </div>
        </div>

        <div class="grid">
            <div>
                <label>Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="<?= $errorField === 'email' ? 'input-error' : '' ?>" required>
            </div>
            <div>
                <label>Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>" class="<?= $errorField === 'contact_number' ? 'input-error' : '' ?>" required>
            </div>
            <div>
                <label>Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required>
            </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr;">
            <div>
                <label>Payment Term</label>
                <input type="text" name="payment_term" value="<?= htmlspecialchars($_POST['payment_term'] ?? '') ?>" required>
            </div>
        </div>

        <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

        <div class="footer">
            <a href="./index.php" class="btn btn-cancel">Cancel</a>
            <button type="button" class="btn btn-submit" onclick="openConfirm()">Submit Supplier</button>
        </div>
    </form>
</div>


<!-- ✅ Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <h3>Confirm</h3>
        <p>Are you sure you want to add this supplier?</p>
        <button class="btn btn-submit" onclick="submitForm()">Yes, Add</button>
        <button class="btn btn-cancel" onclick="closeConfirm()">Cancel</button>
    </div>
</div>


<script>
// ✅ Modal Controls
function openConfirm() {
    document.getElementById('confirmModal').style.display = "block";
}
function closeConfirm() {
    document.getElementById('confirmModal').style.display = "none";
}
function submitForm() {
    document.getElementById('addSupplierForm').submit();
}
</script>
<script>
// ✅ Validate Fields Before Confirmation
function validateForm() {
    let isValid = true;
    let inputs = document.querySelectorAll("#addSupplierForm input[required]");

    // Remove previous highlights
    inputs.forEach(input => input.classList.remove('input-error'));

    inputs.forEach(input => {
        if (input.value.trim() === "") {
            isValid = false;
            input.classList.add('input-error'); 
        }
    });

    if (!isValid) {
        showModal('Warning', 'Please fill out all required fields before submitting.', 'warning');
        return false;
    }
    
    return true;
}

// ✅ Open Confirm Modal ONLY if all fields are filled
function openConfirm() {
    if (validateForm()) {
        document.getElementById('confirmModal').style.display = "block";
    }
}

function closeConfirm() {
    document.getElementById('confirmModal').style.display = "none";
}

function submitForm() {
    document.getElementById('addSupplierForm').submit();
}
</script>

</body>
</html>
