<?php
session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

include("db/config.php");
include("abstract_helpers.php");

$msg = "";
$edit_mode = false;
$edit_id = null;
$edit_name = '';
$edit_role = '';
$edit_stock = 0;


if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $db->prepare("DELETE FROM gifts WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $msg = "<div class='alert alert-success alert-dismissible fade show'><i class='feather icon-check-circle'></i> Gift deleted successfully! <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show'><i class='feather icon-alert-circle'></i> Error deleting gift. <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
    }
    $stmt->close();
    // Redirect to clean URL
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $db->prepare("SELECT gift_name, target_role, total_stock FROM gifts WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $edit_mode = true;
        $edit_name = $row['gift_name'];
        $edit_role = $row['target_role'];
        $edit_stock = $row['total_stock'];
    }
    $stmt->close();
}

// ==========================================
// 3. HANDLE FORM SUBMIT (Add or Update)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $gift_name = trim($_POST['gift_name']);
    $target_role = $_POST['target_role'];
    $stock = (int)$_POST['stock'];

    if ($edit_mode) {
        // UPDATE
        $stmt = $db->prepare("UPDATE gifts SET gift_name = ?, target_role = ?, total_stock = ? WHERE id = ?");
        $stmt->bind_param("ssii", $gift_name, $target_role, $stock, $edit_id);
        $action_msg = "updated";
    } else {
        // INSERT
        $stmt = $db->prepare("INSERT INTO gifts (gift_name, target_role, total_stock) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $gift_name, $target_role, $stock);
        $action_msg = "added";
    }

    if ($stmt->execute()) {
        $msg = "<div class='alert alert-success alert-dismissible fade show'><i class='feather icon-check-circle'></i> Gift successfully $action_msg! <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
        $stmt->close();
        
        // Agar update successful tha, toh edit mode se bahar nikal jao
        if ($edit_mode) {
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show'><i class='feather icon-alert-circle'></i> Error: " . $db->error . " <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
        $stmt->close();
    }
}

// ==========================================
// 4. FETCH ALL GIFTS FOR TABLE
// ==========================================
$all_gifts = $db->query("SELECT * FROM gifts ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Gifts Rules</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php echo abstract_swal_css(); ?>
    <style>
        .red-text { color: red; }
        .table-card-header { background: #f8f9fa; border-bottom: 1px solid #eaeaea; padding: 15px 20px; }
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
                                <h5 class="m-b-10">Manage Gift Rules</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- FORM CARD (Add / Edit) -->
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><?php echo $edit_mode ? '<i class="feather icon-edit"></i> Edit Gift Rule' : '<i class="feather icon-plus"></i> Add New Gift Rule'; ?></h5>
                        </div>
                        <div class="card-block">
                            <?php echo $msg; ?>

                            <form method="post" action="" autocomplete="off">
                                <div class="row">
                                    <!-- Gift Name -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="gift_name" class="form-label">Gift Name <span class="red-text">*</span></label>
                                            <input type="text" id="gift_name" name="gift_name" class="form-control" placeholder="e.g., Premium Diary & Pen" value="<?php echo htmlspecialchars($edit_name); ?>" required>
                                        </div>
                                    </div>

                                    <!-- Target Role -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="target_role" class="form-label">Who gets this gift? <span class="red-text">*</span></label>
                                            <select id="target_role" name="target_role" class="form-control" required>
                                                <option value="" disabled>Choose Role</option>
                                                <option value="all" <?php echo ($edit_role == 'all') ? 'selected' : ''; ?>>Everyone (All Delegates)</option>
                                                <option value="faculty" <?php echo ($edit_role == 'faculty') ? 'selected' : ''; ?>>Only Faculty / Speakers</option>
                                                <option value="delegate" <?php echo ($edit_role == 'delegate') ? 'selected' : ''; ?>>Only Delegates / Doctors</option>
                                                <option value="student" <?php echo ($edit_role == 'student') ? 'selected' : ''; ?>>Only Students</option>
                                                <option value="sponsor" <?php echo ($edit_role == 'sponsor') ? 'selected' : ''; ?>>Only Sponsors</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Total Stock -->
                                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="stock" class="form-label">Total Stock (Quantity) <span class="red-text">*</span></label>
                                            <input type="number" id="stock" name="stock" class="form-control" placeholder="e.g., 500" min="1" value="<?php echo htmlspecialchars($edit_stock); ?>" required>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-3">
                                        <button type="submit" class="btn btn-primary" name="submit">
                                            <i class="feather icon-save"></i>&nbsp; <?php echo $edit_mode ? 'Update Gift' : 'Add Gift to System'; ?>
                                        </button>
                                        
                                        <?php if ($edit_mode): ?>
                                            <a href="admin_add_gift.php" class="btn btn-secondary">
                                                <i class="feather icon-x"></i>&nbsp; Cancel Edit
                                          </a>
                                        <?php else: ?>
                                            <a href="distribute_gifts.php" class="btn btn-success ml-2">
                                                <i class="feather icon-package"></i>&nbsp; Go to Distribution Desk
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- DATA TABLE CARD (List of Gifts) -->
                <div class="col-sm-12 mt-4">
                    <div class="card">
                        <div class="card-header table-card-header">
                            <h5><i class="feather icon-list"></i> Existing Gift Rules</h5>
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table id="giftTable" class="table table-striped table-bordered nowrap">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Gift Name</th>
                                            <th>Target Role</th>
                                            <th>Total Stock</th>
                                            <th>Created At</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($all_gifts && $all_gifts->num_rows > 0): ?>
                                            <?php while ($row = $all_gifts->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['gift_name']); ?></td>
                                                    <td>
                                                        <span class="badge badge-light-primary text-uppercase">
                                                            <?php echo htmlspecialchars($row['target_role']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $row['total_stock']; ?></td>
                                                    <td><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                                                    <td class="text-center">
                                                        <a href="?edit_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="feather icon-edit"></i>
                                                        </a>
                                                        <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger delete-btn" title="Delete" data-name="<?php echo htmlspecialchars($row['gift_name']); ?>">
                                                            <i class="feather icon-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No gifts added yet.</td>
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
            // Initialize DataTable
            $('#giftTable').DataTable();

            // SweetAlert Delete Confirmation
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                var giftName = $(this).data('name');
                
                // Using standard SweetAlert2 syntax (adjust if your abstract_swal uses v1)
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You are about to delete the gift: '" + giftName + "'",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = url;
                        }
                    });
                } else {
                    // Fallback to standard JS confirm if Swal is not loaded
                    if (confirm("Are you sure you want to delete '" + giftName + "'?")) {
                        window.location.href = url;
                    }
                }
            });
        });
    </script>
</body>
</html