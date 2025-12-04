<?php
include 'db_config.php';

echo "Checking lcs_master_notes table...\n";

$result = $conn->query("SHOW TABLES LIKE 'lcs_master_notes'");
if ($result->num_rows > 0) {
    echo "Table exists.\n";

    $col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
    if ($col && $col->num_rows > 0) {
        echo "status_date column exists.\n";
    } else {
        echo "status_date column does NOT exist.\n";
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM lcs_master_notes");
    $row = $result->fetch_assoc();
    echo "Number of records: " . $row['count'] . "\n";

    if ($row['count'] > 0) {
        $result = $conn->query("SELECT * FROM lcs_master_notes LIMIT 3");
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['id'] . ", LCS_ID: " . $row['lcs_id'] . ", Status_Date: " . $row['status_date'] . ", Note: " . substr($row['note'], 0, 50) . "...\n";
        }
    }
} else {
    echo "Table does NOT exist.\n";
}

$conn->close();
?>
