<?php
session_start();
if(!isset($_SESSION['username'])){
    header('location: ../login/login.php');
}

// Include database connection
include '../databse/connect.php';

// Fetch all orders for the current user
$userId = $_SESSION['userid'];
$query = "SELECT o.*, 
          COUNT(DISTINCT oi.item_id) as total_items,
          o.updated_at,
          GROUP_CONCAT(
              CONCAT(p.product_name, ' (', f.farm_name, ') - ', oi.quantity, ' ', p.unit) 
              SEPARATOR ', '
          ) as product_details
          FROM tbl_orders o 
          LEFT JOIN tbl_order_items oi ON o.order_id = oi.order_id 
          LEFT JOIN tbl_products p ON oi.product_id = p.product_id
          LEFT JOIN tbl_farms f ON p.farm_id = f.farm_id
          WHERE o.user_id = ? 
          GROUP BY o.order_id 
          ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderId = intval($_POST['order_id']);
    $userId = $_SESSION['userid'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First verify the order belongs to the user and is pending
        $verifyQuery = "SELECT order_id FROM tbl_orders 
                       WHERE order_id = ? AND user_id = ? AND order_status = 'pending'";
        $stmt = $conn->prepare($verifyQuery);
        $stmt->bind_param("ii", $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Invalid order or order cannot be cancelled');
        }

        // Get the order items to restore quantities
        $itemsQuery = "SELECT product_id, quantity FROM tbl_order_items WHERE order_id = ?";
        $stmt = $conn->prepare($itemsQuery);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        // Update product quantities
        while ($item = $items_result->fetch_assoc()) {
            $updateStockQuery = "UPDATE tbl_products 
                               SET stock = stock + ? 
                               WHERE product_id = ?";
            $stmt = $conn->prepare($updateStockQuery);
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update product stock');
            }
        }
        
        // Update order status to cancelled
        $updateOrderQuery = "UPDATE tbl_orders 
                           SET order_status = 'cancelled', 
                               updated_at = CURRENT_TIMESTAMP 
                           WHERE order_id = ?";
        $stmt = $conn->prepare($updateOrderQuery);
        $stmt->bind_param("i", $orderId);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update order status');
        }
        
        // Commit transaction
        mysqli_commit($conn);
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Dashboard - Farmfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f0f2f5;
        }

        .sidebar {
            width: 250px;
            background: #1a4d2e;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            transition: width 0.3s ease;
        }

        .sidebar.shrink {
            width: 80px;
        }

        .sidebar .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar .sidebar-header h2 {
            transition: opacity 0.3s ease, width 0.3s ease;
        }

        .sidebar.shrink .sidebar-header h2 {
            opacity: 0;
            visibility: hidden;
            width: 0;
        }

        .sidebar .menu img {
            width: 25px;
            height: 25px;
            cursor: pointer;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .sidebar-menu li {
            margin: 15px 0;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar-menu a:hover {
            background: #2d6a4f;
        }

        .sidebar-menu i {
            margin-right: 10px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .active {
            background: #2d6a4f;
            font-weight: 500;
        }
        .sidebar.shrink .sidebar-menu span {
            opacity: 0;
            visibility: hidden;
            width: 0;
            transition: opacity 0.3s ease, width 0.3s ease;
        }

        .sidebar.shrink .sidebar-menu i {
            margin-right: 0;
        }

        .pro {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-menu-container {
            position: relative;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            background-color: #1a4d2e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .profile-icon i {
            color: white;
            font-size: 1.2rem;
        }

        .profile-icon:hover {
            background-color: #2d6a4f;
        }

        .profile-popup {
            position: absolute;
            top: 120%;
            right: 0;
            width: 220px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .profile-popup.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-info {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .profile-name {
            color: #1f2937;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .profile-email {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .popup-logout-btn {
            width: 100%;
            padding: 12px 15px;
            text-align: left;
            background: none;
            border: none;
            color: #dc2626;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .popup-logout-btn:hover {
            background-color: #f3f4f6;
        }

        .popup-logout-btn i {
            font-size: 0.9rem;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            transition: margin-left 0.3s ease;
            padding: 20px;
            background-color: #f0f2f5;
        }

        .main-content.shrink {
            margin-left: 80px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                padding: 10px;
            }

            .main-content {
                margin-left: 60px;
            }

            .sidebar.shrink {
                width: 60px;
            }

            .main-content.shrink {
                margin-left: 60px;
            }
        }

        .dashboard-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: #1a4d2e;
            font-size: 1.8rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #4b5563;
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 600;
            color: #1a4d2e;
        }

        .chart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-card h2 {
            color: #1a4d2e;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .order-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .order-item:hover {
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .notification-item {
            padding: 15px;
            border-left: 4px solid #1a4d2e;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 0 8px 8px 0;
        }

        .notification-message {
            color: #1f2937;
            margin-bottom: 5px;
        }

        .notification-time {
            color: #6b7280;
        }

        .farm-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .farm-card:hover {
            transform: translateY(-5px);
        }

        .farm-card h3 {
            color: #1a4d2e;
            margin-bottom: 15px;
        }

        .view-farm {
            display: inline-block;
            padding: 8px 16px;
            background: #1a4d2e;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 15px;
            transition: background 0.3s ease;
        }

        .view-farm:hover {
            background: #2d6a4f;
        }

        .footer {
            background: white;
            color: #4b5563;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
            border-radius: 12px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .logout-btn {
            padding: 10px 20px;
            background: linear-gradient(to right, #dc2626, #ef4444);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 10px rgba(220,38,38,0.2);
        }
      
        .popup-logout-btn {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            padding: 15px 20px;
            border-radius: 0 0 15px 15px;
            transition: all 0.3s ease;
        }

        .popup-logout-btn:hover {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        @media (max-width: 1024px) {
            .chart-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
        }

        .orders-section {
            padding: 50px;
        }

        .orders-section h1 {
            color: #1a4d2e;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .empty-orders {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-orders i {
            font-size: 48px;
            color: #1a4d2e;
            margin-bottom: 15px;
        }

        .empty-orders p {
            color: #666;
            margin-bottom: 20px;
        }

        .browse-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #1a4d2e;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s;
        }

        .browse-btn:hover {
            background: #2d6a4f;
        }

        .orders-list {
            display: grid;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 25px;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .order-header {
            padding: 25px;
            background: linear-gradient(to right, #1a4d2e, #2d6a4f);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .order-info h3 {
            color: white;
            font-size: 1.2em;
            margin-bottom: 8px;
        }

        .order-date {
            color: rgba(255,255,255,0.8);
            font-size: 0.95em;
        }

        .order-status {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .order-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-info h3 {
            color: white;
            margin-bottom: 5px;
        }

        .order-date {
            color: #fff;
            font-size: 0.9em;
        }

        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .order-status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .order-status.processing {
            background: #cce5ff;
            color: #004085;
        }

        .order-status.shipped {
            background: #d4edda;
            color: #155724;
        }

        .order-status.delivered {
            background: #c3e6cb;
            color: #155724;
        }

        .order-status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-details {
            padding: 20px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-row span:first-child {
            min-width: 120px;
            color: #666;
        }

        .detail-row {
            align-items: flex-start;
            padding: 8px 0;
        }

        .detail-row span:last-child {
            color: #333;
            font-weight: 500;
        }

        .product-list {
            font-size: 0.9em;
            line-height: 1.6;
            color: #4b5563;
            max-width: 400px;
            word-wrap: break-word;
        }

        .product-list-item {
            display: block;
            margin-bottom: 5px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .product-list-item:last-child {
            border-bottom: none;
        }

        .product-quantity {
            color: #1a4d2e;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .orders-section {
                padding: 10px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-status {
                margin-top: 10px;
            }
        }

        .order-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .cancel-order-btn {
            padding: 8px 16px;
            background-color: #dc2626;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .cancel-order-btn:hover {
            background-color: #b91c1c;
        }

        .cancel-order-btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        .order-status.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            color: white;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification.success {
            background-color: #22c55e;
        }

        .notification.error {
            background-color: #ef4444;
        }

        .cancel-order-btn i.fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

         .modal-content {
            position: relative;
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            width: 90%;
            max-width: 700px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
            padding: 5px;
            line-height: 1;
        }

        .close-modal:hover {
            color: #dc2626;
        }

        .tracking-timeline {
            margin-top: 30px;
            position: relative;
            padding: 20px 0;
            transition: opacity 0.3s ease;
        }

        .tracking-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            position: relative;
            opacity: 0.5;
        }

        .tracking-step::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 40px;
            bottom: -30px;
            width: 2px;
            background-color: #e5e7eb;
        }

        .tracking-step:last-child::before {
            display: none;
        }

        .tracking-step.active {
            opacity: 1;
        }

        .tracking-step.completed {
            opacity: 1;
        }

        .step-icon {
            width: 40px;
            height: 40px;
            background-color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .tracking-step.completed .step-icon {
            background-color: #1a4d2e;
            color: white;
            transform: scale(1.1);
            transition: all 0.3s ease;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            width: 90%;
            max-width: 700px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .tracking-timeline {
            margin-top: 40px;
            position: relative;
            padding: 30px 0;
        }

        .tracking-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 40px;
            position: relative;
            opacity: 0.7;
            transition: all 0.3s ease;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1a4d2e, #2d6a4f);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            color: white;
            transition: all 0.3s ease;
        }

        .tracking-step.completed .step-icon {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            transform: scale(1.1);
        }

        .tracking-step.active .step-icon {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(26, 77, 46, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(26, 77, 46, 0); }
            100% { box-shadow: 0 0 0 0 rgba(26, 77, 46, 0); }
        }

        .step-info {
            flex: 1;
        }

        .step-info h4 {
            margin: 0;
            color:rgb(36, 85, 153);
        }

        .step-date {
            font-size: 0.9em;
            color: #6b7280;
            margin-top: 4px;
        }

        .track-order-btn {
            padding: 6px 12px;
            background-color: #1a4d2e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }

        .track-order-btn:hover {
            background-color: #2d6a4f;
        }

        .step-icon i {
            font-size: 1.2em;
        }

        .tracking-step.active .step-icon {
            box-shadow: 0 0 0 3px rgba(26, 77, 46, 0.2);
        }
        
        .tracking-step.active h4 {
            color: #fff;
        }
        .tracking-step.active .step-date {
            color: #fff;
        }
          

        .cancel-order-btn {
            padding: 10px 20px;
            background: linear-gradient(to right, #dc2626, #ef4444);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cancel-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220,38,38,0.3);
        }

        .track-order-btn {
            padding: 10px 20px;
            background: linear-gradient(to right, #1a4d2e, #2d6a4f);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 15px;
        }

        .track-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26,77,46,0.3);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="userindex.php" ><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse.php" ><i class="fas fa-store"></i><span>Browse Farms</span></a></li>
            <li><a href="cart.php" ><i class="fas fa-shopping-cart"></i><span>My Cart</span></a></li>
            <li><a href="orders.php" class="active" ><i class="fas fa-truck"></i><span>My Orders</span></a></li>
            <li><a href="favorite.php" ><i class="fas fa-heart"></i><span>Favorite Farms</span></a></li>
            <li><a href="events.php" ><i class="fas fa-calendar"></i><span>Farm Events</span></a></li>
            <li><a href="profile.php" ><i class="fas fa-user"></i><span>Profile</span></a></li>
            <!-- <li><a href="settings.php" ><i class="fas fa-cog"></i><span>Settings</span></a></li> -->
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="container">
            <div class="user-section">
                <div class="profile-menu-container">
                    <div class="pro">
                        <div class="head">
                            <h2>FarmFolio</h2>
                        </div>
                        <div class="profile-icon" id="profileIcon">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <div class="profile-popup" id="profilePopup">
                        <div class="profile-info">
                        <p class="profile-name"><?php echo $_SESSION['username'];?></p>
                        <p class="profile-email"><?php echo $_SESSION['email'];?></p>
                        </div>
                        <button class="popup-logout-btn" onclick="window.location.href='../logout/logout.php'">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            </div>

            <div class="orders-section">
                <h1>My Orders</h1>
                
                <?php if (empty($orders)): ?>
                    <div class="empty-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <p>No orders found</p>
                        <a href="browse.php" class="browse-btn">Browse Products</a>
                    </div>
                <?php else: $i=0;?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h3>Order Date </h3>
                                        <span class="order-date">
                                            <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?>
                                        </span>
                                    </div>
                                    <div class="order-actions">
                                        <div class="order-status <?php echo strtolower($order['order_status']); ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </div>
                                        <?php if ($order['order_status'] == 'pending'): ?>
                                            <button class="cancel-order-btn" 
                                                    data-order-id="<?php echo $order['order_id']; ?>"
                                                    onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                                                Cancel Order
                                            </button>
                                        <?php endif; ?>
                                        <button class="track-order-btn" 
                                                onclick="trackOrder(<?php echo $order['order_id']; ?>)">
                                            <i class="fas fa-map-marker-alt"></i> Track Order
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="order-details">
                                    <div class="detail-row">
                                        <span>Products:</span>
                                        <span class="product-list"><?php echo htmlspecialchars($order['product_details']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Total Items:</span>
                                        <span><?php echo $order['total_items']; ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Total Amount:</span>
                                        <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Payment Method:</span>
                                        <span><?php echo strtoupper($order['payment_method']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Delivery Address:</span>
                                        <span><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>&copy; 2024 Farmfolio. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script src="profile.js"></script>
    <script>
    function cancelOrder(orderId) {
        if (!confirm('Are you sure you want to cancel this order?')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';

        fetch('orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cancel_order=1&order_id=${orderId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const orderCard = button.closest('.order-card');
                const statusDiv = orderCard.querySelector('.order-status');
                
                // Update status
                statusDiv.textContent = 'Cancelled';
                statusDiv.className = 'order-status cancelled';
                
                // Remove cancel button
                button.remove();
                
                // Show success message
                showNotification('Order cancelled . ', 'success');
            } else {
                showNotification(data.error || 'Failed to cancel order', 'error');
                button.disabled = false;
                button.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while cancelling the order', 'error');
            button.disabled = false;
            button.textContent = originalText;
        });
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    function trackOrder(orderId) {
        const modal = document.getElementById('trackingModal');
        modal.style.display = 'block';
        
        // Reset timeline state
        document.querySelectorAll('.tracking-step').forEach(step => {
            step.classList.remove('completed', 'active');
            step.querySelector('.step-date').textContent = '';
        });
        
        // Show loading state
        document.querySelector('.tracking-timeline').style.opacity = '0.5';
        
        // Fetch order tracking details
        fetch(`get_tracking.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                document.querySelector('.tracking-timeline').style.opacity = '1';
                updateTrackingTimeline(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to load tracking information', 'error');
                modal.style.display = 'none';
            });
    }

    function updateTrackingTimeline(data) {
        const steps = document.querySelectorAll('.tracking-step');
        const statusOrder = {
            'pending': 0,
            'processing': 1,
            'shipped': 2,
            'delivered': 3,
            'cancelled': -1
        };
        
        const currentStatusIndex = statusOrder[data.status];
        
        steps.forEach(step => {
            const stepStatus = step.dataset.status;
            const stepIndex = statusOrder[stepStatus];
            const dateElement = step.querySelector('.step-date');
            
            if (currentStatusIndex === -1 && stepStatus === 'pending') {
                // Show only pending for cancelled orders
                step.classList.add('completed');
                dateElement.textContent = data.pending_date;
                return;
            }
            
            if (stepIndex <= currentStatusIndex) {
                step.classList.add('completed');
                const dateKey = `${stepStatus}_date`;
                if (data[dateKey]) {
                    dateElement.textContent = data[dateKey];
                }
            }
            
            if (stepIndex === currentStatusIndex) {
                step.classList.add('active');
            }
        });
    }

    // Replace the existing modal close handlers with this code
    document.addEventListener('DOMContentLoaded', function() {
        // Get modal elements
        const modal = document.getElementById('trackingModal');
        const closeBtn = modal.querySelector('.close-modal');
        
        // Close modal when clicking the X button
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    </script>
    <div id="trackingModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" data-dismiss="modal">&times;</span>
            <h2>Order Tracking</h2>
            <div class="tracking-timeline">
                <div class="tracking-step" data-status="pending">
                    <div class="step-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="step-info">
                        <h4>Order Pending</h4>
                        <p class="step-date"></p>
                    </div>
                </div>
                <div class="tracking-step" data-status="processing">
                    <div class="step-icon"><i class="fas fa-cog"></i></div>
                    <div class="step-info">
                        <h4>Picked Up the Order</h4>
                        <p class="step-date"></p>
                    </div>
                </div>
                <div class="tracking-step" data-status="shipped">
                    <div class="step-icon"><i class="fas fa-truck"></i></div>
                    <div class="step-info">
                        <h4>The Order is Ready to Deliver</h4>
                        <p class="step-date"></p>
                    </div>
                </div>
                <div class="tracking-step" data-status="delivered">
                    <div class="step-icon"><i class="fas fa-box-open"></i></div>
                    <div class="step-info">
                        <h4>Delivered</h4>
                        <p class="step-date"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>