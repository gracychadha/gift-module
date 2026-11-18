<?php
require_once 'db/config.php';

if (isset($_GET['gift_id']) && isset($_GET['student_id'])) {
    $gift_id = (int)$_GET['gift_id'];
    $student_id = (int)$_GET['student_id'];

    // Double check: pehle se toh nahi diya?
    $check_stmt = $db->prepare("SELECT id FROM gift_distributions WHERE gift_id = ? AND student_id = ?");
    $check_stmt->bind_param("ii", $gift_id, $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if (!$check_result->fetch_assoc()) {
        // Entry karo!
        $insert_stmt = $db->prepare("INSERT INTO gift_distributions (gift_id, student_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $gift_id, $student_id);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// Wapas distribution page pe bhej do
header("Location: distribute_gifts.php?student_id=" . $student_id);
exit;
?>