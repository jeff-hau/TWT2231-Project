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

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)";
mysqli_query($conn, $sql);

// Handle form submission for adding user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add_user') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $insert_user_sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $insert_user_sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);
        
        try {
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['result' => 'success']);
            } else {
                echo json_encode(['result' => 'error']);
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
    
    if ($action == 'change_password') {
        $user_id = $_POST['user_id'];
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        
        // Fetch current password
        $fetch_password_sql = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $fetch_password_sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $current_password_hash);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Validate old password
        if (password_verify($old_password, $current_password_hash)) {
            $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            $update_password_sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_password_sql);
            mysqli_stmt_bind_param($stmt, "si", $hashed_new_password, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['result' => 'success']);
            } else {
                echo json_encode(['result' => 'error']);
            }
            mysqli_stmt_close($stmt); // Close the statement after execution
        } else {
            echo json_encode(['result' => 'invalid_old_password']);
        }
    }
    
    if ($action == 'delete_user') {
        $user_id = $_POST['user_id'];

        // Check the number of remaining users
        $count_users_sql = "SELECT COUNT(*) AS user_count FROM users";
        $result = mysqli_query($conn, $count_users_sql);
        $row = mysqli_fetch_assoc($result);
        $user_count = $row['user_count'];

        if ($user_count > 1) {
            $delete_user_sql = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete_user_sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['result' => 'success']);
            } else {
                echo json_encode(['result' => 'error']);
            }
            mysqli_stmt_close($stmt); // Close the statement after execution
        } else {
            echo json_encode(['result' => 'only_one_user']);
        }
    }
    exit;
}

// Fetch all users
$all_users = [];
$all_users_sql = "SELECT id, username FROM users";
$result = mysqli_query($conn, $all_users_sql);
while ($row = mysqli_fetch_assoc($result)) {
    $all_users[] = $row;
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
        .add-user-button {
            padding: 15px;
            font-size: 1.2rem;
            border: none;
            border-radius: 8px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
        }
        .add-user-button:hover {
            background-color: #0056b3;
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
        .btn-blue {
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        .btn-blue:hover {
            background-color: #0056b3;
        }
        .btn-red {
            padding: 5px;
            background-color: red;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
        .popup input[type="text"], .popup input[type="password"] {
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
        }
        #success-popup, #error-popup {
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
            text-align: center;
        }
        #success-popup svg, #error-popup svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
        }
        #success-popup h2, #error-popup h2 {
            margin-top: 0;
        }
        .error {
            color: red;
            display: none;
        }
        /* Confirmation Popup Styles */
        #delete-confirmation-popup {
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
            text-align: center;
        }
        #delete-confirmation-popup .btn-ok, 
        #delete-confirmation-popup .btn-cancel {
            margin: 10px;
        }

    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="container">
        <div class="main-content">
            <h1>User Management</h1>
            <button class="add-user-button">Add User</button>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                        <tr data-user-id="<?= $user['id']; ?>">
                            <td><?= $user['username']; ?></td>
                            <td>
                                <button class="btn-blue change-password-btn">Change Password</button>
                                <button class="btn-red delete-user-btn">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Add User Popup -->
    <div class="popup" id="add-user-popup">
        <button class="close-btn">&times;</button>
        <h2>Add User</h2>
        <form id="add-user-form">
            <div class="form-group">
                <label for="add-username">Username:</label>
                <input type="text" id="add-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="add-password">Password:</label>
                <input type="password" id="add-password" name="password" required>
            </div>
            <button type="submit" class="btn-ok">Add User</button>
            <button type="button" class="btn-cancel">Cancel</button>
        </form>
    </div>
    <!-- Change Password Popup -->
    <div class="popup" id="change-password-popup">
        <button class="close-btn">&times;</button>
        <h2>Change Password</h2>
        <form id="change-password-form">
            <div class="form-group">
                <label for="old-password">Old Password:</label>
                <input type="password" id="old-password" name="old_password" required>
            </div>
            <div class="form-group">
                <label for="new-password">New Password:</label>
                <input type="password" id="new-password" name="new_password" required>
            </div>
            <button type="submit" class="btn-ok">Change Password</button>
            <button type="button" class="btn-cancel">Cancel</button>
        </form>
    </div>
    <!-- Success Popup -->
    <div id="success-popup">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 0C5.37258 0 0 5.37258 0 12C0 18.6274 5.37258 24 12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0ZM10 17L5 12L6.41 10.59L10 14.17L17.59 6.59L19 8L10 17Z" fill="#4CAF50"/>
        </svg>
        <h2>Success</h2>
        <p id="success-message">Operation completed successfully!</p>
    </div>
    <!-- Error Popup -->
    <div id="error-popup">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 0C5.37258 0 0 5.37258 0 12C0 18.6274 5.37258 24 12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0ZM12 22C6.486 22 2 17.514 2 12C2 6.486 6.486 2 12 2C17.514 2 22 6.486 22 12C22 17.514 17.514 22 12 22ZM13 13V18H11V13H13ZM13 6V11H11V6H13Z" fill="#F44336"/>
        </svg>
        <h2>Error</h2>
        <p id="error-message">An error occurred. Please try again.</p>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Delete Confirmation Popup -->
    <div class="popup" id="delete-confirmation-popup">
        <button class="close-btn">&times;</button>
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete this user?</p>
        <button class="btn-ok" id="confirm-delete-btn">Delete</button>
        <button class="btn-cancel">Cancel</button>
    </div>

    <script>
