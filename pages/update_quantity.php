<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $change = isset($_POST['change']) ? intval($_POST['change']) : 0;

    if ($product_id > 0 && $change !== 0) {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

        if ($conn->connect_error) {
            echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE product SET quantity = quantity + ? WHERE id = ?");
        $stmt->bind_param("ii", $change, $product_id);

        if ($stmt->execute()) {
            $new_quantity_stmt = $conn->prepare("SELECT quantity FROM product WHERE id = ?");
            $new_quantity_stmt->bind_param("i", $product_id);
            $new_quantity_stmt->execute();
            $new_quantity_stmt->bind_result($new_quantity);
            $new_quantity_stmt->fetch();

            echo json_encode(['success' => true, 'newQuantity' => $new_quantity]);
            
            $new_quantity_stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update quantity.']);
        }

        $stmt->close();
        $conn->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid product ID or change value.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>
