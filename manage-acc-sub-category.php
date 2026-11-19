<?php
session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit();
}
$name = $_SESSION['login_user'];
include("db/config.php");

// Fetch Subcategories with Parent Category Name using JOIN
$query = "SELECT s.id, s.category_id, c.category_name AS parent_category, s.subcategory_name, s.duration_days, s.status 
          FROM accommodation_subcategories s 
          LEFT JOIN accommodation_categories c ON s.category_id = c.id 
          ORDER BY s.id DESC";
$result = mysqli_query($db, $query);

// Fetch Active Categories for Dropdowns
$cat_query = "SELECT id, category_name FROM accommodation_categories WHERE status = 1";
$cat_result = mysqli_query($db, $cat_query);

// Fetch Settings
$settings_query = "SELECT * FROM login_settings";
$settingsResult = mysqli_query($db, $settings_query);
$settings = mysqli_fetch_assoc($settingsResult);

$logoPath = $settings['backend_panel_logo'] ?? '';
$helpdeskNumber = $settings['helpdesk_no'] ?? '';
$favicon = $settings['favicon'] ?? 'assets/images/favicon.png';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage Accommodation Sub Categories</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="">

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
                                <h5 class="m-b-10">Manage Accommodation Sub-category</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header d-flex justify-content-between align-items-center">
                            <h5>Sub-category List</h5>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class='feather icon-plus'></i> Add Sub-category
                            </button>
                        </div>
                        <div class="card-body">
                            <form id="deleteMForm" action="process-subcategory.php" method="post">
                                <input type="hidden" name="action" value="multi_delete">
                                <button type="button" id="deleteSelected" class="btn btn-danger mb-3">
                                    <i class='feather icon-trash'></i> Delete Selected
                                </button>

                                <div class="dt-responsive table-responsive">
                                    <?php
                                    if (isset($_SESSION['msg'])) {
                                        echo $_SESSION['msg'];
                                        unset($_SESSION['msg']);
                                    }
                                    if (isset($_GET['status'])) {
                                        $st = base64_decode($_GET['status']);
                                        if ($st > 0) {
                                            echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                                                    <strong><i class='feather icon-check'></i> Success!</strong> Operation completed successfully.
                                                    <button type='button' class='close' data-bs-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                                  </div>";
                                        } else {
                                            echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                                                    <strong><i class='feather icon-x'></i> Error!</strong> Operation failed.
                                                    <button type='button' class='close' data-bs-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                                  </div>";
                                        }
                                    }
                                    ?>

                                    <table id="basic-btn" class="table table-striped table-bordered nowrap">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" id="selectAll"></th>
                                                <th>S.NO</th>
                                                <th>PARENT CATEGORY</th>
                                                <th>SUB-CATEGORY NAME</th>
                                                <th>DURATION (DAYS)</th>
                                                <th>STATUS</th>
                                                <th>ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $count = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $encoded_id = base64_encode($row['id']);
                                                $status_badge = $row['status'] == 1 ? '<span class="badge bg-success">Enable</span>' : '<span class="badge bg-danger">Disable</span>';
                                            ?>
                                                <tr class='record'>
                                                    <td><input type='checkbox' name='item_ids[]' class="sub-checkbox" value='<?php echo $encoded_id; ?>'></td>
                                                    <td><?php echo $count++; ?></td>
                                                    <td><?php echo htmlspecialchars($row['parent_category']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['subcategory_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['duration_days']); ?> Days</td>
                                                    <td><?php echo $status_badge; ?></td>
                                                    <td>
                                                        <button type='button' class='btn btn-warning btn-sm edit-btn'
                                                            data-id='<?php echo $encoded_id; ?>'
                                                            data-catid='<?php echo $row['category_id']; ?>'
                                                            data-name='<?php echo htmlspecialchars($row['subcategory_name']); ?>'
                                                            data-duration='<?php echo $row['duration_days']; ?>'
                                                            data-status='<?php echo $row['status']; ?>'
                                                            data-bs-toggle='modal' data-bs-target='#editModal'>
                                                            <i class='feather icon-edit'></i>
                                                        </button>
                                                        <button type='button' class='btn btn-danger btn-sm delete-btn'
                                                            data-id='<?php echo $encoded_id; ?>' data-bs-toggle='modal' data-bs-target='#deleteModal'>
                                                            <i class='feather icon-trash'></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= MODALS ================= -->

    <!-- 1. ADD MODAL -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="process-subcategory.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Sub-category</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Parent Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php
                                mysqli_data_seek($cat_result, 0); // Reset pointer
                                while ($cat = mysqli_fetch_assoc($cat_result)) {
                                    echo "<option value='{$cat['id']}'>{$cat['category_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sub-category Name <span class="text-danger">*</span></label>
                            <input type="text" name="subcategory_name" class="form-control" placeholder="e.g., 2 Days" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration in Days <span class="text-danger">*</span></label>
                            <input type="number" name="duration_days" class="form-control" placeholder="e.g., 2" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="1">Enable</option>
                                <option value="0">Disable</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Sub-category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. EDIT MODAL -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="process-subcategory.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="encoded_id" id="edit_encoded_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Sub-category</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Parent Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="edit_category_id" class="form-control" required>
                                <?php
                                mysqli_data_seek($cat_result, 0);
                                while ($cat = mysqli_fetch_assoc($cat_result)) {
                                    echo "<option value='{$cat['id']}'>{$cat['category_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sub-category Name <span class="text-danger">*</span></label>
                            <input type="text" name="subcategory_name" id="edit_subcategory_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration in Days <span class="text-danger">*</span></label>
                            <input type="number" name="duration_days" id="edit_duration_days" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-control">
                                <option value="1">Enable</option>
                                <option value="0">Disable</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Update Sub-category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. SINGLE DELETE MODAL -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">Are you sure you want to delete this Sub-category?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. MULTI DELETE MODAL -->
    <div class="modal fade" id="deleteSelectedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Bulk Deletion</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">Are you sure you want to delete the selected sub-categories?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmMultiDelete" class="btn btn-danger">Delete Selected</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. ALERT MODAL (No Selection) -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">No Selection</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">Please select at least one sub-category to delete.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#basic-btn').DataTable();
            $(".alert").delay(5000).slideUp(300);

            // Select All Checkbox
            $('#selectAll').on('click', function() {
                $('.sub-checkbox').prop('checked', this.checked);
            });

            // Populate Edit Modal
            $('.edit-btn').on('click', function() {
                $('#edit_encoded_id').val($(this).data('id'));
                $('#edit_category_id').val($(this).data('catid'));
                $('#edit_subcategory_name').val($(this).data('name'));
                $('#edit_duration_days').val($(this).data('duration'));
                $('#edit_status').val($(this).data('status'));
            });

            // Single Delete Logic
            let deleteId = '';
            $('.delete-btn').on('click', function() {
                deleteId = $(this).data('id');
            });
            $('#confirmDelete').on('click', function() {
                if (deleteId) {
                    window.location.href = 'process-subcategory.php?action=delete&id=' + deleteId;
                }
            });

            // Multi Delete Logic
            $('#deleteSelected').on('click', function() {
                if ($('.sub-checkbox:checked').length === 0) {
                    new bootstrap.Modal(document.getElementById('alertModal')).show();
                } else {
                    new bootstrap.Modal(document.getElementById('deleteSelectedModal')).show();
                }
            });
            $('#confirmMultiDelete').on('click', function() {
                $('#deleteMForm').submit();
            });
        });
    </script>
</body>

</html>