<?php
session_start();
include("db/config.php");
// echo '<pre>';
// print_r($_SESSION);
// exit;
if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit();
}

$admin_id = $_SESSION['login_user_id'];
$role_id = $_SESSION['admin_role'] ?? 0;
$employee_id = $_SESSION['employee_id'] ?? null;
$role_name = '';
$employee_data = null;
// echo $employee_id;
// exit;
// Fetch role name
$role_stmt = $db->prepare("SELECT role_name FROM roles WHERE role_id = ?");
$role_stmt->bind_param("i", $role_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();

if ($role_row = $role_result->fetch_assoc()) {
    $role_name = strtolower($role_row['role_name']);
}

?>

<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <title>Control Management System</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />

    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .birthday-list {
            max-height: 100px;
            overflow-y: auto;
            /* keep scroll functionality */
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE and Edge */
        }

        .birthday-list::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }

        .welcome-block {
            display: flex;
            
            align-items: center;
            gap: 16px;
        }

        .welcome-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #022b55;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 51, 153, 0.25);
        }

        .welcome-title {
            color: #fff;
            font-weight: 600;
            margin: 0;
        }

        .welcome-sub {
            color: #e4e7e9;
            font-size: 14px;
        }

        .clock-widget {
            display: inline-block;
            background: #fff;
            border: 1px solid #eaeaea;
            border-radius: 12px;
            padding: 10px 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .clock-time {
            font-size: 26px;
            font-weight: 600;
            color: #003399;
            letter-spacing: 1px;
            line-height: 1.1;
        }

        .clock-time i {
            font-size: 20px;
            margin-right: 4px;
        }

        .clock-time small {
            font-size: 13px;
            font-weight: 600;
        }

        .clock-date {
            font-size: 13px;
            color: #6c757d;
            margin-top: 4px;
        }

        @media (max-width: 767.98px) {
            .welcome-block {
                justify-content: center;
                text-align: center;
                flex-direction: column;
            }
        }
    </style>

</head>

<body class="">

    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <!-- Header -->
    <?php
    include("header.php");
    ?>
    <!-- /Header -->

    <!-- navbar -->
    <?php
    include("navbar.php");
    ?>
    <!-- /navbar -->


    <section class="pcoded-main-container">
      <h1>Hello</h1>
    </section>

    <!-- Event Modal -->
    <!-- Event Tooltip Overlay -->
    <div id="tooltipOverlay" class="tooltip-overlay" onclick="closeEventTooltip()"></div>

    <!-- Event Tooltip -->
    <div id="eventTooltip" class="event-tooltip">
        <div class="tooltip-header">
            <h3 id="tooltipDate"></h3>
            <button class="tooltip-close" onclick="closeEventTooltip()">&times;</button>
        </div>
        <div id="tooltipBody"></div>
    </div>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <!--<script src="assets/js/menu-setting.min.js"></script>-->

    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap4.min.js"></script>
    <script src="assets/js/plugins/buttons.colVis.min.js"></script>
    <script src="assets/js/plugins/buttons.print.min.js"></script>
    <script src="assets/js/plugins/pdfmake.min.js"></script>
    <script src="assets/js/plugins/vfs_fonts.js"></script>
    <script src="assets/js/plugins/jszip.min.js"></script>
    <script src="assets/js/plugins/dataTables.buttons.min.js"></script>
    <script src="assets/js/plugins/buttons.html5.min.js"></script>
    <script src="assets/js/plugins/buttons.bootstrap4.min.js"></script>
    <script src="assets/js/pages/data-export-custom.js"></script>
</body>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new").load("loadpost.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#total").load("loadnewleads.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#user").load("load-category.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new1").load("loadpage.php");

        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new2").load("loadadmin.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new3").load("totaldownloads.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new4").load("loadleads.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new5").load("loadclients.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        $("#logged").delay(5000).slideUp(300);
    });
</script>

<script>
    function display_ct6() {
        var x = new Date();
        var ampm = x.getHours() >= 12 ? 'PM' : 'AM';
        var hours = x.getHours() % 12;
        hours = hours ? hours : 12;
        hours = String(hours).padStart(2, '0');
        var minutes = String(x.getMinutes()).padStart(2, '0');
        var seconds = String(x.getSeconds()).padStart(2, '0');

        var timeEl = document.getElementById('ct-time');
        var ampmEl = document.getElementById('ct-ampm');
        var dateEl = document.getElementById('ct-date');

        if (timeEl) timeEl.innerHTML = hours + ':' + minutes + ':' + seconds;
        if (ampmEl) ampmEl.innerHTML = ampm;

        if (dateEl) {
            var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            dateEl.innerHTML = days[x.getDay()] + ', ' + String(x.getDate()).padStart(2, '0') + ' ' + months[x.getMonth()] + ' ' + x.getFullYear();
        }

        display_c6();
    }

    function display_c6() {
        var refresh = 1000; // Refresh rate in milli seconds
        mytime = setTimeout('display_ct6()', refresh)
    }
    display_c6()
</script>


<script>
    function showEventModal(events, date) {
        const tooltip = document.getElementById('eventTooltip');
        const overlay = document.getElementById('tooltipOverlay');
        const tooltipDate = document.getElementById('tooltipDate');
        const tooltipBody = document.getElementById('tooltipBody');

        tooltipDate.textContent = date;

        let html = '';
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        events.forEach(event => {
            const eventDate = new Date(event.event_date);
            eventDate.setHours(0, 0, 0, 0);

            const isExpired = eventDate < today;
            const statusText = isExpired ? 'Expired' : 'Upcoming';
            const statusClass = isExpired ? 'expired' : 'upcoming';

            html += `
                    <div class="event-details">
                        <div class="event-detail-title">${escapeHtml(event.title)}</div>
                        ${event.description ? `<div class="event-detail-row"><strong>Description:</strong> ${escapeHtml(event.description)}</div>` : ''}
                        ${event.type ? `<div class="event-detail-row"><strong>Type:</strong> ${escapeHtml(event.type)}</div>` : ''}
                        ${event.location ? `<div class="event-detail-row"><strong>Location:</strong> ${escapeHtml(event.location)}</div>` : ''}
                        
                        <span class="event-badge ${statusClass}">${statusText}</span>
                       
                    </div>
                `;
        });

        tooltipBody.innerHTML = html;

        // Position tooltip in center
        overlay.classList.add('show');
        tooltip.classList.add('show');

        // Center the tooltip
        setTimeout(() => {
            const rect = tooltip.getBoundingClientRect();
            tooltip.style.left = `${(window.innerWidth - rect.width) / 2}px`;
            tooltip.style.top = `${Math.max(50, (window.innerHeight - rect.height) / 2)}px`;
        }, 10);
    }

    function closeEventTooltip() {
        const tooltip = document.getElementById('eventTooltip');
        const overlay = document.getElementById('tooltipOverlay');
        tooltip.classList.remove('show');
        overlay.classList.remove('show');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Close tooltip with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEventTooltip();
        }
    });
</script>

<script>
    // AJAX month navigation
    $(document).on('click', '.nav-btn', function(e) {
        e.preventDefault();
        const month = $(this).data('month');
        const year = $(this).data('year');

        $('#calendar-container').html('<div class="loading">Loading...</div>');

        $.ajax({
            url: 'calendar-section.php',
            type: 'GET',
            data: {
                month: month,
                year: year
            },
            success: function(response) {
                $('#calendar-container').html(response);
            },
            error: function() {
                $('#calendar-container').html('<div class="loading">Error loading calendar.</div>');
            }
        });
    });
</script>

</html>