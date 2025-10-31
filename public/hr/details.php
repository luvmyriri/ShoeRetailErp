<?php
include('../../includes/dbconnection.php');

$id = intval($_GET['id']); // sanitize

// Helper to safely get row values
function val($row, $key) {
  return isset($row[$key]) ? htmlspecialchars($row[$key]) : '';
}

// -- Handle Save (employee update) --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_employee'])) {
  // Sanitize & escape inputs
  $FirstName = mysqli_real_escape_string($conn, $_POST['FirstName'] ?? '');
  $LastName  = mysqli_real_escape_string($conn, $_POST['LastName'] ?? '');
  $Gender    = mysqli_real_escape_string($conn, $_POST['Gender'] ?? '');
  $MaritalStatus = mysqli_real_escape_string($conn, $_POST['MaritalStatus'] ?? '');
  $BirthDate = mysqli_real_escape_string($conn, $_POST['BirthDate'] ?? '');
  $PlaceOfBirth = mysqli_real_escape_string($conn, $_POST['PlaceOfBirth'] ?? '');
  $Age = mysqli_real_escape_string($conn, $_POST['Age'] ?? '');
  $street_house_no = mysqli_real_escape_string($conn, $_POST['street_house_no'] ?? '');
  $residential_address = mysqli_real_escape_string($conn, $_POST['residential_address'] ?? '');
  $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
  $region = mysqli_real_escape_string($conn, $_POST['region'] ?? '');
  $zip_code = mysqli_real_escape_string($conn, $_POST['zip_code'] ?? '');
  $Phone = mysqli_real_escape_string($conn, $_POST['Phone'] ?? '');
  $Email = mysqli_real_escape_string($conn, $_POST['Email'] ?? '');

  // Emergency Contact
  $emergency_contact_name = mysqli_real_escape_string($conn, $_POST['emergency_contact_name'] ?? '');
  $emergency_contact_number = mysqli_real_escape_string($conn, $_POST['emergency_contact_number'] ?? '');

  // Financial
  $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name'] ?? '');
  $account_name = mysqli_real_escape_string($conn, $_POST['account_name'] ?? '');
  $account_number = mysqli_real_escape_string($conn, $_POST['account_number'] ?? ''); // <-- changed from bank_account
  $sss = mysqli_real_escape_string($conn, $_POST['sss'] ?? '');
  $philhealth = mysqli_real_escape_string($conn, $_POST['philhealth'] ?? '');
  $pag_ibig = mysqli_real_escape_string($conn, $_POST['pag_ibig'] ?? ''); // <-- matches DB column
  $tin = mysqli_real_escape_string($conn, $_POST['tin'] ?? '');

  // Build UPDATE query with correct column names
  $updateQuery = "
    UPDATE employees
    SET
      FirstName='$FirstName',
      LastName='$LastName',
      Gender='$Gender',
      MaritalStatus='$MaritalStatus',
      BirthDate='$BirthDate',
      PlaceOfBirth='$PlaceOfBirth',
      Age='$Age',
      street_house_no='$street_house_no',
      residential_address='$residential_address',
      city='$city',
      region='$region',
      zip_code='$zip_code',
      Phone='$Phone',
      Email='$Email',
      emergency_contact_name='$emergency_contact_name',
      emergency_contact_number='$emergency_contact_number',
      bank_name='$bank_name',
      account_name='$account_name',
      account_number='$account_number',
      sss='$sss',
      philhealth='$philhealth',
      pag_ibig='$pag_ibig',
      tin='$tin'
    WHERE EmployeeID='$id'
  ";

  if (mysqli_query($conn, $updateQuery)) {
    echo "<script>alert('Employee information updated successfully!');</script>";
    echo "<script>window.location.href='details.php?id=$id';</script>";
    exit;
  } else {
    echo "<script>alert('Error updating record: " . mysqli_real_escape_string($conn, mysqli_error($conn)) . "');</script>";
  }
}

