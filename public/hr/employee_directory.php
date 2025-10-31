<?php
// 1. Start session
session_start();

// 2. Include database connection
include('../../config/database.php'); // Use the main config file

// 3. Fetch all employees
$employees = [];
$fetchError = null;
try {
    $employees = dbFetchAll("SELECT * FROM employees ORDER BY FirstName, LastName");
} catch (Exception $e) {
    $fetchError = "Error fetching employee data: " . $e->getMessage();
    error_log($fetchError);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Directory - Shoe Retail ERP</title>
    
    <link rel="stylesheet" href="../css/style.css"> 
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        /* Styles for the employee cards */
        body {
            /* Use your site's main background color */
            background-color: #f8f9fa; 
        }
        .employee-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); /* Softer shadow */
            text-align: center;
            padding: 25px 20px;
            transition: all 0.3s ease;
            height: 100%; /* Makes all cards in a row the same height */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.12);
        }
        .employee-img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px auto; /* Center the image */
            border: 3px solid #f0f0f0;
        }
        .status-badge {
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .status-active {
            background-color: #e0f8e9; /* Lighter green */
            color: #1d7b3c;
        }
        .status-on-leave {
            background-color: #fef3c7; /* Yellow */
            color: #b45309;
        }
        .status-inactive {
            background-color: #fde8e8; /* Lighter red */
            color: #b91c1c;
        }
        .card-emp-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        .card-emp-role {
            font-size: 0.9rem;
            color: #714B67; /* Use your theme color */
            font-weight: 500;
            margin-bottom: 12px;
        }
        .card-emp-details {
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 5px;
            word-break: break-all; /* Fixes long emails */
        }
        .card-emp-joined {
            font-size: 0.75rem;
            color: #888;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .btn-view-details {
            background-color: #714B67;
            border-color: #714B67;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .btn-view-details:hover {
            background-color: #5a3c53;
            border-color: #5a3c53;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="main-wrapper" style="margin-left: 0;">
        
        <main class="main-content">
            
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Employee Directory</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / 
                        <a href="/ShoeRetailErp/public/hr/index.php">HR Management</a> / 
                        Employee Directory
                    </div>
                </div>
                <div class="page-header-actions">
                    </div>
            </div>

            <div class="container-fluid py-4"> <?php if ($fetchError): ?>
                    <div class="alert alert-danger"><?php echo $fetchError; ?></div>
                <?php endif; ?>

                <div class="row g-4">
                    <?php
                    if (empty($employees)) {
                        echo "<p>No employees found in the database.</p>";
                    } else {
                        foreach ($employees as $row) {
                            // Determine status class
                            $status = htmlspecialchars($row['Status']);
                            $statusClass = 'status-inactive'; // Default
                            if ($status == 'Active') {
                                $statusClass = 'status-active';
                            } elseif ($status == 'On Leave') {
                                $statusClass = 'status-on-leave';
                            }
                            
                            // Handle missing image
                            $imagePath = !empty($row['image']) ? '../uploads/' . htmlspecialchars($row['image']) : '../assets/img/default-profile.png'; // Fallback image
                            
                            echo '
                            <div class="col-xl-3 col-lg-3 col-md-4">
                                <div class="employee-card">
                                    <div> <img src="'.$imagePath.'" alt="Profile" class="employee-img">
                                        <div class="status-badge '.$statusClass.'">'.$status.'</div>
                                        <h5 class="card-emp-name">'.htmlspecialchars($row['FirstName']).' '.htmlspecialchars($row['LastName']).'</h5>
                                        <p class="card-emp-role">'.htmlspecialchars($row['Role']).'</p>
                                        <p class="card-emp-details"><i class="bi bi-envelope-fill text-muted"></i> '.htmlspecialchars($row['Email']).'</p>
                                        <p class="card-emp-details"><i class="bi bi-telephone-fill text-muted"></i> '.htmlspecialchars($row['Phone'] ?? 'N/A').'</p>
                                        <p class="card-emp-joined">Joined: '.date('M d, Y', strtotime($row['HireDate'])).'</p>
                                    </div>
                                    <div> <a href="details.php?id='.$row['EmployeeID'].'" class="btn btn-view-details mt-2">View Details</a>
                                    </div>
                                </div>
                            </div>
                            ';
                        }
                    }
                    ?>
                </div>
            </div>

        </main>
    </div>

    <div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
      </div>

    <script src="../js/app.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>