<?php
session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

include("db/config.php");
include("abstract_helpers.php");

// ==========================================
// 1. HANDLE CSV EXPORT (Scalable: Uses same in-memory map)
// ==========================================
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=master_gift_checklist_' . date('Y-m-d_H-i-s') . '.csv');
    
    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility (Hindi/Special chars support)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Dynamic Headers
    $headers = ['Delegate Name', 'Delegate ID', 'Role', 'Payment Status'];
    foreach ($gifts_for_export as $g) {
        $headers[] = $g['gift_name'];
    }
    fputcsv($output, $headers);
    
    // Dynamic Rows
    foreach ($users_for_export as $user) {
        $row = [
            trim($user['first_name'] . ' ' . $user['last_name']),
            $user['delegate_id'],
            ucfirst($user['role']),
            ucfirst($user['payment_status'])
        ];
        
        foreach ($gifts_for_export as $gift) {
            // O(1) Memory Lookup
            if (isset($received_map[$user['student_id']][$gift['id']])) {
                $row[] = 'Yes';
            } else {
                $row[] = 'No';
            }
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit; // Stop execution after download
}

// ==========================================
// 2. SCALABLE DATA FETCHING (Sirf 3 Queries, chahe 10,000 users kyu na ho)
// ==========================================

// Query 1: Saare Gifts
$gifts_query = $db->query("SELECT id, gift_name FROM gifts ORDER BY id ASC");
$gifts = [];
$gifts_for_export = []; // Export ke liye alag se store
while ($g = $gifts_query->fetch_assoc()) {
    $gifts[] = $g;
    $gifts_for_export[] = $g;
}

// Query 2: Saare Registered Users
$users_query = $db->query("SELECT student_id, first_name, last_name, delegate_id, role, payment_status 
                           FROM registered_users 
                           ORDER BY student_id ASC");
$users = [];
$users_for_export = [];
while ($u = $users_query->fetch_assoc()) {
    $users[] = $u;
    $users_for_export[] = $u;
}

// Query 3: Saare Distribution Records (The Magic O(1) Map)
$dist_query = $db->query("SELECT student_id, gift_id FROM gift_distributions");
$received_map = [];
while ($d = $dist_query->fetch_assoc()) {
    $received_map[$d['student_id']][$d['gift_id']] = true;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Master Gift Checklist</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php echo abstract_swal_css(); ?>
    
    <style>
        .table-card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #eaeaea;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .check-cell {
            text-align: center;
            font-size: 22px;
            vertical-align: middle;
            width: 80px;
        }
        .text-success-custom { color: #28a745; }
        .text-muted-custom { color: #e9ecef; font-size: 28px; font-weight: bold; }
        
        /* Sticky first column for horizontal scrolling */
        .sticky-col {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 1;
            border-right: 2px solid #eaeaea;
            min-width: 200px;
        }
        thead .sticky-col {
            background: #f8f9fa;
            z-index: 2;
        }
        .btn-export {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-export:hover {
            background: #218838;
            color: white;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <?php include("header.php"); ?>
    <?php include("navbar.php"); ?>

    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Master Gift Distribution Checklist</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <h5 class="m-b-0">
                                <i class="feather icon-clipboard"></i> 
                                Delegate vs Gift Matrix 
                                <span class="badge badge-primary ml-2"><?php echo count($users); ?> Delegates</span>
                            </h5>
                            <!-- Export Button -->
                            <a href="?export=csv" class="btn-export">
                                <i class="feather icon-download"></i> Export to Excel / CSV
                            </a>
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table id="checklistTable" class="table table-hover table-bordered nowrap" style="width:100%">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="sticky-col">Delegate Name & ID</th>
                                            <th>Role</th>
                                            <th>Payment</th>
                                            <?php foreach ($gifts as $gift): ?>
                                                <th class="text-center"><?php echo htmlspecialchars($gift['gift_name']); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                        <!-- Total Distributed Row -->
                                        <tr class="bg-light">
                                            <th class="sticky-col">Total Given</th>
                                            <th>-</th>
                                            <th>-</th>
                                            <?php 
                                            foreach ($gifts as $gift): 
                                                $count = 0;
                                                foreach($received_map as $user_id => $user_gifts) {
                                                    if (isset($user_gifts[$gift['id']])) $count++;
                                                }
                                            ?>
                                                <th class="text-center font-weight-bold text-primary"><?php echo $count; ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($users) > 0): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td class="sticky-col">
                                                        <div class="font-weight-bold text-dark"><?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($user['delegate_id']); ?></small>
                                                    </td>
                                                    
                                                    <td>
                                                        <span class="badge badge-light-primary"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                                                    </td>
                                                    
                                                    <td>
                                                        <span class="badge <?php echo ($user['payment_status'] === 'paid') ? 'badge-success' : 'badge-warning'; ?>">
                                                            <?php echo ucfirst($user['payment_status']); ?>
                                                        </span>
                                                    </td>

                                                    <!-- Gift Checkboxes (O(1) Lookup) -->
                                                    <?php foreach ($gifts as $gift): ?>
                                                        <td class="check-cell">
                                                            <?php if (isset($received_map[$user['student_id']][$gift['id']])): ?>
                                                                <i class="feather icon-check-circle text-success-custom" title="Received"></i>
                                                            <?php else: ?>
                                                                <span class="text-muted-custom" title="Not Received">–</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="<?php echo 3 + count($gifts); ?>" class="text-center text-muted py-4">
                                                    No registered delegates found.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap4.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <?php echo abstract_swal_js(); ?>

    <script>
        $(document).ready(function() {
            $('#checklistTable').DataTable({
                "pageLength": 100, // 1000+ users ke liye 100 rows per page best hai
                "order": [[ 0, "asc" ]], 
                "language": {
                    "search": "Search Delegate Name or ID:"
                },
                "dom": '<"top"lf>rt<"bottom"ip><"clear">'
            });
        });
    </script>
</body>
</html>