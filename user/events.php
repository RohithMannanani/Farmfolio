<?php
//0 active product 1-inactive product
include '../databse/connect.php';
session_start();
if(!isset($_SESSION['username'])){
    header('location: ../login/login.php');
}
$user_id=$_SESSION['userid'];
// Modified query to show all active farms, even without images
$farms_query = "SELECT 
    f.farm_id,
    f.farm_name,
    f.location,
    f.description,
    f.created_at,
    f.status,
    COUNT(DISTINCT CASE WHEN p.status ='0' THEN p.product_id END) AS product_count,
    MIN(fi.path) as farm_image  -- Get first image if exists
FROM tbl_farms f
LEFT JOIN tbl_products p ON f.farm_id = p.farm_id
LEFT JOIN tbl_farm_image fi ON f.farm_id = fi.farm_id
WHERE f.status = 'active'
GROUP BY f.farm_id
ORDER BY f.created_at DESC";

$farms_result = mysqli_query($conn, $farms_query);
//favorite count
$fev_count = "SELECT COUNT(favorite_id) AS fev_count FROM tbl_favorites WHERE user_id = $user_id";

// Execute the query
$fev_result = $conn->query($fev_count);
// Check if the query was successful
if ($fev_result) {
    // Fetch the result
    $fev = $fev_result->fetch_assoc();
} else {
    // Handle error if the query failed
    echo "Error: " . $conn->error;
}