$(document).ready(function() {
    var userIdToDelete;

    // Add User button click event
    $('.add-user-button').on('click', function() {
        $('#add-user-popup').fadeIn();
    });

    // Close button click event
    $('.close-btn, .btn-cancel').on('click', function() {
        $(this).closest('.popup').fadeOut();
    });

    // Handle Add User form submission
    $('#add-user-form').on('submit', function(event) {
        event.preventDefault();
        var formData = $(this).serialize() + '&action=add_user';

        $.post('users.php', formData, function(response) {
            var result = JSON.parse(response);
            if (result.result === 'success') {
                $('#add-user-popup').fadeOut();
                $('#success-message').text('User added successfully!');
                $('#success-popup').fadeIn();
                setTimeout(function() {
                    $('#success-popup').fadeOut();
                    location.reload();
                }, 2000);
            } else if (result.result === 'duplicate') {
                $('#error-message').text('Username already exists!');
                $('#error-popup').fadeIn();
                setTimeout(function() {
                    $('#error-popup').fadeOut();
                }, 2000);
            } else {
                $('#error-message').text('An error occurred. Please try again.');
                $('#error-popup').fadeIn();
                setTimeout(function() {
                    $('#error-popup').fadeOut();
                }, 2000);
            }
        });
    });

    // Handle Change Password button click event
    $(document).on('click', '.change-password-btn', function() {
        var userId = $(this).closest('tr').data('user-id');
        $('#change-password-popup').data('user-id', userId).fadeIn();
    });

    // Handle Change Password form submission
    $('#change-password-form').on('submit', function(event) {
        event.preventDefault();
        var userId = $('#change-password-popup').data('user-id');
        var formData = $(this).serialize() + '&action=change_password&user_id=' + userId;

        $.post('users.php', formData, function(response) {
            var result = JSON.parse(response);
            if (result.result === 'success') {
                $('#change-password-popup').fadeOut();
                $('#success-message').text('Password changed successfully!');
                $('#success-popup').fadeIn();
                setTimeout(function() {
                    $('#success-popup').fadeOut();
                }, 2000);
            } else if (result.result === 'invalid_old_password') {
                $('#error-message').text('Invalid old password.');
                $('#error-popup').fadeIn();
                setTimeout(function() {
                    $('#error-popup').fadeOut();
                }, 2000);
            } else {
                $('#error-message').text('An error occurred. Please try again.');
                $('#error-popup').fadeIn();
                setTimeout(function() {
                    $('#error-popup').fadeOut();
                }, 2000);
            }
        });
    });

    // Handle Delete User button click event
    $(document).on('click', '.delete-user-btn', function() {
        userIdToDelete = $(this).closest('tr').data('user-id');
        $('#delete-confirmation-popup').fadeIn();
    });

    // Handle Delete Confirmation
    $('#confirm-delete-btn').on('click', function() {
        $.post('users.php', { action: 'delete_user', user_id: userIdToDelete }, function(response) {
            var result = JSON.parse(response);
            $('#delete-confirmation-popup').fadeOut();
            if (result.result === 'success') {
                $('#success-message').text('User deleted successfully!');
                $('#success-popup').fadeIn();
                setTimeout(function() {
                    $('#success-popup').fadeOut();
                    location.reload();
                }, 2000);
            } else if (result.result === 'only_one_user') {
                $('#error-message').text('Cannot delete the only remaining user.');
                $('#error-popup').fadeIn();
                setTimeout(function() {
                    $('#error-popup').fadeOut();
                }, 2000);
            } else {
                $('#error-message').text('An error occurred. Please try again.');
                $('#error-popup').fadeIn();
                setTimeout(function() {
                    $('#error-popup').fadeOut();
                }, 2000);
            }
        });
    });
});

    </script>
</body>
</html>
