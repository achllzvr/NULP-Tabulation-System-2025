<?php
// Run this script once to create the tie_groups table
require_once('../classes/database.php');
$con = new database();
$conn = $con->opencon();
$sql = file_get_contents(__DIR__ . '/sql/tie_groups.sql');
if ($conn->multi_query($sql)) {
    do {
        // flush multi_query results
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "tie_groups table created/verified successfully.";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
