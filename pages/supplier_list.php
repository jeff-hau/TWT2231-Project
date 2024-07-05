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

// Handle form submission for adding supplier
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['supplier_name'])) {
    $supplier_name = $_POST['supplier_name'];
    $supplier_contact = $_POST['supplier_contact'];
    $supplier_address = $_POST['supplier_address'];

    $insert_supplier_sql = "INSERT INTO supplier (supplier_name, supplier_contact, supplier_address) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_supplier_sql);
    mysqli_stmt_bind_param($stmt, "sss", $supplier_name, $supplier_contact, $supplier_address);

    try {
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['result' => 'success']);
            exit;
        } else {
            echo json_encode(['result' => 'error']);
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo json_encode(['result' => 'duplicate']);
        } else {
            echo json_encode(['result' => 'error']);
        }
    } finally {
        mysqli_stmt_close($stmt); // Close the statement after execution
    }
}


// Handle search
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$search_results = [];
$all_suppliers = [];

if (!empty($search_term)) {
    $search_sql = "SELECT id, supplier_name, supplier_contact FROM supplier WHERE supplier_name LIKE ? OR supplier_contact LIKE ? LIMIT 10";
    $stmt = mysqli_prepare($conn, $search_sql);
    $search_param = "%$search_term%";
    mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $search_results[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Fetch all suppliers
$all_suppliers_sql = "SELECT id, supplier_name, supplier_contact FROM supplier";
$result = mysqli_query($conn, $all_suppliers_sql);
while ($row = mysqli_fetch_assoc($result)) {
    $all_suppliers[] = $row;
}

// Close connection
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
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        .container {
            display: flex;
            width: 100%;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background-color: #fff;
            flex-grow: 1;
            height: 100vh;
            overflow-y: auto;
        }
        .add-supplier-button {
            padding: 15px;
            font-size: 1.2rem;
            border: none;
            border-radius: 8px;
            background-color: #28a745;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
        }
        .add-supplier-button:hover {
            background-color: #218838;
        }
        .search-container {
            display: flex;
            margin-bottom: 20px;
            align-items: center;
        }
        .search-bar {
            flex-grow: 1;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
        }
        .search-button img {
            width: 20px;
            height: 20px;
        }
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }
        th {
            background-color: #f2f2f2;
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
            animation: popup-animation 0.3s ease-out forwards;
            width: 80%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .popup .form-group {
            margin-bottom: 20px;
        }
        .popup label {
            font-weight: bold;
        }
        .popup input[type="text"], .popup textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .popup button {
            padding: 10px 20px;
            font-size: 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .popup .btn-ok {
            background-color: #007bff;
            color: white;
        }
        .popup .btn-ok:hover {
            background-color: #0056b3;
        }
        .popup .btn-cancel {
            background-color: #ddd;
            margin-left: 10px;
        }
        .popup .btn-cancel:hover {
            background-color: #bbb;
        }
        .popup .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        @keyframes popup-animation {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
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
        p.result-message {
            text-align: center;
            margin: 20px 0 60px;
            font-size: 1.25em;
        }
        p.result-message.success {
            color: #73AF55;
        }
        p.result-message.error {
            color: #D06079;
        }
        @-webkit-keyframes dash {
            0% { stroke-dashoffset: 1000; }
            100% { stroke-dashoffset: 0; }
        }
        @keyframes dash {
            0% { stroke-dashoffset: 1000; }
            100% { stroke-dashoffset: 0; }
        }
        @-webkit-keyframes dash-check {
            0% { stroke-dashoffset: -100; }
            100% { stroke-dashoffset: 900; }
        }
        @keyframes dash-check {
            0% { stroke-dashoffset: -100; }
            100% { stroke-dashoffset: 900; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../components/sidebar.php'; ?>
        <div class="main-content">
            <h1>Supplier</h1>

            <button class="add-supplier-button" id="add-supplier-button">Add Supplier</button>
            
            <form action="" method="GET" class="search-container">
                <input type="text" id="search-input" name="search" class="search-bar" placeholder="Search by company name or phone number" value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="search-button">
                    <img src="../assets/search.svg" alt="Search">
                </button>
            </form>

            <?php if (!empty($search_term)) { ?>
                <h2>Search Results</h2>
                <?php if (!empty($search_results)) { ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Supplier Name</th>
                                <th>Phone Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($search_results as $supplier) { ?>
                                <tr onclick="showSupplierInfo(<?php echo $supplier['id']; ?>)">
                                    <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['supplier_contact']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p>No suppliers found.</p>
                <?php } ?>
            <?php } else { ?>
                <p>Perform a search to get results.</p>
            <?php } ?>

            <h2>All Suppliers</h2>
            <table>
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Phone Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_suppliers)) { ?>
                        <tr>
                            <td colspan="2">No suppliers found</td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($all_suppliers as $supplier) { ?>
                            <tr onclick="showSupplierInfo(<?php echo $supplier['id']; ?>)">
                                <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['supplier_contact']); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="popup" id="add-supplier-popup">
        <button class="close-btn" onclick="closePopup('add-supplier-popup')">&times;</button>
        <form id="add-supplier-form" method="post" action="supplier_list.php">
            <div class="form-group">
                <label for="supplier_name">Supplier Name:</label>
                <input type="text" id="supplier_name" name="supplier_name" required>
            </div>
            <div class="form-group">
                <label for="supplier_contact">Contact Number:</label>
                <input type="text" id="supplier_contact" name="supplier_contact" required>
            </div>
            <div class="form-group">
                <label for="supplier_address">Address:</label>
                <textarea id="supplier_address" name="supplier_address" required></textarea>
            </div>
            <button type="submit" class="btn-ok">Add</button>
            <button type="button" class="btn-cancel" onclick="closePopup('add-supplier-popup')">Cancel</button>
        </form>
    </div>

    <div class="popup" id="supplier-info-popup">
        <button class="close-btn" onclick="closePopup('supplier-info-popup')">&times;</button>
        <div id="supplier-info-content"></div>
    </div>

    <div class="popup" id="add-result-popup">
        <div id="result-animation"></div>
        <p id="result-message" class="result-message"></p>
        <button type="button" class="btn-ok" onclick="closePopup('add-result-popup')">OK</button>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(function() {
            $("#search-input").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "supplier_search.php",
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $("#search-input").val(ui.item.value);
                    return false;
                }
            });
        });

        document.getElementById('add-supplier-button').addEventListener('click', function() {
            document.getElementById('add-supplier-popup').style.display = 'block';
        });

        function closePopup(popupId) {
            document.getElementById(popupId).style.display = 'none';
            if (popupId === 'add-result-popup') {
                resetForm();
            }
        }

        function resetForm() {
            document.getElementById('add-supplier-form').reset();
        }

        function showSupplierInfo(supplierId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'supplier_info.php?id=' + supplierId, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var supplierDetails = JSON.parse(xhr.responseText);
                    var popupContent = `
                        <h2>Supplier Info</h2>
                        <p><strong>Name:</strong> ${supplierDetails.supplier_name}</p>
                        <p><strong>Contact:</strong> ${supplierDetails.supplier_contact}</p>
                        <p><strong>Address:</strong> ${supplierDetails.supplier_address}</p>
                    `;
                    document.getElementById('supplier-info-content').innerHTML = popupContent;
                    document.getElementById('supplier-info-popup').style.display = 'block';
                }
            };
            xhr.send();
        }

        function showResultPopup(result) {
            var resultAnimation = document.getElementById('result-animation');
            var resultMessage = document.getElementById('result-message');
            
            if (result === 'success') {
                resultAnimation.innerHTML = `
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                        <circle class="path circle" fill="none" stroke="#73AF55" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                        <polyline class="path check" fill="none" stroke="#73AF55" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
                    </svg>
                `;
                resultMessage.textContent = "Supplier added successfully!";
                resultMessage.className = "result-message success";
            } else {
                resultAnimation.innerHTML = `
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                        <circle class="path circle" fill="none" stroke="#D06079" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                        <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="34.4" y1="37.9" x2="95.8" y2="92.3"/>
                        <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="95.8" y1="38" x2="34.4" y2="92.2"/>
                    </svg>
                `;
                resultMessage.textContent = result === 'duplicate' ? "Error: Supplier name already exists." : "Error: Failed to add supplier.";
                resultMessage.className = "result-message error";
            }
            
            document.getElementById('add-supplier-popup').style.display = 'none';
            document.getElementById('add-result-popup').style.display = 'block';
        }

        // Handle form submission
        document.getElementById('add-supplier-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            
            fetch('supplier_list.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showResultPopup(data.result);
                if (data.result === 'success') {
                    // Refresh the page after a short delay to show the updated supplier list
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showResultPopup('error');
            });
        });
        </script>
</body>
</html>
