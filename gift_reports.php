<?php
session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

include("db/config.php");
include("abstract_helpers.php");

$success_msg = "";
$error_msg = "";

// ==========================================
// 1. HANDLE MANUAL STAFF ENTRY ADDITION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff_gift'])) {
    $gift_id = (int)$_POST['gift_id'];
    $quantity = (int)$_POST['quantity'];
    $notes = trim($_POST['notes']);

    if ($gift_id > 0 && $quantity > 0 && !empty($notes)) {
        // Check available stock
        $stock_check = $db->query("SELECT total_stock FROM gifts WHERE id = $gift_id")->fetch_assoc()['total_stock'] ?? 0;
        $used = $db->query("SELECT COUNT(*) as c FROM gift_distributions WHERE gift_id = $gift_id")->fetch_assoc()['c'] ?? 0;
        $remaining = $stock_check - $used;

        if ($quantity <= $remaining) {
            $stmt = $db->prepare("INSERT INTO gift_distributions (gift_id, student_id, notes, distributed_at) VALUES (?, NULL, ?, NOW())");
            $db->begin_transaction();
            try {
                for ($i = 0; $i < $quantity; $i++) {
                    $stmt->bind_param("is", $gift_id, $notes);
                    $stmt->execute();
                }
                $db->commit();
                $success_msg = "✅ Successfully added $quantity manual entry/entries for Staff/Organizer!";
            } catch (Exception $e) {
                $db->rollback();
                $error_msg = "❌ Database Error: " . $e->getMessage();
            }
            $stmt->close();
        } else {
            $error_msg = "❌ Not enough stock! Only $remaining remaining for this gift.";
        }
    } else {
        $error_msg = "❌ Please fill all fields correctly.";
    }
}

// ==========================================
// 2. HANDLE CSV EXPORT
// ==========================================
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=gift_distribution_report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    fputcsv($output, array('Date & Time', 'Recipient Name', 'Delegate ID', 'Role', 'Payment Status', 'Gift Name', 'Notes / Reason'));
    
    $query = "SELECT gd.distributed_at, gd.notes, ru.first_name, ru.last_name, ru.delegate_id, ru.role, ru.payment_status, g.gift_name 
              FROM gift_distributions gd 
              LEFT JOIN registered_users ru ON gd.student_id = ru.student_id 
              JOIN gifts g ON gd.gift_id = g.id 
              ORDER BY gd.distributed_at DESC";
              
    $result = $db->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $recipient_name = ($row['first_name']) ? trim($row['first_name'] . ' ' . $row['last_name']) : 'Staff / Organizer';
        $del_id = $row['delegate_id'] ? $row['delegate_id'] : 'N/A';
        $role = $row['role'] ? ucfirst($row['role']) : 'Internal';
        $payment = $row['payment_status'] ? ucfirst($row['payment_status']) : 'N/A';
        $notes = $row['notes'] ? $row['notes'] : '-';

        fputcsv($output, array(
            $row['distributed_at'],
            $recipient_name,
            $del_id,
            $role,
            $payment,
            $row['gift_name'],
            $notes
        ));
    }
    fclose($output);
    exit;
}

// ==========================================
// 3. FETCH DATA FOR TABLE & MODAL DROPDOWN
// ==========================================
$query = "SELECT gd.id, gd.distributed_at, gd.notes, ru.first_name, ru.last_name, ru.delegate_id, ru.role, ru.payment_status, g.gift_name 
          FROM gift_distributions gd 
          LEFT JOIN registered_users ru ON gd.student_id = ru.student_id 
          JOIN gifts g ON gd.gift_id = g.id 
          ORDER BY gd.distributed_at DESC";
$report_data = $db->query($query);

