<?php
session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

include("db/config.php");
include("abstract_helpers.php");

$student_data = null;
$is_payment_pending = null;
$student_role = 'delegate'; //  default set (PHP notice fix)
$eligible_gifts = [];
$error_msg = "";

if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $input = trim($_GET['student_id']);

    // LIKE pattern ke liye % aur _ escape karo (taaki user ka % galat match na kare)
    $escaped = str_replace(['%', '_'], ['\%', '\_'], $input);
    $like    = '%' . $escaped . '%';

    // ✅ MULTI-FIELD SMART SEARCH
    // Number (student_id / contact_number) | delegate_id | First Name | Last Name | Full Name
    $stmt = $db->prepare("SELECT student_id, first_name, contact_number, last_name, role, specialty, hospital, delegate_id, payment_status 
                          FROM registered_users 
                          WHERE student_id = ? 
                             OR delegate_id = ? 
                             OR contact_number = ? 
                             OR first_name LIKE ? 
                             OR last_name LIKE ? 
                             OR CONCAT(first_name, ' ', last_name) LIKE ?
                          ORDER BY student_id ASC
                          LIMIT 1");
    // 6 values: student_id, delegate_id, contact_number, first_name LIKE, last_name LIKE, full_name LIKE
    $stmt->bind_param("ssssss", $input, $input, $input, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();
    $stmt->close();

    if ($student_data) {

        // ✅ Payment Check Flag
        $is_payment_pending = ($student_data['payment_status'] !== 'paid');

        $student_role = strtolower(trim($student_data['role'] ?? 'delegate'));

        // ✅ Gifts + Distributed Count (ek hi query mein)
        $gifts_result = $db->query("
            SELECT g.*, 
                   (SELECT COUNT(*) FROM gift_distributions d WHERE d.gift_id = g.id) AS distributed_count
            FROM gifts g
        ");

        while ($gift = $gifts_result->fetch_assoc()) {
            $gift_role = strtolower($gift['target_role']);
            $is_eligible = ($gift_role === 'all' || $gift_role === $student_role);

            $check_stmt = $db->prepare("SELECT id FROM gift_distributions WHERE gift_id = ? AND student_id = ?");
            $check_stmt->bind_param("ii", $gift['id'], $student_data['student_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $already_taken = $check_result->fetch_assoc();
            $check_stmt->close();

            $eligible_gifts[] = [
                'gift' => $gift,
                'eligible' => $is_eligible,
                'taken' => ($already_taken !== null)
            ];
        }

        // ✅ Overall Stats
        $stats = $db->query("
            SELECT 
                COALESCE(SUM(g.total_stock), 0) AS total_stock,
                (SELECT COUNT(*) FROM gift_distributions) AS total_distributed
            FROM gifts g
        ")->fetch_assoc();
        $stats['total_remaining'] = $stats['total_stock'] - $stats['total_distributed'];
    } else {
        $error_msg = "❌ '$input' not exists! Try with accurate one";
    }
}

// Safe percentage calculation
$dist_percent = (isset($stats['total_stock']) && $stats['total_stock'] > 0) ? round(($stats['total_distributed'] / $stats['total_stock']) * 100) : 0;
$rem_percent  = (isset($stats['total_stock']) && $stats['total_stock'] > 0) ? round(($stats['total_remaining'] / $stats['total_stock']) * 100) : 0;
//  Overall Stats (summary ke liye)
$stats = $db->query("
    SELECT 
        COALESCE(SUM(g.total_stock), 0) AS total_stock,
        (SELECT COUNT(*) FROM gift_distributions) AS total_distributed
    FROM gifts g
")->fetch_assoc();
$stats['total_remaining'] = $stats['total_stock'] - $stats['total_distributed'];


// Safe percentage calculation (Division by zero se bachne ke liye)
$dist_percent = $stats['total_stock'] > 0 ? round(($stats['total_distributed'] / $stats['total_stock']) * 100) : 0;
$rem_percent  = $stats['total_stock'] > 0 ? round(($stats['total_remaining'] / $stats['total_stock']) * 100) : 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Gift Distribution Desk</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- HTML5 QR Code Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <?php echo abstract_swal_css(); ?>

    <style>
        /* ===== Theme-aligned custom styles ===== */
        .red-text {
            color: red;
        }

        .scanner-wrap {
            background: linear-gradient(135deg, #1e5799 0%, #2989d8 100%);
            color: #fff;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .scanner-wrap h4 {
            color: #fff;
            margin-bottom: 5px;
        }

        .scanner-wrap p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 13px;
        }

        .scanner-wrap .form-control {
            max-width: 420px;
            margin: 12px auto 0;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 1px;
            border: 2px solid #fff;
            border-radius: 8px;
            height: 50px;
        }

        .scanner-wrap .btn-reset {
            margin-top: 10px;
        }

        .student-card {
            background: #eef4fb;
            border-left: 5px solid #2989d8;
            border-radius: 8px;
            padding: 18px 22px;
            margin-bottom: 22px;
        }

        .student-card h4 {
            margin-bottom: 8px;
            color: #1e3c72;
        }

        .student-card .meta {
            font-size: 13px;
            color: #555;
            margin: 3px 0;
        }

        .student-card .meta i {
            color: #2989d8;
        }

        .gift-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 18px;
        }

        .gift-card {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .gift-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .gift-card h5 {
            font-size: 15px;
            margin-bottom: 6px;
            color: #333;
        }

        .gift-card .rule-tag {
            font-size: 11px;
            color: #888;
            margin-bottom: 12px;
        }

        .gift-card.not-eligible {
            opacity: 0.55;
            background: #f7f7f7;
        }

        .btn-give {
            display: inline-block;
            background: #28a745;
            color: #fff;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
        }

        .btn-give:hover {
            background: #218838;
            color: #fff;
            text-decoration: none;
        }

        .btn-taken {
            display: inline-block;
            background: #6c757d;
            color: #fff;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            cursor: not-allowed;
        }

        .badge-pay {
            font-size: 11px;
        }

        /* ===== Expressive Stat Cards ===== */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f1f1;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-content {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .stat-icon i {
            color: #fff;
            font-size: 24px;
        }

        /* Icon Background Colors */
        .bg-primary {
            background: linear-gradient(135deg, #1e5799 0%, #2989d8 100%);
        }

        .bg-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .bg-warning {
            background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%);
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
            line-height: 1;
        }

        .stat-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 5px 0 0 0;
            font-weight: 600;
        }

        .stat-footer {
            margin-top: 10px;
        }

        .stat-footer .progress {
            background: #f0f0f0;
            border-radius: 2px;
        }

        .btn-pending {
            background: #ff9800;
            /* Orange color for warning */
            cursor: not-allowed;
        }

        .btn-pending:hover {
            background: #f57c00;
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
                                <h5 class="m-b-10">Gift Distribution Desk</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="feather icon-package"></i> Scan Badge &amp; Distribute Gifts</h5>
                        </div>
                        <div class="card-block">

                            <!-- ===== HARDWARE SCANNER BOX ===== -->
                            <div class="scanner-wrap">
                                <h4><i class="feather icon-scan"></i> Gift Distribution Desk</h4>
                                <p>Scan Badge QR / Type <b>ID</b>, <b>Name</b>, or <b>Phone</b> & Press Enter</p>

                                <form method="GET" id="scanForm" action="">
                                    <input type="text" name="student_id" id="scanner_input" class="form-control"
                                        autocomplete="off"
                                        placeholder="Click here and Scan..."
                                        value="<?php echo htmlspecialchars($_GET['student_id'] ?? ''); ?>">

                                    <div class="mt-3">
                                        <button type="button" class="btn btn-light btn-sm" id="resetBtn">
                                            <i class="feather icon-refresh-cw"></i> Clear & Scan Next
                                        </button>
                                    </div>
                                </form>

                                <?php if ($error_msg): ?>
                                    <div class="alert alert-danger mt-3 mb-0">
                                        <i class="feather icon-alert-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- ===== EXPRESSIVE STATS SUMMARY ===== -->
                            <div class="row stats-row mb-4">
                                <!-- Total Stock Card -->
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <div class="stat-card stat-total">
                                        <div class="stat-content">
                                            <div class="stat-icon bg-primary">
                                                <i class="feather icon-package"></i>
                                            </div>
                                            <div class="stat-info">
                                                <h3 class="stat-value"><?php echo number_format($stats['total_stock']); ?></h3>
                                                <p class="stat-label">Total Stock</p>
                                            </div>
                                        </div>
                                        <div class="stat-footer">
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar bg-primary" style="width: 100%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Distributed Card -->
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <div class="stat-card stat-given">
                                        <div class="stat-content">
                                            <div class="stat-icon bg-success">
                                                <i class="feather icon-check-circle"></i>
                                            </div>
                                            <div class="stat-info">
                                                <h3 class="stat-value"><?php echo number_format($stats['total_distributed']); ?></h3>
                                                <p class="stat-label">Distributed (<?php echo $dist_percent; ?>%)</p>
                                            </div>
                                        </div>
                                        <div class="stat-footer">
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $dist_percent; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Remaining Card -->
                                <div class="col-md-4 col-sm-12 mb-3">
                                    <div class="stat-card stat-remain">
                                        <div class="stat-content">
                                            <div class="stat-icon bg-warning">
                                                <i class="feather icon-clock"></i>
                                            </div>
                                            <div class="stat-info">
                                                <h3 class="stat-value"><?php echo number_format(max(0, $stats['total_remaining'])); ?></h3>
                                                <p class="stat-label">Remaining (<?php echo $rem_percent; ?>%)</p>
                                            </div>
                                        </div>
                                        <div class="stat-footer">
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar bg-warning" style="width: <?php echo $rem_percent; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== STUDENT INFO ===== -->
                            <?php if ($student_data): ?>
                                <div class="student-card">
                                    <h4><i class="feather icon-user"></i>
                                        <?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?>
                                    </h4>
                                    <p class="meta"><i class="feather icon-id-card"></i>
                                        <strong>Delegate ID:</strong> <?php echo htmlspecialchars($student_data['delegate_id']); ?>
                                        &nbsp;|&nbsp;
                                        <strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($student_role)); ?>
                                        &nbsp;|&nbsp;
                                        <span class="badge badge-pay <?php echo ($student_data['payment_status'] === 'paid') ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo strtoupper($student_data['payment_status']); ?>
                                        </span>
                                    </p>

                                </div>

                                <h6 class="mb-3"><i class="feather icon-gift"></i> Available Gifts for this Delegate</h6>
                                <div class="gift-list">
                                    <?php foreach ($eligible_gifts as $item): $gift = $item['gift']; ?>
                                        <div class="gift-card <?php echo (!$item['eligible']) ? 'not-eligible' : ''; ?>">
                                            <h5><?php echo htmlspecialchars($gift['gift_name']); ?></h5>
                                            <p class="rule-tag">Rule: For <?php echo ucfirst($gift['target_role']); ?></p>

                                            <?php if ($item['taken']): ?>
                                                <span class="btn-taken"><i class="feather icon-check-circle"></i> Already Received</span>

                                            <?php elseif ($item['eligible']): ?>
                                                <?php if ($is_payment_pending): ?>
                                                    <!-- Agar payment pending hai toh ye dikhega -->
                                                    <span class="btn-taken btn-pending">
                                                        <i class="feather icon-alert-circle"></i> Payment Pending
                                                    </span>
                                                <?php else: ?>
                                                    <!-- Agar payment paid hai toh normal button dikhega -->
                                                    <a href="give_gift.php?gift_id=<?php echo $gift['id']; ?>&student_id=<?php echo $student_data['student_id']; ?>" class="btn-give">
                                                        <i class="feather icon-gift"></i> Give Gift
                                                    </a>
                                                <?php endif; ?>

                                            <?php else: ?>
                                                <span class="btn-taken"><i class="feather icon-x-circle"></i> Not Eligible</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <br>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <?php echo abstract_swal_js(); ?>

    <script>
        $(document).ready(function() {
            const $input = $('#scanner_input');
            const $form = $('#scanForm');

            let lastKeyTime = 0;
            let scanTimeout = null;

            // 1. Page load hote hi input pe focus lao
            $input.focus();

            // 2. Agar staff galti se bahar click kar de, focus wapas input pe lao
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#resetBtn, #scanner_input').length) {
                    $input.focus();
                }
            });

            // 3. MAGIC LOGIC: Scanner Speed Detection
            $input.on('keypress', function(e) {
                let now = Date.now();
                let timeDiff = now - lastKeyTime; // Do keys ke beech ka time
                lastKeyTime = now;

                clearTimeout(scanTimeout);

                // Agar time 50ms se kam hai = SCANNER (Insaan itni tez type nahi kar sakta)
                if (timeDiff < 50 && timeDiff > 0) {

                    // Scanner ne type karna band kar diya toh 150ms baad auto-submit karo
                    scanTimeout = setTimeout(function() {
                        let val = $input.val().trim();
                        if (val.length > 2) { // Chhote accidental scans ko ignore karo
                            $input.css('opacity', '0.5'); // Visual feedback
                            $form.submit();
                        }
                    }, 150);
                }
                // Agar time 50ms se zyada hai = HUMAN TYPING (Toh auto-submit mat karo, Enter ka wait karo)
            });

            // 4. Fallback: Agar koi manually type kar raha hai, toh Enter dabane par submit ho
            $input.on('keydown', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $form.submit();
                }
            });

            // 5. Reset Button Logic
            $('#resetBtn').on('click', function(e) {
                e.preventDefault();
                $input.val('');
                $input.css('opacity', '1');
                window.location.href = window.location.pathname;
            });
        });
    </script>
</body>

</html>