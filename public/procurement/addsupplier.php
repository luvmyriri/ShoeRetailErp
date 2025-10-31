<?php
include './Connection.php';

// ✅ Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $supplier_name  = trim($_POST['supplier_name']);
    $contact_person = trim($_POST['contact_person']);
    $email          = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $address        = trim($_POST['address']);
    $payment_term   = trim($_POST['payment_term']);

    $insert_sql = "INSERT INTO suppliers 
        (SupplierName, ContactName, Email, Phone, Address, PaymentTerms, Status)
        VALUES 
        ('$supplier_name', '$contact_person', '$email', '$contact_number', '$address', '$payment_term', 'Active')";

    try {
        if ($conn->query($insert_sql)) {
            echo "<script>
                    alert('✅ Supplier added successfully!');
                    window.location.href = './index.php';
                  </script>";
            exit();
        }
    } catch (mysqli_sql_exception $e) {

        // ✅ Remove previous errors/highlights
        echo "<script>
                document.querySelectorAll('input').forEach(el => el.classList.remove('input-error'));
              </script>";

        if ($e->getCode() == 1062) {

            // ✅ Duplicate Email
            if (str_contains($e->getMessage(), 'Email')) {
                echo "<script>
                    const field = document.getElementById('email');
                    field.classList.add('input-error');
                    field.focus();
                    alert('❌ Email already exists!');
                </script>";
            }

            // ✅ Duplicate Phone
            elseif (str_contains($e->getMessage(), 'Phone')) {
                echo "<script>
                    const field = document.getElementById('contact_number');
                    field.classList.add('input-error');
                    field.focus();
                    alert('❌ Phone number already exists!');
                </script>";
            }
        } else {
            echo "<script>alert('❌ Database Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Supplier</title>

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

<div class="form-container">
     <h2>Add Supplier</h2>
    <hr style="margin:18px 0;border:none;border-top:1px solid #eee;" />

    <form method="POST" id="addSupplierForm">

        <div class="grid" style="grid-template-columns: 1fr 1fr;">
            <div>
                <label>Supplier Name</label>
                <input type="text" name="supplier_name" required>
            </div>
            <div>
                <label>Contact Person</label>
                <input type="text" name="contact_person" required>
            </div>
        </div>

        <div class="grid">
            <div>
                <label>Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label>Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" required>
            </div>
            <div>
                <label>Address</label>
                <input type="text" name="address" required>
            </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr;">
            <div>
                <label>Payment Term</label>
                <input type="text" name="payment_term" required>
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
        alert("⚠️ Please fill out all required fields before submitting.");
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

<?php $conn->close(); ?>
