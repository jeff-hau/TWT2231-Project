<?php
include '../config.php';

// Create connection to MySQL server
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
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

// Handle form submission
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $upc = $_POST['upc'];
    $category = $_POST['category'];
    $type = $_POST['type'];
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $manufacturing_date = $_POST['manufacturing_date'];
    $expiry_date = $_POST['expiry_date'];
    $measurement = $_POST['measurement'];
    $quantity = $_POST['quantity'];
    $supplier = $_POST['supplier'];
    $requires_prescription = isset($_POST['requires_prescription']) ? 1 : 0;

    $insert_sql = "INSERT INTO product (upc, category_id, type_id, product_name, description, price, manufacturing_date, expired_date, measurement, quantity, supplier_id, requires_prescription) 
                   VALUES ('$upc', '$category', '$type', '$product_name', '$description', '$price', '$manufacturing_date', '$expiry_date', '$measurement', '$quantity', '$supplier', '$requires_prescription')";

    try {
        if (mysqli_query($conn, $insert_sql)) {
            $message = "success";
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $message = "duplicate";
        } else {
            $message = "error";
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Inventory System</title>
    <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../styles/sidebar.css">
    <style>
        .container {
            display: flex;
        }
        h1 {
            text-align: center;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background-color: #fff;
            flex-grow: 1;
            height: 100vh;
            overflow-y: auto;
        }
        form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .form-group {
            flex: 1 1 calc(50% - 20px);
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: bold;
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 8px;
            margin-top: 5px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group input[type="checkbox"] {
            margin-top: 0;
            margin-right: 10px;
        }
        .form-group input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }
        .form-group input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .checkbox-group {
            flex-direction: row;
            align-items: center;
        }
        .popup {
            display: none;
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: popup-animation 1s ease-out forwards;
        }
        .popup svg {
            width: 120px;
            display: block;
            margin: 20px auto;
        }
        .popup p {
            text-align: center;
            font-size: 1.5em;
            margin: 20px 0;
        }
        .popup button {
            display: block;
            margin: 0 auto;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        .popup button:hover {
            background-color: #0056b3;
        }
        @keyframes popup-animation {
            0% {
                transform: translate(-50%, -50%) scale(0.5);
                opacity: 0;
            }
            100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
        }
        svg {
            width: 100px;
            display: block;
            margin: 40px auto 0;
        }
        .path {
            stroke-dasharray: 1000;
            stroke-dashoffset: 0;
        }
        .path.circle {
            -webkit-animation: dash .9s ease-in-out;
            animation: dash .9s ease-in-out;
        }
        .path.line {
            stroke-dashoffset: 1000;
            -webkit-animation: dash .9s .35s ease-in-out forwards;
            animation: dash .9s .35s ease-in-out forwards;
        }
        .path.check {
            stroke-dashoffset: -100;
            -webkit-animation: dash-check .9s .35s ease-in-out forwards;
            animation: dash-check .9s .35s ease-in-out forwards;
        }
        p {
            text-align: center;
            margin: 20px 0 60px;
            font-size: 1.25em;
        }
        p.success {
            color: #73AF55;
        }
        p.error {
            color: #D06079;
        }
        @-webkit-keyframes dash {
            0% {
                stroke-dashoffset: 1000;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }
        @keyframes dash {
            0% {
                stroke-dashoffset: 1000;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }
        @-webkit-keyframes dash-check {
            0% {
                stroke-dashoffset: -100;
            }
            100% {
                stroke-dashoffset: 900;
            }
        }
        @keyframes dash-check {
            0% {
                stroke-dashoffset: -100;
            }
            100% {
                stroke-dashoffset: 900;
            }
        }
        input#requires_prescription {
            width: 20px;
            height: 20px;
            border-radius: 10px;
            border: 3px solid lightgray;
        }
        div.form-group.checkbox-group{
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <h1>Add Medicine</h1>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="upc">UPC</label>
                <input type="text" id="upc" name="upc" placeholder="Enter UPC" required>
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="" disabled selected>Select category</option>
                    <?php while ($row = mysqli_fetch_assoc($category_result)) { ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['category_name']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type" required>
                    <option value="" disabled selected>Select type</option>
                    <?php while ($row = mysqli_fetch_assoc($type_result)) { ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['type_name']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="product_name">Product Name</label>
                <input type="text" id="product_name" name="product_name" placeholder="Enter Product Name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Enter Description" required></textarea>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="text" id="price" name="price" placeholder="Enter Price" required>
            </div>
            <div class="form-group">
                <label for="manufacturing_date">Manufacturing Date</label>
                <input type="date" id="manufacturing_date" name="manufacturing_date" required>
            </div>
            <div class="form-group">
                <label for="expiry_date">Expiry Date</label>
                <input type="date" id="expiry_date" name="expiry_date" required>
            </div>
            <div class="form-group">
                <label for="measurement">Measurement</label>
                <input type="text" id="measurement" name="measurement" placeholder="Enter Measurement" required>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" placeholder="Enter Quantity" required>
            </div>
            <div class="form-group">
                <label for="supplier">Supplier</label>
                <select id="supplier" name="supplier" required>
                    <option value="" disabled selected>Select supplier</option>
                    <?php while ($row = mysqli_fetch_assoc($supplier_result)) { ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['supplier_name']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group checkbox-group">
                <label for="requires_prescription">Requires Prescription &ensp;</label>
                <input type="checkbox" id="requires_prescription" name="requires_prescription">
            </div>
            <div class="form-group">
                <input type="submit" value="Add Medicine">
            </div>
        </form>
    </div>
    <div id="popup" class="popup">
        <?php if ($message === 'success') { ?>
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                <circle class="path circle" fill="none" stroke="#73AF55" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                <polyline class="path check" fill="none" stroke="#73AF55" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
            </svg>
            <p class="success">Medicine added successfully!</p>
        <?php } else if ($message === 'duplicate') { ?>
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                <circle class="path circle" fill="none" stroke="#D06079" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="34.4" y1="37.9" x2="95.8" y2="92.3"/>
                <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="95.8" y1="38" x2="34.4" y2="92.2"/>
            </svg>
            <p class="error">Duplicate UPC entry!</p>
        <?php } else if ($message === 'error') { ?>
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                <circle class="path circle" fill="none" stroke="#D06079" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="34.4" y1="37.9" x2="95.8" y2="92.3"/>
                <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="95.8" y1="38" x2="34.4" y2="92.2"/>
            </svg>
            <p class="error">Error adding medicine!</p>
        <?php } ?>
        <button onclick="document.getElementById('popup').style.display='none'">Close</button>
    </div>
    <script>
        <?php if ($message === 'success' || $message === 'duplicate' || $message === 'error') { ?>
            document.getElementById('popup').style.display = 'block';
        <?php } ?>
    </script>
</body>
</html>
