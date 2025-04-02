<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'connect.php';
    $conn = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Check if user is logged in and get user details
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get user details from users table
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Validate required fields
    error_log("POST data received: " . print_r($_POST, true));

// Check individual fields and log which ones are missing
$required_fields = ['goods_id', 'duration_days', 'total_amount'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

    // Get and sanitize data
    $goods_id = filter_var($_POST['goods_id'], FILTER_VALIDATE_INT);
    $rent_days = filter_var($_POST['duration_days'], FILTER_VALIDATE_INT);
    $total_cost = filter_var($_POST['total_amount'], FILTER_VALIDATE_FLOAT);

    // Begin transaction
    $conn->beginTransaction();

    // Insert rental recorda
    $stmt = $conn->prepare("
        INSERT INTO rentals 
        (user_id, goods_id, rent_days, rental_date, total_cost) 
        VALUES (?, ?, ?, CURRENT_DATE, ?) 
        RETURNING rental_id
    ");
    
    $stmt->execute([
        $user['user_id'],
        $goods_id,
        $rent_days,
        $total_cost
    ]);
    
    $rental_id = $stmt->fetchColumn();

    // Update goods availability
    $stmt = $conn->prepare("
        UPDATE goods 
        SET availability_status = false 
        WHERE goods_id = ?
    ");
    $stmt->execute([$goods_id]);

    // Commit transaction
    $conn->commit();

    // Send success response with user details
    echo json_encode([
        'success' => true,
        'rental_id' => $rental_id,
        'renter_info' => [
            'username' => $user['username'],
            'phone' => $user['phone'],
            'address' => $user['address'],
            'zipcode' => $user['zipcode']
        ],
        'message' => 'Rental saved successfully'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Save rental error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}