// Fetch gifts for the modal dropdown
$gifts_list = $db->query("SELECT id, gift_name FROM gifts ORDER BY gift_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gift Distribution Reports</title>
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
        .btn-export:hover { background: #218838; color: white; text-decoration: none; }
        
        .btn-add-staff {
            background: #17a2b8;
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
        .btn-add-staff:hover { background: #138496; color: white; text-decoration: none; }

        .badge-pay { font-size: 11px; }
        .text-staff { color: #6c757d; font-style: italic; }
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
                                <h5 class="m-b-10">Gift Distribution Reports</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <h5 class="m-b-0"><i class="feather icon-list"></i> All Distributed Gifts (Delegates + Staff)</h5>
                            <div>
                                <!-- Add Staff Entry Button (Triggers Modal) -->
                                <button type="button" class="btn-add-staff mr-2" data-toggle="modal" data-target="#staffModal">
                                    <i class="feather icon-plus-circle"></i> Add Staff Entry
                                </button>
                                <!-- Export Button -->
                                <a href="?export=csv" class="btn-export">
                                    <i class="feather icon-download"></i> Export CSV
                                </a>
                            </div>
                        </div>
                        <div class="card-block">
                            
                            <!-- Success/Error Messages -->
                            <?php if($success_msg): ?>
                                <div class="alert alert-success alert-dismissible fade show">
                                    <?php echo $success_msg; ?>
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            <?php endif; ?>
                            <?php if($error_msg): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?php echo $error_msg; ?>
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            <?php endif; ?>

                            <div class="dt-responsive table-responsive">
                                <table id="reportTable" class="table table-striped table-bordered nowrap">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Recipient Name</th>
                                            <th>Delegate ID</th>
                                            <th>Role</th>
                                            <th>Payment</th>
                                            <th>Gift Name</th>
                                            <th>Notes / Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($report_data && $report_data->num_rows > 0): ?>
                                            <?php while ($row = $report_data->fetch_assoc()): 
                                                $is_staff = !$row['first_name'];
                                                $name = $is_staff ? 'Staff / Organizer' : trim($row['first_name'] . ' ' . $row['last_name']);
                                                $del_id = $row['delegate_id'] ? $row['delegate_id'] : 'N/A';
                                                $role = $row['role'] ? ucfirst($row['role']) : 'Internal';
                                                $payment = $row['payment_status'] ? ucfirst($row['payment_status']) : 'N/A';
                                                $notes = $row['notes'] ? htmlspecialchars($row['notes']) : '-';
                                            ?>
                                                <tr>
                                                    <td><?php echo date('d M Y, h:i A', strtotime($row['distributed_at'])); ?></td>
                                                    <td class="<?php echo $is_staff ? 'text-staff font-weight-bold' : ''; ?>">
                                                        <?php echo htmlspecialchars($name); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($del_id); ?></td>
                                                    <td><?php echo htmlspecialchars($role); ?></td>
                                                    <td>
                                                        <?php if ($is_staff): ?>
                                                            <span class="badge badge-secondary">N/A</span>
                                                        <?php else: ?>
                                                            <span class="badge <?php echo ($row['payment_status'] === 'paid') ? 'badge-success' : 'badge-warning'; ?>">
                                                                <?php echo htmlspecialchars($payment); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['gift_name']); ?></td>
                                                    <td><?php echo $notes; ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No gift distributions found yet.</td>
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

    <!-- ========================================== -->
    <!-- MODAL: Add Manual Staff Entry              -->
    <!-- ========================================== -->
    <div class="modal fade" id="staffModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="feather icon-users"></i> Add Manual Staff/Organizer Entry</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Select Gift <span class="text-danger">*</span></label>
                            <select name="gift_id" class="form-control" required>
                                <option value="">-- Choose Gift --</option>
                                <?php while($g = $gifts_list->fetch_assoc()): ?>
                                    <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['gift_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" max="100" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Recipient Name / Reason <span class="text-danger">*</span></label>
                            <input type="text" name="notes" class="form-control" placeholder="e.g., Given to Chief Organizer Mr. Sharma" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_staff_gift" class="btn btn-primary">
                            <i class="feather icon-check"></i> Save Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap4.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <?php echo abstract_swal_js(); ?>

    <script>
        $(document).ready(function() {
            $('#reportTable').DataTable({
                "order": [[ 0, "desc" ]],
                "language": {
                    "search": "Search by Name, ID, Gift or Notes:"
                }
            });
        });
    </script>
</body>
</html>