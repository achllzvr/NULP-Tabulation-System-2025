<?php
// Helper to fetch advancements for a round
function get_advancements_for_round($conn, $round_id) {
    // Use to_round_id per schema
    $stmt = $conn->prepare("SELECT * FROM advancements WHERE to_round_id = ?");
    $stmt->bind_param("i", $round_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $advancements = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $advancements;
}
