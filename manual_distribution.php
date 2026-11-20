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

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $gift_id = (int)$_POST['gift_id'];
    $quantity = (int)$_POST['quantity'];
    $notes = trim($_POST['notes']); // e.g., "Given to Chief Organizer"

    if ($quantity > 0 && $gift_id > 0) {
        // Check if enough stock is available (Optional but recommended)
        $stock_check = $db->query("SELECT total_stock FROM gifts WHERE id = $gift_id")->fetch_assoc()['total_stock'];
        $used = $db->query("SELECT COUNT(*) as c FROM gift_distributions WHERE gift_id = $gift_id")->fetch_assoc()['c'];
        $remaining = $stock_check - $used;

        if ($quantity <= $remaining) {
            // Insert multiple rows based on quantity
            $stmt = $db->prepare("INSERT INTO gift_distributions (gift_id, student_id, notes, distributed_at) VALUES (?, NULL, ?, NOW())");
            
            $db->begin_transaction();
            try {
                for ($i = 0; $i < $quantity; $i++) {
                    $stmt->bind_param("is", $gift_id, $notes);
                    $stmt->execute();
                }
                $db->commit();
                $success_msg = "✅ Successfully distributed $quantity kit(s) to Staff/Organizer!";
            } catch (Exception $e) {
                $db->rollback();
                $error_msg = "❌ Error: " . $e->getMessage();
            }
            $stmt->close();
        } else {
            $error_msg = "❌ Not enough stock! Only $remaining remaining.";
        }
    } else {
        $error_msg = "❌ Invalid quantity or gift selected.";
    }
}

// Fetch Gifts for Dropdown
$gifts_list = $db->query("SELECT id, gift_name, total_stock FROM gifts ORDER BY gift_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manual Staff Distribution</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" href="assets/css/style.css">
    <?php echo abstract_swal_css(); ?>
</head>
<body>
    <div class="loader-bg"><div class="loader-track"><div class="loader-fill"></div></div></div>
    <?php include("header.php"); ?>
    <?php include("navbar.php"); ?>

    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Manual Distribution (Staff / Organizers)</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="feather icon-users"></i> Give Gifts to Internal Team</h5>
                        </div>
                        <div class="card-block">
                            <?php if($success_msg) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
                            <?php if($error_msg) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

                            <form method="post" action="" autocomplete="off">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Select Gift <span class="text-danger">*</span></label>
                                            <select name="gift_id" class="form-control" required>
                                                <option value="">-- Choose Gift --</option>
                                                <?php while($g = $gifts_list->fetch_assoc()): ?>
                                                    <option value="<?php echo $g['id']; ?>">
                                                        <?php echo htmlspecialchars($g['gift_name']); ?> 
                                                        (Stock: <?php echo $g['total_stock']; ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Quantity <span class="text-danger">*</span></label>
                                            <input type="number" name="quantity" class="form-control" min="1" max="100" placeholder="e.g., 10" required>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Recipient / Reason (Notes)</label>
                                            <input type="text" name="notes" class="form-control" placeholder="e.g., Given to Chief Organizer Mr. Sharma" required>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mt-3">
                                        <button type="submit" name="submit" class="btn btn-primary">
                                            <i class="feather icon-check-circle"></i> Deduct Stock & Record
                                        </button>
                                        <a href="gift_master_checklist.php" class="btn btn-secondary ml-2">Back to Reports</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
</body>
</html>