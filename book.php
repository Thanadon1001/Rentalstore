<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบ session
if (!isset($_SESSION['user_id'])) {
    // ถ้ามี session_id จาก thank_you.php
    if (isset($_GET['session_id'])) {
        session_id($_GET['session_id']);
        session_start();
    }
    // ถ้ายังไม่มี session ให้ไปหน้า login
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
require_once 'connect.php';

try {
    // Create PDO connection
    $conn = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Modified query to check rental status
    $query = "
        SELECT 
            g.*,
            CASE 
                WHEN EXISTS (
                    SELECT 1 
                    FROM rentals r 
                    WHERE r.goods_id = g.goods_id 
                    AND r.rental_date + (r.rent_days || ' days')::INTERVAL >= CURRENT_DATE
                ) THEN true 
                ELSE false 
            END as is_rented
        FROM goods g
        ORDER BY g.category, g.goods_name
    ";
    
    // Execute query
    $stmt = $conn->query($query);
    $goods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group items by category
    $categorized_goods = [];
    foreach ($goods as $item) {
        $category = $item['category'] ?: 'Other';
        $categorized_goods[$category][] = $item;
    }
    
    // Sort categories alphabetically
    ksort($categorized_goods);
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die('<div class="alert alert-danger m-3">ขออภัย เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล</div>');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StyleSwap - Rental Store</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">
    <style>
        .category-section {
            margin-bottom: 40px;
        }
        .category-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 30px;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-img-top {
            height: 300px;
            object-fit: cover;
        }
        .card-body {
            padding: 20px;
        }
        .price-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .btn-star {
            background: transparent;
            color: #ffd700;
            border: 2px solid #ffd700;
            margin-right: 10px;
        }
        .btn-star:hover {
            background: #ffd700;
            color: white;
        }
        .btn-rent {
            background: #667eea;
            border: none;
        }
        .btn-rent:hover {
            background: #764ba2;
        }
        .size-info {
            color: #666;
            font-size: 0.9em;
            margin: 10px 0;
        }
        .rental-status {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        padding: 5px 15px;
        border-radius: 4px;
        font-size: 0.9em;
        z-index: 1;
    }

    .btn-secondary {
        background: #dc3545;
        border: none;
        opacity: 0.9;
        cursor: not-allowed;
        padding: 8px 16px;
        border-radius: 4px;
    }

    .btn-secondary:hover {
        background: #c82333;
    }
    .logout-btn {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #dc3545;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        z-index: 1000;
        transition: background-color 0.3s;
    }

    .logout-btn:hover {
        background: #c82333;
        text-decoration: none;
        color: white;
    }

    .logout-btn i {
        margin-right: 5px;
    }
    </style>
</head>
<body>
<a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>Logout
    </a>
    <div class="container-fluid py-5">
        <?php foreach ($categorized_goods as $category => $items): ?>
        <div class="category-section">
            <h2 class="category-title">
                <i class="fas fa-tag mr-2"></i><?php echo htmlspecialchars($category); ?>
            </h2>
            
            <div class="row">
                <?php foreach (array_chunk($items, 3) as $chunk): ?>
                    <?php foreach ($chunk as $item): ?>
                    <div class="col-md-4">
                    <div class="card">
    <?php if ($item['is_rented']): ?>
        <div class="rental-status">RENTED</div>
    <?php endif; ?>
    <img src="img/<?php echo htmlspecialchars($item['image_path']); ?>" 
         class="card-img-top" 
         alt="<?php echo htmlspecialchars($item['goods_name']); ?>">
    <div class="card-body">
        <h5 class="card-title font-weight-bold">
            <?php echo htmlspecialchars($item['goods_name']); ?>
        </h5>
        <p class="card-text">
            <?php echo nl2br(htmlspecialchars($item['description'])); ?>
        </p>
        <div class="size-info">
            <i class="fas fa-ruler mr-2"></i>
            <?php echo htmlspecialchars($item['size_info']); ?>
        </div>
        <div class="price-section">
            <div class="mb-2">
                <i class="far fa-clock mr-2"></i>1 วัน: <?php echo number_format($item['price_1_day']); ?> บาท
            </div>
            <div class="mb-2">
                <i class="far fa-clock mr-2"></i>3 วัน: <?php echo number_format($item['price_3_day']); ?> บาท
            </div>
            <div>
                <i class="far fa-clock mr-2"></i>7 วัน: <?php echo number_format($item['price_7_day']); ?> บาท
            </div>
        </div>
        <div class="mt-3">
            <button id="star-btn-<?php echo $item['goods_id']; ?>" 
                    onclick="toggleStar(this)" 
                    class="btn btn-star">
                <i class="far fa-star"></i>
            </button>
            <?php if ($item['is_rented']): ?>
                <button class="btn btn-secondary text-white" disabled>
                    <i class="fas fa-clock mr-2"></i>RENTED
                </button>
            <?php else: ?>
                <a href="pay.php?id=<?php echo $item['goods_id']; ?>" 
                   class="btn btn-rent text-white">
                    <i class="fas fa-shopping-cart mr-2"></i>เช่าเลย
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    function toggleStar(btn) {
        const icon = btn.querySelector('i');
        if (icon.classList.contains('far')) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            alert('เพิ่มในรายการโปรดแล้ว!');
        } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            alert('นำออกจากรายการโปรดแล้ว!');
        }
    }
    </script>
</body>
</html>