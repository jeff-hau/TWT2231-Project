<?php
include '../config.php';
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: ../index.php');
    exit;
}

// Create connection to MySQL server
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch product details
    $query = "SELECT * FROM product WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    if (!$product) {
        echo "Product not found.";
        exit;
    }

    // Fetch categories
    $category_sql = "SELECT id, category_name FROM medicine_category";
    $category_result = mysqli_query($conn, $category_sql);

    // Fetch types
    $type_sql = "SELECT id, type_name FROM medicine_type";
    $type_result = mysqli_query($conn, $type_sql);

    // Fetch suppliers
    $supplier_sql = "SELECT id, supplier_name FROM supplier";
    $supplier_result = mysqli_query($conn, $supplier_sql);
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $upc = $_POST['upc'];
    $category_id = $_POST['category_id'];
    $type_id = $_POST['type_id'];
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $manufacturing_date = $_POST['manufacturing_date'];
    $expired_date = $_POST['expired_date'];
    $measurement = $_POST['measurement'];
    $quantity = $_POST['quantity'];
    $supplier_id = $_POST['supplier_id'];
    $requires_prescription = isset($_POST['requires_prescription']) ? 1 : 0;

    // Update product details
    $update_sql = "UPDATE product SET upc=?, category_id=?, type_id=?, product_name=?, description=?, price=?, manufacturing_date=?, expired_date=?, measurement=?, quantity=?, supplier_id=?, requires_prescription=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "siissdsssiiii", $upc, $category_id, $type_id, $product_name, $description, $price, $manufacturing_date, $expired_date, $measurement, $quantity, $supplier_id, $requires_prescription, $id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    exit;
} else {
    echo "Invalid request.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../styles/sidebar.css">
    <style>
        .form_container {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            max-width: 2500px;
            margin: 0 auto;
        }
        .form_container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .form_content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .form_column {
            flex: 1;
            min-width: 300px;
        }
        .form_group {
            margin-bottom: 15px;
        }
        .form_group label {
            display: block;
            margin-bottom: 5px;
            color: black;
        }
        .form_group input,
        .form_group select,
        .form_group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .form_group textarea {
            resize: vertical;
            height: 100px;
        }
        .form_group input[type="checkbox"] {
            width: auto;
        }
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        .btn-group button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-update {
            background-color: #007bff;
            color: white;
        }
        .btn-update:hover {
            background-color: #0056b3;
        }
        .btn-cancel {
            background-color: #ddd;
        }
        .btn-cancel:hover {
            background-color: #bbb;
        }
        .inline-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 17px;
        }

        .inline-label {
            margin-right: 10px; 
        }

        input[type="checkbox"] {
            transform: scale(1.6); 
            margin: 0 10px 0 0; 
        }
    </style>
</head>
<body>

<div class="form_container">
    <h2>Edit Product</h2>
    <form id="edit-form">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
        <div class="form_content">
            <div class="form_column">
                <div class="form_group">
                    <label for="upc">UPC:</label>
                    <input type="text" name="upc" value="<?php echo htmlspecialchars($product['upc']); ?>" readonly>
                </div>
                <div class="form_group">
                    <label for="category_id">Category:</label>
                    <select name="category_id" required>
                        <?php while ($row = mysqli_fetch_assoc($category_result)) { ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($product['category_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['category_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form_group">
                    <label for="type_id">Type:</label>
                    <select name="type_id" required>
                        <?php while ($row = mysqli_fetch_assoc($type_result)) { ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($product['type_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['type_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form_group">
                    <label for="product_name">Product Name:</label>
                    <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                </div>
                <div class="form_group">
                    <label for="description">Description:</label>
                    <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                <div class="form_group">
                    <label for="price">Price:</label>
                    <input type="number" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>
            </div>
            <div class="form_column">
                <div class="form_group">
                    <label for="manufacturing_date">Manufacturing Date:</label>
                    <input type="date" name="manufacturing_date" value="<?php echo htmlspecialchars($product['manufacturing_date']); ?>" required>
                </div>
                <div class="form_group">
                    <label for="expired_date">Expired Date:</label>
                    <input type="date" name="expired_date" value="<?php echo htmlspecialchars($product['expired_date']); ?>" required>
                </div>
                <div class="form_group">
                    <label for="measurement">Measurement:</label>
                    <input type="text" name="measurement" value="<?php echo htmlspecialchars($product['measurement']); ?>" required>
                </div>
                <div class="form_group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" name="quantity" value="<?php echo htmlspecialchars($product['quantity']); ?>" required>
                </div>
                <div class="form_group">
                    <label for="supplier_id">Supplier:</label>
                    <select name="supplier_id" required>
                        <?php while ($row = mysqli_fetch_assoc($supplier_result)) { ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($product['supplier_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['supplier_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form_group inline-group">
                    <label for="requires_prescription" class="inline-label">Requires Prescription:</label>
                    <input type="checkbox" name="requires_prescription" id="requires_prescription" value="1" <?php if ($product['requires_prescription'] == 1) echo 'checked'; ?>>
                </div>
            </div>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn-update">Update</button>
            <button type="button" class="btn-cancel" onclick="closeEditPopup()">Cancel</button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#edit-form').on('submit', function(event) {
        event.preventDefault(); // Prevent default form submission

        $.ajax({
            url: 'edit_product.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Product updated successfully.');
                    closeEditPopup();
                    location.reload(); // Reload the page to reflect changes
                } else {
                    alert('Error updating product: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
                alert("Failed to update product. Please try again.");
            }
        });
    });
});

function closeEditPopup() {
    $('#edit-popup').hide();
}
</script>
</body>
</html>
