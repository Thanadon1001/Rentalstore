<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตั้งค่าการเชื่อมต่อฐานข้อมูล
require_once 'connect.php';

// เพิ่ม debugging
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Rental ID: " . ($_GET['rental_id'] ?? 'not set'));

// ตรวจสอบ session และ rental_id
if (!isset($_SESSION['user_id']) || !isset($_GET['rental_id'])) {
    header('Location: login.php');
    exit;
}

try {
    // สร้างการเชื่อมต่อ database พร้อม error mode
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $rental_id = $_GET['rental_id'];
    
    // Query ดึงข้อมูลจาก 3 ตาราง - ต้องตรงกับโครงสร้างตารางจริง
    $sql = "
        SELECT 
            r.rental_id,
            r.rent_days,
            r.rental_date,
            r.total_cost as total_amount, -- เปลี่ยนชื่อฟิลด์ให้ตรงกับที่ใช้ในหน้า HTML
            g.goods_name,
            g.category,
            g.size_info,
            u.username,
            u.phone,
            u.address,
            u.zipcode
        FROM rentals r
        JOIN goods g ON r.goods_id = g.goods_id
        JOIN users u ON r.user_id = u.user_id
        WHERE r.rental_id = :rental_id 
        AND r.user_id = :user_id
    ";

    // เตรียมและ execute query
    $stmt = $conn->prepare($sql);
    $params = [
        ':rental_id' => $rental_id,
        ':user_id' => $_SESSION['user_id']
    ];
    
    // Debug log
    error_log("Executing query with params: " . json_encode($params));
    
    $stmt->execute($params);
    $rental = $stmt->fetch();

    // ตรวจสอบว่าพบข้อมูลหรือไม่
    if (!$rental) {
        error_log("No rental found for ID: {$rental_id}");
        throw new Exception('Rental information not found');
    }

    // Debug log
    error_log("Found rental data: " . json_encode($rental));

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    exit('<div style="color: red; padding: 20px;">Database error occurred. Please check the logs.</div>');
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    exit('<div style="color: red; padding: 20px;">' . htmlspecialchars($e->getMessage()) . '</div>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - StyleSwap</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
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
            color: #2d3436;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #1a1a1a;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        h3::after {
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

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .detail-label {
            color: #6b7280;
            font-weight: 500;
        }

        .detail-value {
            font-weight: 400;
            color: #1a1a1a;
        }

        .btn {
            display: block;
            width: fit-content;
            margin: 30px auto 0;
            padding: 12px 30px;
            background: #4b5563;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            background: #374151;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
        <h3>Order Confirmation</h3>
            
            <!-- ข้อมูลสินค้า -->
            <div class="detail-row">
                <span class="detail-label">Product</span>
                <span class="detail-value"><?php echo htmlspecialchars($rental['goods_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Category</span>
                <span class="detail-value"><?php echo htmlspecialchars($rental['category']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Size</span>
                <span class="detail-value"><?php echo htmlspecialchars($rental['size_info']); ?></span>
            </div>

            <!-- ข้อมูลการเช่า -->
            <div class="detail-row">
                <span class="detail-label">Rental Duration</span>
                <span class="detail-value"><?php echo $rental['rent_days']; ?> days</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Rental Date</span>
                <span class="detail-value"><?php echo date('d/m/Y', strtotime($rental['rental_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount</span>
                <span class="detail-value">฿<?php echo number_format($rental['total_amount'], 2); ?></span>
            </div>

            <!-- ข้อมูลผู้เช่า -->
            <div class="detail-row">
                <span class="detail-label">Renter Name</span>
                <span class="detail-value"><?php echo htmlspecialchars($rental['username']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value"><?php echo htmlspecialchars($rental['phone']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Shipping Address</span>
                <span class="detail-value"><?php echo htmlspecialchars($rental['address']); ?></span>
            </div>
            <div class="detail-row">
    <span class="detail-label">Zipcode</span>
    <span class="detail-value"><?php echo htmlspecialchars($rental['zipcode']); ?></span>
</div>

<!-- เปลี่ยนเป็นปุ่มที่ส่ง session ไปด้วย -->
<a href="book.php?session_id=<?php echo session_id(); ?>" class="btn">Back to Shop</a>
        </div>
    </div>
</body>
</html>