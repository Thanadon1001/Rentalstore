<?php
$dsn = "pgsql:host=caboose.proxy.rlwy.net;port=52806;dbname=postgres";
$username = "postgres";
$password = "ikqjokIoIpnvGEzjITisIjFvFbVZkagO";


try {
    $conn = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Create Users table with password column
    $sql_users = "
        CREATE TABLE IF NOT EXISTS users (
            user_id SERIAL PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            address TEXT NOT NULL,
            phone VARCHAR(20),          -- Added phone column
            zipcode VARCHAR(10),        -- Added zipcode column
            password TEXT NOT NULL      -- Added password column
        )";

    // Add password column if table exists but column doesn't
    $sql_add_password = "
        DO $$
        BEGIN
            IF EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_name = 'users'
            ) AND NOT EXISTS (
                SELECT FROM information_schema.columns 
                WHERE table_name = 'users' AND column_name = 'password'
            ) THEN
                ALTER TABLE users ADD COLUMN password TEXT NOT NULL DEFAULT '';
            END IF;
        END $$;
    ";

    // Create enhanced Goods table with pricing
    $sql_goods = "
        CREATE TABLE IF NOT EXISTS goods (
            goods_id SERIAL PRIMARY KEY,
            goods_name VARCHAR(100) NOT NULL,
            description TEXT,
            image_path VARCHAR(255),
            price_1_day DECIMAL(10,2) NOT NULL,
            price_3_day DECIMAL(10,2) NOT NULL,
            price_7_day DECIMAL(10,2) NOT NULL,
            category VARCHAR(50),
            size_info VARCHAR(100),
            availability_status BOOLEAN DEFAULT true
        )";
    // Create Rentals table (unchanged)
   // ...existing code...

    // Create enhanced Rentals table
    $sql_rentals = "
        CREATE TABLE IF NOT EXISTS rentals (
            rental_id SERIAL PRIMARY KEY,
            user_id INT REFERENCES users(user_id) ON DELETE CASCADE,
            goods_id INT REFERENCES goods(goods_id) ON DELETE CASCADE,
            rent_days INT CHECK (rent_days IN (1, 3, 7)),
            rental_date DATE NOT NULL DEFAULT CURRENT_DATE,
            total_cost DECIMAL(10,2) NOT NULL,
            deposit_amount DECIMAL(10,2),
            notes TEXT
        )";

    // Execute the table creation queries
    $conn->exec($sql_users);
    $conn->exec($sql_add_password);
    $conn->exec($sql_goods);
    $conn->exec($sql_rentals);  // Removed $sql_pricing since it's merged with goods


    // Execute the table creation queries
    $conn->exec($sql_users);
    $conn->exec($sql_add_password);
    $conn->exec($sql_goods);
    $conn->exec($sql_rentals);



} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Close the connection
$conn = null;
?>