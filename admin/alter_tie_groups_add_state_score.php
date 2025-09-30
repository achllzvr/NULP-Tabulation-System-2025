<?php
// Run this script once to alter the tie_groups table to add state and score columns
require_once('../classes/database.php');
$con = new database();
$conn = $con->opencon();
$sql = file_get_contents(__DIR__ . '/sql/alter_tie_groups_add_state_score.sql');
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "tie_groups table altered: state and score columns added.";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
