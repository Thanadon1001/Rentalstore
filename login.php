<?php
session_start();
require_once 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Check user credentials
        $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                // Clear any existing session data
                session_unset();
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                
                // Debug logging
                error_log("Setting session data - User ID: " . $user['user_id']);
                error_log("Current session data: " . print_r($_SESSION, true));
                
                // Ensure session is written
                session_write_close();
                
                // Start new session for next request
                session_start();
                
                // Verify session was saved
                error_log("Verifying session data: " . print_r($_SESSION, true));
                
                // Make sure there's no output before redirect
                if (ob_get_length()) ob_clean();
                
                // Redirect to book.php
                header("Location: book.php");
                exit();
            } else {
                $error = "Incorrect password";
            }
        } else {
            $error = "Username not found";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "An error occurred during login";
    }
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StyleSwap - Login</title>
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
        display: flex;
        justify-content: center;
        align-items: center;
        background: #faf7f2;
        background-image: 
            radial-gradient(at 90% 10%, rgba(0, 0, 0, 0.03) 0px, transparent 50%),
            radial-gradient(at 10% 90%, rgba(0, 0, 0, 0.02) 0px, transparent 50%);
        padding: 20px;
    }

    .container {
        width: 100%;
        max-width: 450px;
    }

    .card {
        background: rgba(255, 253, 250, 0.95);
        padding: 40px;
        border-radius: 20px;
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.08),
            0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
    }

    .card-title {
        font-size: 28px;
        font-weight: 600;
        color: #1a1a1a;
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
        background: #1a1a1a;
        border-radius: 2px;
    }

    .input-field {
        position: relative;
        margin-bottom: 25px;
    }

    .input-field input {
        width: 100%;
        padding: 15px 15px 15px 45px;
        border: 2px solid rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: white;
        color: #1a1a1a;
    }

    .input-field input:focus {
        border-color: #1a1a1a;
        outline: none;
        box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.05);
    }

    .input-field .material-icons {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
        transition: all 0.3s ease;
    }

    .input-field input:focus + .material-icons {
        color: #1a1a1a;
    }

    .btn {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
        color: white;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .btn:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #333333 0%, #1a1a1a 100%);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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
    }

    @media (max-width: 480px) {
        .card {
            padding: 30px 20px;
        }

        .card-title {
            font-size: 24px;
        }

        .input-field input {
            padding: 12px 12px 12px 40px;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h4 class="card-title">Welcome Back</h4>
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="material-icons" style="font-size: 16px; vertical-align: text-bottom;">error_outline</i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="input-field">
                    <input id="username" name="username" type="text" placeholder="Username" required>
                    <i class="material-icons">account_circle</i>
                </div>
                <div class="input-field">
                    <input id="password" name="password" type="password" placeholder="Password" required>
                    <i class="material-icons">lock</i>
                </div>
                <button type="submit" class="btn">
                    Sign In
                    <i class="material-icons">arrow_forward</i>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Add focus effects for better UX
        document.querySelectorAll('.input-field input').forEach(input => {
            input.addEventListener('focus', () => {
                input.previousElementSibling?.classList.add('active');
            });
            input.addEventListener('blur', () => {
                if (!input.value) {
                    input.previousElementSibling?.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>