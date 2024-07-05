<?php
include '../config.php';

// Create connection to MySQL server
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$term = isset($_GET['term']) ? $_GET['term'] : '';
$result = array();

if (!empty($term)) {
    $search_sql = "SELECT supplier_name, supplier_contact FROM supplier WHERE supplier_name LIKE ? OR supplier_contact LIKE ? LIMIT 10";
    $stmt = mysqli_prepare($conn, $search_sql);
    $search_param = "%$term%";
    mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $query_result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($query_result)) {
        $result[] = array(
            'label' => $row['supplier_name'] . ' (' . $row['supplier_contact'] . ')',
            'value' => $row['supplier_name'] // Only return the supplier name as the value
        );
    }
    
    mysqli_stmt_close($stmt);
}

// Close connection
mysqli_close($conn);

// Return the result as JSON
echo json_encode($result);
?>