// -- Handle Contract Upload --
$uploadedContract = false;
$contractFilename = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_contract'])) {
  if (!empty($_FILES['contractFile']['name'])) {
    $allowed = ['pdf','doc','docx'];
    $orig = $_FILES['contractFile']['name'];
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
      $targetDir = __DIR__ . '/contracts/';
      if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

      $newName = 'contract_emp_' . $id . '.' . $ext;
      $targetPath = $targetDir . $newName;

      if (move_uploaded_file($_FILES['contractFile']['tmp_name'], $targetPath)) {
        $uploadedContract = true;
        @chmod($targetPath, 0644);
        echo "<script>alert('Contract uploaded successfully.');</script>";
      } else {
        echo "<script>alert('Failed to move uploaded file. Check permissions.');</script>";
      }
    } else {
      echo "<script>alert('Invalid file type. Allowed: pdf, doc, docx');</script>";
    }
  } else {
    echo "<script>alert('No file selected for upload.');</script>";
  }
}

// Fetch employee info
$query = mysqli_query($conn, "SELECT * FROM employees WHERE EmployeeID = '$id'");
if (!$query) {
  die("Query failed: " . mysqli_error($conn));
}
$row = mysqli_fetch_array($query);

// Find contract file if exists
$contractPathRel = '';
foreach (['pdf','doc','docx'] as $e) {
  $p = __DIR__ . "/contracts/contract_emp_{$id}.$e";
  if (file_exists($p)) {
    $contractPathRel = "contracts/contract_emp_{$id}.$e";
    break;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Details - <?php echo val($row,'FirstName') . ' ' . val($row,'LastName'); ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* [Your existing styles - unchanged] */
    body { background-color: #f4f6f9; font-family: 'Poppins', sans-serif; }
    .container-xl { max-width:1200px; }
    .profile-header { display:flex; align-items:center; background:#fff; border-radius:15px; padding:18px; box-shadow:0 3px 10px rgba(0,0,0,0.06); margin-bottom:18px; }
    .profile-header img { width:96px; height:96px; border-radius:50%; object-fit:cover; margin-right:18px; border:3px solid #f4a226; }
    .nav-tabs { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); margin-bottom:16px; overflow:hidden; }
    .nav-tabs .nav-link { color:#495057; font-weight:500; border:none; padding:12px 18px; }
    .nav-tabs .nav-link.active { background-color:#f4a226 !important; color:white !important; border-radius:8px; }
    .card { border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.03); background:#fff; padding:20px; margin-bottom:18px; }
    .section-title { font-weight:600; font-size:1.1rem; margin-bottom:16px; color:#333; border-bottom:2px solid #f4a226; display:inline-block; padding-bottom:6px; }
    .subsection-title { font-weight:600; font-size:0.95rem; color:#6c757d; margin-top:20px; margin-bottom:12px; }
    .form-control[readonly] { background:#f8f9fa; border:1px solid #e9ecef; pointer-events:none; }
    .btn-theme { background:#f4a226; color:#fff; border:none; font-weight:600; padding:10px 24px; border-radius:8px; transition: all 0.3s; }
    .btn-theme:hover { background:#d98f20; color:#fff; transform: translateY(-1px); }
    .back-button-container { padding-top:12px; padding-left:18px; padding-bottom:6px; }
    .custom-back-btn { background:#f4a226; color:white; border:none; border-radius:20px; font-weight:bold; padding:6px 14px; font-size:0.95rem; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; transition: all 0.3s; }
    .custom-back-btn:hover { background:#e0951b; color:white; text-decoration:none; transform: translateX(-3px); }
    .edit-btn-row { display:flex; justify-content:center; gap:12px; margin-top:24px; margin-bottom:30px; }
    .small-muted { font-size:0.9rem; color:#6c757d; }
    .form-label { font-weight:500; color:#495057; margin-bottom:6px; }
    @media (max-width:767px) {
      .profile-header { flex-direction:row; gap:12px; }
      .profile-header img { width:84px; height:84px; }
    }
  </style>
</head>
<body>
<div class="container container-xl py-4">

  <!-- back -->
   <!-- Back Button -->
  <div class="back-button-container">
    <a href="employee_directory.php" class="btn custom-back-btn">
      <i class="bi bi-arrow-left"></i> 
    </a>
  </div>

  <!-- profile header -->
  <div class="profile-header">
    <img src="../uploads/<?php echo val($row,'image'); ?>" alt="Profile">
    <div>
      <h4 style="margin:0;"><?php echo val($row,'FirstName') . ' ' . val($row,'LastName'); ?></h4>
      <div style="margin-top:6px;">
        <span class="badge bg-success"><?php echo strtoupper(val($row,'Status')); ?></span>
      </div>
      <p class="small-muted mb-0"><?php echo val($row,'Role'); ?></p>
      <small class="small-muted">Employee ID: #<?php echo val($row,'EmployeeID'); ?></small>
    </div>
  </div>

  <!-- tabs -->
  <ul class="nav nav-tabs" id="employeeTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button">Personal Information</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="contract-tab" data-bs-toggle="tab" data-bs-target="#contract" type="button">Contract</button>
    </li>
  </ul>

  <div class="tab-content" id="employeeTabContent">

    <!-- PERSONAL INFORMATION -->
    <div class="tab-pane fade show active" id="personal">
      <form method="POST" id="editForm" enctype="multipart/form-data">
        <input type="hidden" name="save_employee" value="1">

        <div class="row g-3">
          <!-- LEFT column: Personal + Financial -->
          <div class="col-lg-6 col-12">
            <!-- Personal Information -->
            <div class="card">
              <div class="section-title">Personal Information</div>
              <div class="mb-3">
                <label class="form-label">First Name</label>
                <input class="form-control" name="FirstName" value="<?php echo val($row,'FirstName'); ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input class="form-control" name="LastName" value="<?php echo val($row,'LastName'); ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Gender</label>
                <input class="form-control" name="Gender" value="<?php echo val($row,'Gender'); ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Marital Status</label>
                <input class="form-control" name="MaritalStatus" value="<?php echo val($row,'MaritalStatus'); ?>" readonly>
              </div>

              <div class="row">
                <div class="col-md-7 mb-3">
                  <label class="form-label">Birthdate</label>
                  <input class="form-control" type="date" name="BirthDate" id="BirthDate" value="<?php echo val($row,'BirthDate'); ?>" readonly>
                </div>
                <div class="col-md-5 mb-3">
                  <label class="form-label">Age</label>
                  <input class="form-control" type="number" name="Age" id="Age" value="<?php echo val($row,'Age'); ?>" readonly>
                </div>
              </div>

              <div class="mb-0">
                <label class="form-label">Place of Birth</label>
                <input class="form-control" name="PlaceOfBirth" value="<?php echo val($row,'PlaceOfBirth'); ?>" readonly>
              </div>
            </div>

            <!-- Financial Information -->
            <div class="card">
              <div class="section-title">Financial Information</div>

              <!-- Bank Details -->
              <div class="subsection-title">Bank Details</div>
              <div class="mb-3">
                <label class="form-label">Bank Name</label>
                <input class="form-control" name="bank_name" value="<?php echo val($row,'bank_name'); ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Account Name <small class="text-muted">(Must match employee name)</small></label>
                <input class="form-control" name="account_name" value="<?php echo val($row,'account_name'); ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Account Number</label>
                <input class="form-control" name="account_number" value="<?php echo val($row,'account_number'); ?>" readonly>
              </div>

              <!-- Government IDs -->
              <div class="subsection-title">Government IDs</div>
              <div class="mb-3">
                <label class="form-label">SSS</label>
                <input class="form-control" name="sss" value="<?php echo val($row,'sss'); ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">PhilHealth</label>
                <input class="form-control" name="philhealth" value="<?php echo val($row,'philhealth'); ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Pag-IBIG</label>
                <input class="form-control" name="pag_ibig" value="<?php echo val($row,'pag_ibig'); ?>" readonly>
              </div>
              <div class="mb-0">
                <label class="form-label">TIN</label>
                <input class="form-control" name="tin" value="<?php echo val($row,'tin'); ?>" readonly>
              </div>
            </div>
          </div>

          <!-- RIGHT column: Contact + Address -->
          <div class="col-lg-6 col-12">
            <!-- Contact Information -->
            <div class="card">
              <div class="section-title">Contact Information</div>
              <div class="mb-3">
                <label class="form-label">Phone</label>
                <input class="form-control" name="Phone" value="<?php echo val($row,'Phone'); ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control" name="Email" value="<?php echo val($row,'Email'); ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Emergency Contact Name</label>
                <input class="form-control" name="emergency_contact_name" value="<?php echo val($row,'emergency_contact_name'); ?>" readonly>
              </div>
              <div class="mb-0">
                <label class="form-label">Emergency Contact No.</label>
                <input class="form-control" name="emergency_contact_number" value="<?php echo val($row,'emergency_contact_number'); ?>" readonly>
              </div>
            </div>

            <!-- Address Information -->
            <div class="card">
              <div class="section-title">Address Information</div>
              <div class="mb-3">
                <label class="form-label">Street / House No.</label>
                <input class="form-control" name="street_house_no" value="<?php echo val($row,'street_house_no'); ?>" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Residential Address</label>
                <input class="form-control" name="residential_address" value="<?php echo val($row,'residential_address'); ?>" readonly>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">City</label>
                  <input class="form-control" name="city" value="<?php echo val($row,'city'); ?>" readonly>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Region</label>
                  <input class="form-control" name="region" value="<?php echo val($row,'region'); ?>" readonly>
                </div>
              </div>
              <div class="mb-0">
                <label class="form-label">ZIP Code</label>
                <input class="form-control" name="zip_code" value="<?php echo val($row,'zip_code'); ?>" readonly>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit / Save Buttons -->
        <div class="edit-btn-row">
          <button type="button" id="editBtn" class="btn btn-theme"><i class="bi bi-pencil me-2"></i>Edit Information</button>
          <button type="submit" name="save_employee" id="saveBtn" class="btn btn-theme" style="display:none;">Save Changes</button>
        </div>
      </form>
    </div>

    <!-- CONTRACT TAB -->
    <div class="tab-pane fade" id="contract">
      <div class="card">
        <div class="section-title">Contract Information</div>
        <p class="small-muted mb-4">You can view, attach, or export the employee's contract below.</p>

        <div class="d-flex flex-wrap align-items-center gap-2">
          <?php if ($contractPathRel): ?>
            <a href="<?php echo $contractPathRel; ?>" target="_blank" class="btn btn-theme">View Contract</a>
            <a href="<?php echo $contractPathRel; ?>" download class="btn btn-theme">Download</a>
          <?php else: ?>
            <span class="small-muted">No contract attached yet.</span>
          <?php endif; ?>
        </div>

        <hr class="my-4">

        <form action="details.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" class="d-flex flex-wrap align-items-end gap-3">
          <div style="flex: 1; min-width: 250px;">
            <label class="form-label">Upload New Contract</label>
            <input type="file" name="contractFile" accept=".pdf,.doc,.docx" class="form-control">
            <small class="text-muted">Accepted formats: PDF, DOC, DOCX</small>
          </div>
          <button type="submit" name="upload_contract" class="btn btn-theme">Upload Contract</button>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
  // Auto-calculate age
  const birthInput = document.getElementById('BirthDate');
  const ageInput = document.getElementById('Age');

  function calculateAge() {
    const birthDate = new Date(birthInput.value);
    const today = new Date();
    if (!isNaN(birthDate.getTime())) {
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      ageInput.value = age;
    } else {
      ageInput.value = '';
    }
  }

  birthInput.addEventListener('change', calculateAge);
  birthInput.addEventListener('input', calculateAge);

  // Enable editing
  document.getElementById('editBtn').addEventListener('click', function() {
    document.querySelectorAll('#editForm input').forEach(inp => inp.removeAttribute('readonly'));
    document.getElementById('editBtn').style.display = 'none';
    document.getElementById('saveBtn').style.display = 'inline-block';
    document.querySelector('#editForm input[name="FirstName"]').focus();
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>