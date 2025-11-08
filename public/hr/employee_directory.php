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
    <style>
        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .employee-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            text-align: center;
            padding: 1.5rem;
            transition: all var(--transition-base);
            display: flex;
            flex-direction: column;
        }
        .employee-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        .employee-img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            border: 3px solid var(--gray-100);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            display: inline-block;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }
        .status-active { background-color: var(--success-bg); color: var(--success-color); }
        .status-on-leave { background-color: var(--warning-bg); color: var(--warning-color); }
        .status-inactive { background-color: var(--danger-bg); color: var(--danger-color); }
        .card-emp-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }
        .card-emp-role {
            font-size: 0.875rem;
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 0.75rem;
        }
        .card-emp-details {
            font-size: 0.813rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            word-break: break-word;
        }
        .card-emp-joined {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/modal.php'; ?>
    
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
                    <button class="btn btn-primary" onclick="window.location.href='index.php'"><i class="fas fa-plus"></i> Add Employee</button>
                </div>
            </div>

            <?php if ($fetchError): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showModal('Error', '<?php echo addslashes($fetchError); ?>', 'error');
                    });
                </script>
            <?php endif; ?>

            <div class="employee-grid">
                <?php if (empty($employees)): ?>
                    <div class="card" style="text-align: center; padding: 3rem; grid-column: 1 / -1;">
                        <i class="fas fa-users" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                        <p style="color: var(--gray-500);">No employees found in the database.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($employees as $row): ?>
                        <?php
                            $status = htmlspecialchars($row['Status'] ?? 'Active');
                            $statusClass = 'status-active';
                            if ($status == 'On Leave') $statusClass = 'status-on-leave';
                            elseif ($status == 'Inactive') $statusClass = 'status-inactive';
                            
                            $imagePath = !empty($row['image']) ? '../uploads/' . htmlspecialchars($row['image']) : '../assets/img/default-profile.png';
                        ?>
                        <div class="employee-card">
                            <div>
                                <img src="<?= $imagePath ?>" alt="Profile" class="employee-img">
                                <div class="status-badge <?= $statusClass ?>"><?= $status ?></div>
                                <h5 class="card-emp-name"><?= htmlspecialchars($row['FirstName']) ?> <?= htmlspecialchars($row['LastName']) ?></h5>
                                <p class="card-emp-role"><?= htmlspecialchars($row['Role']) ?></p>
                                <p class="card-emp-details"><i class="fas fa-envelope"></i> <?= htmlspecialchars($row['Email']) ?></p>
                                <p class="card-emp-details"><i class="fas fa-phone"></i> <?= htmlspecialchars($row['Phone'] ?? 'N/A') ?></p>
                                <p class="card-emp-joined">Joined: <?= date('M d, Y', strtotime($row['HireDate'])) ?></p>
                            </div>
                            <div>
                                <a href="details.php?id=<?= $row['EmployeeID'] ?>" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>