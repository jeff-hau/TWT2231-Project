<?php
// index.php
session_start();
include 'config.php';

// Create connection to MySQL server (without selecting a database)
$conn = mysqli_connect($db_host, $db_user, $db_pass);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
mysqli_query($conn, $sql);

// Select the database
mysqli_select_db($conn, $db_name);

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)";
mysqli_query($conn, $sql);

// Insert a default user if the table is empty
$hashed_password = password_hash('password', PASSWORD_BCRYPT);
$sql = "INSERT INTO users (username, password) 
        SELECT 'admin', '$hashed_password' 
        WHERE NOT EXISTS (SELECT * FROM users)";
mysqli_query($conn, $sql);

// Create medicine_category table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS medicine_category (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
)";
mysqli_query($conn, $sql);

// Insert predefined categories
$categories = ['Analgesics', 'Antipsychotics', 'Hormonal agents (pituitary)', 'Anesthetics', 'Antispasticity agents', 
    'Hormonal agents (prostaglandins)', 'Anti-addiction agents', 'Antivirals', 'Hormonal agents (sex hormones)', 
    'Antibacterials', 'Anxiolytics', 'Hormonal agents (thyroid)', 'Anticonvulsants', 'Bipolar agents', 
    'Hormone suppressant (adrenal)', 'Antidementia agents', 'Blood glucose regulators', 'Hormone suppressant (pituitary)'];

foreach ($categories as $category) {
    $sql = "INSERT INTO medicine_category (category_name) 
            SELECT '$category' 
            WHERE NOT EXISTS (SELECT * FROM medicine_category WHERE category_name = '$category')";
    mysqli_query($conn, $sql);
}

// Create medicine_type table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS medicine_type (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL UNIQUE
)";
mysqli_query($conn, $sql);

// Insert predefined types
$types = ['Liquid', 'Tablet', 'Capsules', 'Inhalers', 'Syrup', 'Drop'];

foreach ($types as $type) {
    $sql = "INSERT INTO medicine_type (type_name) 
            SELECT '$type' 
            WHERE NOT EXISTS (SELECT * FROM medicine_type WHERE type_name = '$type')";
    mysqli_query($conn, $sql);
}

// Create supplier table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS supplier (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL UNIQUE,
    supplier_contact VARCHAR(15),
    supplier_address TEXT
)";
mysqli_query($conn, $sql);

// Insert predefined suppliers
$suppliers = [
    ['Pharma.Sdn. Bhd', '06123456789', 'Taman Maju, 75450, Melaka, Malaysia']
];

foreach ($suppliers as $supplier) {
    $sql = "INSERT INTO supplier (supplier_name, supplier_contact, supplier_address) 
            SELECT '{$supplier[0]}', '{$supplier[1]}', '{$supplier[2]}' 
            WHERE NOT EXISTS (SELECT * FROM supplier WHERE supplier_name = '{$supplier[0]}')";
    mysqli_query($conn, $sql);
}

// Create product table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS product (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    upc VARCHAR(12) NOT NULL UNIQUE,
    category_id INT(6) UNSIGNED,
    type_id INT(6) UNSIGNED,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2),
    manufacturing_date DATE,
    expired_date DATE,
    measurement VARCHAR(50),
    quantity INT(6),
    supplier_id INT(6) UNSIGNED,
    requires_prescription BOOLEAN,
    FOREIGN KEY (category_id) REFERENCES medicine_category(id),
    FOREIGN KEY (type_id) REFERENCES medicine_type(id),
    FOREIGN KEY (supplier_id) REFERENCES supplier(id)
)";
mysqli_query($conn, $sql);

