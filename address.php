<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data with validation
        $username = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $zipcode = filter_input(INPUT_POST, 'zipcode', FILTER_SANITIZE_STRING);

        // Validate required fields
        if (!$username || !$password || !$address || !$phone || !$zipcode) {
            throw new Exception("All fields are required");
        }

        // Check if username exists
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        $count = $check_stmt->fetchColumn();

        if ($count > 0) {
            throw new Exception("This username is already taken. Please choose another one.");
        }

        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO users (username, password, address, phone, zipcode) 
            VALUES (?, ?, ?, ?, ?)
            RETURNING user_id, username
        ");
            
        $stmt->execute([$username, $password, $address, $phone, $zipcode]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("Failed to create user account");
        }

        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];

        // Debug log
        error_log("User registered successfully: " . json_encode($user));

        // Redirect to book page
        header("Location: book.php");
        exit();

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "Database error occurred. Please try again later.";
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - StyleSwap</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    body {
        min-height: 100vh;
        background: #f8f9fe;
        background-image: 
            radial-gradient(at 80% 0%, #e0e6ff 0px, transparent 50%),
            radial-gradient(at 0% 50%, #ede9ff 0px, transparent 50%);
        padding: 40px 20px;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
    }

    .card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.8);
    }

    .card-title {
        color: #2d3436;
        font-size: 28px;
        font-weight: 600;
        text-align: center;
        margin-bottom: 35px;
        position: relative;
        padding-bottom: 15px;
    }

    .card-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: #6b7280;
        border-radius: 2px;
    }

    .input-field {
        position: relative;
        margin-bottom: 25px;
    }

    .input-field .material-icons {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        transition: all 0.3s;
    }

    .input-field input,
    .input-field textarea {
        width: 100%;
        padding: 15px 15px 15px 50px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s;
        background: white;
    }

    .input-field textarea {
        min-height: 120px;
        resize: vertical;
    }

    .input-field input:focus,
    .input-field textarea:focus {
        border-color: #6b7280;
        outline: none;
        box-shadow: 0 0 0 4px rgba(107, 114, 128, 0.1);
    }

    .input-field input:focus + .material-icons,
    .input-field textarea:focus + .material-icons {
        color: #6b7280;
    }

    .input-field label {
        position: absolute;
        left: 50px;
        top: 15px;
        color: #9ca3af;
        transition: all 0.3s;
        pointer-events: none;
    }

    .input-field input:focus ~ label,
    .input-field textarea:focus ~ label,
    .input-field input:valid ~ label,
    .input-field textarea:valid ~ label {
        transform: translateY(-25px);
        font-size: 12px;
        color: #6b7280;
        background: white;
        padding: 0 6px;
    }

    .buttons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 40px;
    }

    .btn {
        padding: 15px 30px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-cancel {
        background: #f3f4f6;
        color: #4b5563;
    }

    .btn-confirm {
        background: #4b5563;
        color: white;
        box-shadow: 0 4px 6px rgba(75, 85, 99, 0.1);
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    .btn-cancel:hover {
        background: #e5e7eb;
    }

    .btn-confirm:hover {
        background: #374151;
    }

    @media (max-width: 768px) {
        .card {
            padding: 30px 20px;
        }

        .buttons {
            grid-template-columns: 1fr;
        }
    }
    .error-message {
            background: #fff3f3;
            color: #e74c3c;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #ffd1d1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
</style>
</head>
<body>
<div class="container">
        <div class="card">
            <h2 class="card-title">Create Your Account</h2>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="material-icons">error_outline</i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <!-- เพิ่มตรงนี้ -->
            <form method="post">
                <div class="input-field">
                    <input id="fullname" name="fullname" type="text" required>
                    <i class="material-icons">account_circle</i>
                    <label for="fullname">Username</label>
                </div>

                <div class="input-field">
                    <input id="password" name="password" type="password" required>
                    <i class="material-icons">lock</i>
                    <label for="password">Password</label>
                </div>

                <div class="input-field">
                    <textarea id="address" name="address" required></textarea>
                    <i class="material-icons">home</i>
                    <label for="address">Address</label>
                </div>

                <div class="input-field">
                    <input id="phone" name="phone" type="tel" required>
                    <i class="material-icons">phone</i>
                    <label for="phone">Phone Number</label>
                </div>

                <div class="input-field">
                    <input id="zipcode" name="zipcode" type="text" required>
                    <i class="material-icons">markunread_mailbox</i>
                    <label for="zipcode">Postal Code</label>
                </div>

                <div class="buttons">
                    <a href="type.html" class="btn btn-cancel">
                        <i class="material-icons">arrow_back</i>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-confirm">
                        <i class="material-icons">check</i>
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add animation for input fields
        document.querySelectorAll('.input-field input, .input-field textarea').forEach(input => {
            if (input.value) {
                input.parentElement.classList.add('active');
            }
            
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('active');
            });
            
            input.addEventListener('blur', () => {
                if (!input.value) {
                    input.parentElement.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>