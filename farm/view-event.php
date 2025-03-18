<?php
require_once '../databse/connect.php';
session_start();
// Check farm status
$is_farm_active = false;
if(isset($_SESSION['userid'])){
    $userid = $_SESSION['userid'];
    $farm = "SELECT * FROM tbl_farms WHERE user_id=$userid";
    $result = mysqli_query($conn, $farm);
    $row = $result->fetch_assoc();
    
    if($row && $row['status'] == 'active') {
        $is_farm_active = true;
        $farm_id = $row['farm_id'];
    }
}

if (!isset($_GET['id'])) {
    header("Location: events-grid.php");
    exit();
}

$event_id = $_GET['id'];

// Fetch event details
$event_query = "SELECT * FROM tbl_events WHERE event_id = ?";
$stmt = $conn->prepare($event_query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event_result = $stmt->get_result();
$event = $event_result->fetch_assoc();

// Fetch participants
$participants_query = "SELECT p.*, u.username,u.email
                      FROM tbl_participants p 
                      JOIN tbl_signup u ON p.user_id = u.userid 
                      WHERE p.event_id = ?";
$stmt = $conn->prepare($participants_query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$participants_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="farm.css">
    <style>
       
        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-btn {
            background: #1a4d2e;
            color: white;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn {
            background: #1a4d2e;
            color: white;
        }

        .cancel-btn {
            background: #666;
            color: white;
        }

        .inactive-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 70vh;
            text-align: center;
            color: #666;
        }

        .inactive-message h2 {
            font-size: 24px;
            margin-bottom: 16px;
            color: #1a4d2e;
        }

        .inactive-message p {
            font-size: 16px;
            max-width: 600px;
            line-height: 1.6;
        }

        .inactive-icon {
            font-size: 48px;
            color: #1a4d2e;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">
            <h2>Farmfolio</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="farm.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="product.php" ><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="image.php"><i class="fas fa-image"></i><span>Farm Images</span></a></li>
            <li><a href="event.php"><i class="fas fa-calendar"></i><span>Events</span></a></li>
            <li><a href="review.php" class="active"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="orders.php"><i class="fas fa-truck"></i><span>Orders</span></a></li>
            <li><a href="about.php"><i class="fas fa-info-circle"></i><span>Farm Details </span></a></li>
            <!-- <li><a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a></li> -->
        </ul>
    </nav>

    <div class="main-content">
    <div class="dashboard-header">
                <?php if(isset($row['farm_name'])&&isset($_SESSION['username'])){?>
                <h1><?php echo $row['farm_name'];?>Farm</h1>
                <div class="user-section">
                    <span>Welcome, <?php echo $_SESSION['username'];?></span>
                    <a href="http://localhost/mini%20project/logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div>
                <?php }else{?>
                    <h1>Farm Dashboard</h1>
                <div class="user-section">
                    <span>Welcome,</span>
                    <a href="http://localhost/mini%20project/logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div><?php }?>
            </div>
        <?php if($is_farm_active): ?>
            <div class="container mt-4">
        <h2>Event Details</h2>
        <div class="card mb-4">
            <div class="card-body">
                <h3><?php echo $event['event_name']; ?></h3>
                <p><?php echo $event['event_description']; ?></p>
                <p><strong>Date:</strong> <?php echo $event['event_date']; ?></p>
                <!-- <p><strong>Location:</strong> <?php echo $event['event_location']; ?></p> -->
            </div>
        </div>

        <h3>Registered Participants</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Registration Date</th>
                    <!-- <th>Status</th>
                    <th>Action</th> -->
                </tr>
            </thead>
            <tbody>
                <?php while ($participant = $participants_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $participant['username']; ?></td>
                    <td><?php echo $participant['email']; ?></td>
                    <td><?php echo $participant['registration_date']; ?></td>
                    <!-- <td><?php echo $participant['status']; ?></td>
                    <td>
                        <select class="form-control status-select" 
                                data-participant-id="<?php echo $participant['participant_id']; ?>">
                            <option value="Pending" <?php echo $participant['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Confirmed" <?php echo $participant['status'] == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Cancelled" <?php echo $participant['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </td> -->
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="../farm/event.php" class="btn btn-secondary">Back to Events</a>
    </div>
                

        <?php else: ?>
            <div class="inactive-message">
                <i class="fas fa-store-slash inactive-icon"></i>
                <h2>Farm Not Active</h2>
                <p>Your farm is currently inactive. Please contact the administrator to activate your farm account before managing products.</p>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.status-select').change(function() {
                const participantId = $(this).data('participant-id');
                const newStatus = $(this).val();
                
                $.ajax({
                    url: 'update-status.php',
                    method: 'POST',
                    data: {
                        participant_id: participantId,
                        status: newStatus
                    },
                    success: function(response) {
                        alert('Status updated successfully');
                    },
                    error: function() {
                        alert('Error updating status');
                    }
                });
            });
        });
    </script>
</body>
</html>