require_once './auth_log.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['login_time'] = time();

            log_auth_event($username, 'LOGIN');

            header("location: ./pages/home.php");
            exit;
        } else {
            $error = "Invalid username or password";
            log_auth_event($username, 'FAILED_LOGIN', '- Reason: Invalid password');
        }
    } else {
        $error = "Invalid username or password";
        log_auth_event($username, 'FAILED_LOGIN', '- Reason: Invalid username');
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Inventory System</title>
    <link rel="icon" href="./assets/favicon.ico" type="image/x-icon">
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f7f9fc;
            overflow: hidden;
        }
        .login-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 2rem;
            font-size: 2.5rem;
        }
        h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            margin-top: 1rem;
        }
        #bg-wrap {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Pharmacy Inventory System</h2>
        <h3>Login</h3>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login">
        </form>
        <?php if(isset($error)) { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>
    </div>
    <div id="bg-wrap">
        <svg viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice">
            <defs>
                <radialGradient id="Gradient1" cx="50%" cy="50%" fx="0.441602%" fy="50%" r=".5">
                    <animate attributeName="fx" dur="34s" values="0%;3%;0%" repeatCount="indefinite"></animate>
                    <stop offset="0%" stop-color="rgba(255, 0, 255, 1)"></stop>
                    <stop offset="100%" stop-color="rgba(255, 0, 255, 0)"></stop>
                </radialGradient>
                <radialGradient id="Gradient2" cx="50%" cy="50%" fx="2.68147%" fy="50%" r=".5">
                    <animate attributeName="fx" dur="23.5s" values="0%;3%;0%" repeatCount="indefinite"></animate>
                    <stop offset="0%" stop-color="rgba(255, 255, 0, 1)"></stop>
                    <stop offset="100%" stop-color="rgba(255, 255, 0, 0)"></stop>
                </radialGradient>
                <radialGradient id="Gradient3" cx="50%" cy="50%" fx="0.836536%" fy="50%" r=".5">
                    <animate attributeName="fx" dur="21.5s" values="0%;3%;0%" repeatCount="indefinite"></animate>
                    <stop offset="0%" stop-color="rgba(0, 255, 255, 1)"></stop>
                    <stop offset="100%" stop-color="rgba(0, 255, 255, 0)"></stop>
                </radialGradient>
                <radialGradient id="Gradient4" cx="50%" cy="50%" fx="4.56417%" fy="50%" r=".5">
                    <animate attributeName="fx" dur="23s" values="0%;5%;0%" repeatCount="indefinite"></animate>
                    <stop offset="0%" stop-color="rgba(0, 255, 0, 1)"></stop>
                    <stop offset="100%" stop-color="rgba(0, 255, 0, 0)"></stop>
                </radialGradient>
                <radialGradient id="Gradient5" cx="50%" cy="50%" fx="2.65405%" fy="50%" r=".5">
                    <animate attributeName="fx" dur="24.5s" values="0%;5%;0%" repeatCount="indefinite"></animate>
                    <stop offset="0%" stop-color="rgba(0,0,255, 1)"></stop>
                    <stop offset="100%" stop-color="rgba(0,0,255, 0)"></stop>
                </radialGradient>
                <radialGradient id="Gradient6" cx="50%" cy="50%" fx="0.981338%" fy="50%" r=".5">
                    <animate attributeName="fx" dur="25.5s" values="0%;5%;0%" repeatCount="indefinite"></animate>
                    <stop offset="0%" stop-color="rgba(255,0,0, 1)"></stop>
                    <stop offset="100%" stop-color="rgba(255,0,0, 0)"></stop>
                </radialGradient>
            </defs>
            <rect x="13.744%" y="1.18473%" width="100%" height="100%" fill="url(#Gradient1)" transform="rotate(334.41 50 50)">
                <animate attributeName="x" dur="20s" values="25%;0%;25%" repeatCount="indefinite"></animate>
                <animate attributeName="y" dur="21s" values="0%;25%;0%" repeatCount="indefinite"></animate>
                <animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="7s" repeatCount="indefinite"></animateTransform>
            </rect>
            <rect x="-2.17916%" y="35.4267%" width="100%" height="100%" fill="url(#Gradient2)" transform="rotate(255.072 50 50)">
                <animate attributeName="x" dur="23s" values="-25%;0%;-25%" repeatCount="indefinite"></animate>
                <animate attributeName="y" dur="24s" values="0%;50%;0%" repeatCount="indefinite"></animate>
                <animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="12s" repeatCount="indefinite"></animateTransform>
            </rect>
            <rect x="9.00483%" y="14.5733%" width="100%" height="100%" fill="url(#Gradient3)" transform="rotate(139.903 50 50)">
                <animate attributeName="x" dur="25s" values="0%;25%;0%" repeatCount="indefinite"></animate>
                <animate attributeName="y" dur="12s" values="0%;25%;0%" repeatCount="indefinite"></animate>
                <animateTransform attributeName="transform" type="rotate" from="360 50 50" to="0 50 50" dur="9s" repeatCount="indefinite"></animateTransform>
            </rect>
        </svg>
    </div>
</body>
</html>