// Check if farm is favorited by current user
function isFarmFavorited($conn, $farm_id, $user_id) {
    $query = "SELECT * FROM tbl_favorites WHERE farm_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $farm_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

// Get all upcoming events from active farms
$events_query = "SELECT e.*, f.farm_name, f.location, f.farm_id,
                COUNT(p.participant_id) as participant_count,
                (SELECT MIN(path) FROM tbl_farm_image WHERE farm_id = f.farm_id) as farm_image
                FROM tbl_events e 
                JOIN tbl_farms f ON e.farm_id = f.farm_id 
                LEFT JOIN tbl_participants p ON e.event_id = p.event_id 
                WHERE e.status = '1' 
                AND e.event_date >= CURDATE()
                AND f.status = 'active'
                GROUP BY e.event_id 
                ORDER BY e.event_date ASC";

$events_result = mysqli_query($conn, $events_query);
?>

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Events - Farmfolio</title>
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
            grid-template-columns: repeat(3, minmax(240px, 1fr));
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
        .stat-card .order {
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
            position: relative;
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
        #dynamic-content {
            flex: 1;
            padding: 20px;
            box-sizing: border-box;
        }

        .recent-farms {
            padding: 20px;
            margin-bottom: 30px;
        }

        .recent-farms h2 {
            color: #1a4d2e;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .farms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .favorite-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .favorite-btn i {
            color: #d1d5db;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .favorite-btn:hover {
            transform: scale(1.1);
        }

        .favorite-btn.active i {
            color: #e63946;
        }

        .favorite-btn:hover i {
            color: #e63946;
        }

        .favorite-btn.active:hover i {
            color: #d1d5db;
        }

        .farm-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: #f3f4f6;
            position: relative;
        }

        .farm-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .farm-details {
            padding: 20px;
        }

        .farm-details h3 {
            color: #1a4d2e;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .location {
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .location i {
            color: #1a4d2e;
        }

        .description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .farm-stats {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .product-count {
            background: #e8f5e9;
            color: #1a4d2e;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-farm {
            display: inline-block;
            padding: 8px 16px;
            background: #1a4d2e;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s ease;
            width: 100%;
            text-align: center;
        }

        .view-farm:hover {
            background: #2d6a4f;
        }

        .no-farms {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 1.1rem;
            grid-column: 1 / -1;
        }

        @media (max-width: 768px) {
            .farms-grid {
                grid-template-columns: 1fr;
                padding: 10px;
            }
        }

        .no-image {
            width: 100%;
            height: 100%;
            background: #f3f4f6;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6b7280;
        }

        .no-image i {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .no-image p {
            font-size: 0.9rem;
        }

        /* Ensure header/navbar has higher z-index */
        .header, 
        .navbar,
        .profile-section {
            z-index: 100; /* Higher z-index for header elements */
            position: relative;
        }

        .events-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .events-header {
            margin-bottom: 30px;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            background-image: linear-gradient(to right, rgba(255,255,255,0.95), rgba(255,255,255,0.98)), 
                              url('https://img.freepik.com/free-photo/green-field-with-sun_1160-878.jpg');
            background-size: cover;
            background-position: center;
        }

        .events-header::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #1a4d2e, #3E885B);
        }

        .events-header h1 {
            color: #1a4d2e;
            margin-bottom: 10px;
            font-size: 2rem;
            letter-spacing: -0.5px;
            position: relative;
            display: inline-block;
        }

        .events-header h1::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -5px;
            width: 40px;
            height: 3px;
            background: #3E885B;
        }

        .events-header p {
            color: #4b5563;
            font-size: 1.1rem;
        }

        .events-list {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .event-item {
            background: white;
            border-radius: 12px;
            padding: 25px;
            display: flex;
            gap: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.03);
        }

        .event-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: rgba(0,0,0,0);
        }

        .event-item::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 100%;
            background: linear-gradient(to right, #1a4d2e, #3E885B);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .event-item:hover::after {
            transform: scaleX(1);
        }

        .event-date {
            min-width: 110px;
            text-align: center;
            padding: 20px 15px;
            background: linear-gradient(to bottom, #1a4d2e, #3E885B);
            color: white;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 15px rgba(26, 77, 46, 0.2);
            position: relative;
            overflow: hidden;
        }

        .event-date::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.1), rgba(255,255,255,0));
            transform: translateX(-100%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }

        .date-day {
            font-size: 2.4rem;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .date-month {
            font-size: 1.2rem;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
            letter-spacing: 1px;
        }

        .date-year {
            font-size: 1rem;
            opacity: 0.9;
        }

        .event-details {
            flex: 1;
        }

        .event-title {
            font-size: 1.5rem;
            color: #1a4d2e;
            margin-bottom: 12px;
            line-height: 1.3;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .event-item:hover .event-title {
            color: #3E885B;
        }

        .event-farm {
            color: #4b5563;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.05rem;
        }

        .event-farm i {
            color: #3E885B;
            font-size: 1.1rem;
        }

        .event-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 18px;
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            border-left: 4px solid #e8f5e9;
        }

        .event-item:hover .event-info {
            background: #f0f9f0;
            border-left-color: #3E885B;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4b5563;
            transition: all 0.2s ease;
            padding: 8px;
            border-radius: 6px;
        }

        .info-item:hover {
            background: rgba(62, 136, 91, 0.1);
            transform: translateX(5px);
        }

        .info-item i {
            color: #3E885B;
            width: 18px;
            font-size: 1.1rem;
        }

        .event-description {
            color: #4b5563;
            margin: 18px 0;
            line-height: 1.7;
            font-size: 1.05rem;
            padding: 15px;
            padding-left: 18px;
            border-left: 4px solid #e8f5e9;
            background: rgba(248, 250, 252, 0.5);
            border-radius: 0 8px 8px 0;
            transition: all 0.3s ease;
        }

        .event-item:hover .event-description {
            border-left-color: #3E885B;
            background: rgba(240, 249, 240, 0.5);
        }

        .event-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
        }

        .participant-count {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4b5563;
            padding: 8px 15px;
            background: #f9fafb;
            border-radius: 20px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .event-item:hover .participant-count {
            background: #f0f9f0;
        }

        .participant-count i {
            color: #3E885B;
        }

        .register-btn {
            background: linear-gradient(to right, #1a4d2e, #2d6a4f);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(26, 77, 46, 0.15);
            z-index: 1;
        }

        .register-btn::before {
            content: '\f274';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
        }

        .register-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, #2d6a4f, #1a4d2e);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(45, 106, 79, 0.25);
        }

        .register-btn:hover::after {
            opacity: 1;
        }

        .registered-btn {
            background: #e8f5e9;
            color: #1a4d2e;
            padding: 12px 25px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: not-allowed;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(232, 245, 233, 0.5);
        }

        .registered-btn:hover {
            background: #d5ebd7;
        }

        .no-events {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            background-image: linear-gradient(to bottom, rgba(255,255,255,0.98), rgba(255,255,255,0.95)), 
                              url('https://img.freepik.com/free-photo/green-field-with-sun_1160-878.jpg');
            background-size: cover;
            background-position: center;
        }

        .no-events i {
            font-size: 60px;
            color: #3E885B;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-events h3 {
            font-size: 1.6rem;
            color: #1a4d2e;
            margin-bottom: 15px;
        }

        .no-events p {
            color: #4b5563;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Animation for event items */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .event-item {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }
        
        .event-item:nth-child(1) {animation-delay: 0.1s;}
        .event-item:nth-child(2) {animation-delay: 0.2s;}
        .event-item:nth-child(3) {animation-delay: 0.3s;}
        .event-item:nth-child(4) {animation-delay: 0.4s;}
        .event-item:nth-child(5) {animation-delay: 0.5s;}

        /* Custom scrollbar for webkit browsers */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #3E885B;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #1a4d2e;
        }

        @media (max-width: 768px) {
            .event-item {
                flex-direction: column;
            }

            .event-date {
                align-self: flex-start;
                flex-direction: row;
                width: fit-content;
                padding: 10px 15px;
                gap: 10px;
                margin-bottom: 15px;
            }

            .date-day {
                font-size: 1.8rem;
                margin-bottom: 0;
            }

            .date-month, .date-year {
                font-size: 0.9rem;
            }

            .event-info {
                grid-template-columns: 1fr;
            }

            .event-actions {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .register-btn, .registered-btn {
                width: 100%;
                justify-content: center;
            }

            .sidebar {
                width: 60px;
            }

            .sidebar-menu span {
                display: none;
            }

            .main-content {
                margin-left: 60px;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 0;
                padding: 0;
                overflow: hidden;
            }

            .main-content {
                margin-left: 0;
            }

            .pro {
                justify-content: flex-end;
            }

            .head {
                display: none;
            }

            .events-header {
                padding: 15px;
            }

            .events-header h1 {
                font-size: 1.5rem;
            }

            .event-item {
                padding: 15px;
            }

            .event-title {
                font-size: 1.2rem;
            }
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
            <li><a href="orders.php" ><i class="fas fa-truck"></i><span>My Orders</span></a></li>
            <li><a href="favorite.php" ><i class="fas fa-heart"></i><span>Favorite Farms</span></a></li>
            <li><a href="events.php" class="active" ><i class="fas fa-calendar"></i><span>Farm Events</span></a></li>
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
                       <button class="popup-logout-btn"  onclick="window.location.href='../logout/logout.php'">
                       <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            </div>

             <!-- Stats Grid  -->
           

        <!-- Footer -->
        <!-- <div class="footer">
            <p>&copy; 2024 Farmfolio. All rights reserved.</p>
        </div> -->
    </div>

    <script>

        document.addEventListener('DOMContentLoaded', function() {
            const profileIcon = document.getElementById('profileIcon');
            const profilePopup = document.getElementById('profilePopup');
            let timeoutId;

            function showPopup() {
                profilePopup.classList.add('show');
            }

            function hidePopup() {
                profilePopup.classList.remove('show');
            }

            profileIcon.addEventListener('mouseenter', () => {
                clearTimeout(timeoutId);
                showPopup();
            });

            profileIcon.addEventListener('mouseleave', () => {
                timeoutId = setTimeout(() => {
                    if (!profilePopup.matches(':hover')) {
                        hidePopup();
                    }
                }, 300);
            });

            profilePopup.addEventListener('mouseenter', () => {
                console.log("Mouse entered icon");
                clearTimeout(timeoutId);
                showPopup(); 
            });

            profilePopup.addEventListener('mouseleave', () => {
                
                timeoutId = setTimeout(hidePopup, 300);
            });

            profileIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                profilePopup.classList.toggle('show');
            });

            document.addEventListener('click', (e) => {
                if (!profileIcon.contains(e.target) && !profilePopup.contains(e.target)) {
                    hidePopup();
                }
            });
        })

           </script>

    <script>
    async function addToFavorites(button, farmId) {
        try {
            // Add loading state
            button.classList.add('loading');
            
            console.log('Managing favorites for farm:', farmId);
            
            const response = await fetch('add_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `farm_id=${farmId}`
            });

            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('Response data:', data);
            
            // Remove loading state
            button.classList.remove('loading');
            
            if (data.success) {
                // Toggle the active class based on the action
                if (data.action === 'added') {
                    button.classList.add('active');
                } else if (data.action === 'removed') {
                    button.classList.remove('active');
                }
                
                // Update favorite count if it exists
                const favoriteCount = document.querySelector('.value');
                if (favoriteCount) {
                    let count = parseInt(favoriteCount.textContent);
                    count = data.action === 'added' ? count + 1 : count - 1;
                    favoriteCount.textContent = count;
                }
            } else {
                throw new Error(data.message || 'Error managing favorites');
            }
        } catch (error) {
            // Remove loading state
            button.classList.remove('loading');
            
            console.error('Error:', error);
            alert(error.message || 'An error occurred while managing favorites');
        }
    }
    </script>

    <div class="events-container" style="margin-top: 40px;">
        <div class="events-header">
            <h1>Upcoming Farm Events</h1>
            <p>Join exciting farm events and activities in your area</p>
        </div>

        <div class="events-list">
            <?php 
            if(mysqli_num_rows($events_result) > 0):
                $event_count = 0;
                while($event = mysqli_fetch_assoc($events_result)):
                    $event_count++;
                    // Check if user is already registered
                    $check_registration = "SELECT * FROM tbl_participants 
                                        WHERE event_id = ? AND user_id = ?";
                    $stmt = $conn->prepare($check_registration);
                    $stmt->bind_param("ii", $event['event_id'], $_SESSION['userid']);
                    $stmt->execute();
                    $is_registered = $stmt->get_result()->num_rows > 0;
            ?>
                <div class="event-item" style="animation-delay: <?php echo (0.1 * $event_count); ?>s">
                    <div class="event-date">
                        <span class="date-day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                        <span class="date-month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                        <span class="date-year"><?php echo date('Y', strtotime($event['event_date'])); ?></span>
                    </div>
                    <div class="event-details">
                        <h3 class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                        <div class="event-farm">
                            <i class="fas fa-store"></i>
                            <span><?php echo htmlspecialchars($event['farm_name']); ?></span>
                        </div>
                        <div class="event-info">
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo date('l, F d, Y', strtotime($event['event_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($event['location']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-users"></i>
                                <span><?php echo $event['participant_count']; ?> participants</span>
                            </div>
                        </div>
                        <p class="event-description">
                            <?php echo htmlspecialchars($event['event_description']); ?>
                        </p>
                        <div class="event-actions">
                            <div class="participant-count">
                                <i class="fas fa-calendar-check"></i>
                                <span>Event Date: <?php echo date('l, F d, Y', strtotime($event['event_date'])); ?></span>
                            </div>
                            <?php if($is_registered): ?>
                                <button class="registered-btn">
                                    <i class="fas fa-check"></i>
                                    Already Registered
                                </button>
                            <?php else: ?>
                                <a href="farm_details.php?id=<?php echo $event['farm_id']; ?>#events" 
                                   class="register-btn">
                                    Register Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="no-events">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Upcoming Events</h3>
                    <p>There are no upcoming farm events at the moment. Please check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>