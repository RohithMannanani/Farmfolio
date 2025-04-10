<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['username'])){
    header('location: ../login/login.php');
}

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

// Only proceed with other queries if farm is active
if($is_farm_active) {
    // First, get current date
    $current_date = date('Y-m-d');
    
    // Update status of expired events
    $update_expired_events = "UPDATE tbl_events 
                            SET status = '0' 
                            WHERE farm_id = ? 
                            AND event_date < ? 
                            AND status = '1'";
    $stmt = $conn->prepare($update_expired_events);
    $stmt->bind_param("is", $farm_id, $current_date);
    $stmt->execute();

    // Get all events regardless of status
    $events_query = "SELECT *, 
                    DATE_FORMAT(event_date, '%Y-%m-%d') as formatted_date 
                    FROM tbl_events 
                    WHERE farm_id = ? 
                    ORDER BY event_date DESC";
    $stmt = $conn->prepare($events_query);
    $stmt->bind_param("i", $farm_id);
    $stmt->execute();
    $events_result = $stmt->get_result();

    // Add function to check if user is already registered
    function isUserRegistered($conn, $event_id, $user_id) {
        $query = "SELECT * FROM tbl_participants 
                  WHERE event_id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $event_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}

// Handle event submission
if(isset($_POST['add_event'])) {
    $event_name = mysqli_real_escape_string($conn, $_POST['event_name']);
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $event_description = mysqli_real_escape_string($conn, $_POST['event_description']);
    
    $insert_query = "INSERT INTO tbl_events (farm_id, event_name, event_date, event_description) 
                     VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    
    if($stmt) {
        $stmt->bind_param("isss", $farm_id, $event_name, $event_date, $event_description);
        if($stmt->execute()) {
            header("Location: event.php?success=1");
            exit();
        } else {
            header("Location: event.php?error=" . urlencode($stmt->error));
            exit();
        }
    }
}

// Handle event update
if(isset($_POST['edit_event'])) {
    $event_id = mysqli_real_escape_string($conn, $_POST['event_id']);
    $event_name = mysqli_real_escape_string($conn, $_POST['event_name']);
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $event_description = mysqli_real_escape_string($conn, $_POST['event_description']);
    
    $update_query = "UPDATE tbl_events 
                    SET event_name = ?, event_date = ?, event_description = ? 
                    WHERE event_id = ? AND farm_id = ?";
    $stmt = $conn->prepare($update_query);
    
    if($stmt) {
        $stmt->bind_param("sssii", $event_name, $event_date, $event_description, $event_id, $farm_id);
        if($stmt->execute()) {
            echo "<script>alert('Event updated successfully!');</script>";
        } else {
            echo "<script>alert('Error updating event: " . $stmt->error . "');</script>";
        }
    }
}

// Handle event status toggle
if(isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $event_id = intval($_GET['id']);
    $current_status = $_GET['status'];
    
    // Toggle the status ('1' to '0' or '0' to '1')
    $new_status = ($current_status == '1') ? '0' : '1';
    
    $update_query = "UPDATE tbl_events SET status = ? WHERE event_id = ? AND farm_id = ?";
    $stmt = $conn->prepare($update_query);
    
    if($stmt) {
        $stmt->bind_param("sii", $new_status, $event_id, $farm_id);
        if($stmt->execute()) {
            echo json_encode([
                "success" => true, 
                "message" => "Event " . ($new_status == '1' ? "activated" : "deactivated") . " successfully."
            ]);
        } else {
            echo json_encode([
                "success" => false, 
                "message" => "Error updating status: " . $stmt->error
            ]);
        }
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Farm Events </title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="farm.css">
    <style>
        .product-container {
            padding: 20px;
            max-width: 1200px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .add-product-btn {
            padding: 10px 20px;
            background: #1a4d2e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-product-btn:hover {
            background: #2d6a4f;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-details {
            padding: 15px;
        }

        .product-title {
            font-size: 1.1em;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-price {
            color: #2563eb;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-stock {
            color: #666;
            font-size: 0.9em;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

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

        .events-container {
            padding: 20px;
            max-width: 1200px;
        }

        .events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .add-event-btn {
            padding: 10px 20px;
            background: #1a4d2e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .event-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .event-date {
            background: #1a4d2e;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }

        .event-details {
            padding: 15px;
        }

        .event-details h3 {
            margin-bottom: 10px;
            color: #1a4d2e;
        }

        .event-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .inactive-event {
            opacity: 0.7;
            background-color: #f8f8f8;
            border: 1px solid #ddd;
        }

        .activate-btn {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .deactivate-btn {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .activate-btn:hover {
            background-color: #218838;
        }

        .deactivate-btn:hover {
            background-color: #c82333;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn i {
            font-size: 0.9em;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            position: relative;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .close-alert {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }

        .events-filters {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #1a4d2e;
            background: white;
            color: #1a4d2e;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: #1a4d2e;
            color: white;
        }

        .inactive-event {
            opacity: 0.7;
            background-color: #f8f8f8;
            border: 1px solid #ddd;
        }

        .inactive-event .event-date {
            background: #666;
        }

        .event-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-bottom: 10px;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
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
            <li><a href="event.php" class="active"><i class="fas fa-calendar"></i><span>Events</span></a></li>
            <li><a href="review.php"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="orders.php"><i class="fas fa-truck"></i><span>Orders</span></a></li>
            <li><a href="about.php"><i class="fas fa-info-circle"></i><span>Farm Details </span></a></li>
            <!-- <li><a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a></li> -->
        </ul>
    </nav>

    <div class="main-content">
    <div class="dashboard-header">
                <?php if(isset($row['farm_name'])&&isset($_SESSION['username'])){?>
                <h1><?php echo $row['farm_name'];?></h1>
                <div class="user-section">
                    <span>Welcome, <?php echo $_SESSION['username'];?></span>
                    <a href="../logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div>
                <?php }else{?>
                    <h1>Farm Dashboard</h1>
                <div class="user-section">
                    <span>Welcome,</span>
                    <a href="../logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div><?php }?>
            </div>
<?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success">
        Event added successfully!
        <button type="button" class="close-alert">&times;</button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
    <div class="alert alert-error">
        Error: <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="close-alert">&times;</button>
    </div>
<?php endif; ?>
        <?php if($is_farm_active): ?>
            <div class="events-container">
                <div class="events-header">
                    <h2>Farm Events</h2>
                    <button class="add-event-btn" onclick="openModal()">
                        <i class="fas fa-plus"></i> Add New Event
                    </button>
                </div>

                <div class="events-filters">
                    <button class="filter-btn active" data-filter="all">All Events</button>
                    <button class="filter-btn" data-filter="active">Active Events</button>
                    <button class="filter-btn" data-filter="inactive">Deactivated Events</button>
                </div>

                <div class="events-grid">
                    <?php while($event = mysqli_fetch_assoc($events_result)): ?>
                        <div class="event-card <?php echo $event['status'] == '0' ? 'inactive-event' : ''; ?>">
                            <div class="event-date">
                                <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                            </div>
                            <div class="event-details">
                                <span class="event-status <?php echo $event['status'] == '1' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $event['status'] == '1' ? 'Active' : 'Deactivated'; ?>
                                </span>
                                <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                <p><?php echo htmlspecialchars($event['event_description']); ?></p>
                                <div class="event-actions">
                                    <button class="edit-btn" onclick="editEvent(<?php echo $event['event_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="action-btn <?php echo $event['status'] == '1' ? 'deactivate-btn' : 'activate-btn'; ?>" 
                                            onclick="toggleEventStatus(<?php echo $event['event_id']; ?>, '<?php echo $event['status']; ?>')">
                                        <i class="fas <?php echo $event['status'] == '1' ? 'fa-times' : 'fa-check'; ?>"></i>
                                        <?php echo $event['status'] == '1' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                    <a href="view-event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-info btn-sm">View</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Add/Edit Event Modal -->
            <div class="modal" id="eventModal">
                <div class="modal-content">
                    <h2 id="modalTitle">Add New Event</h2>
                    <form id="eventForm" method="POST">
                        <input type="hidden" id="event_id" name="event_id">
                        <div class="form-group">
                            <label for="event_name">Event Name*</label>
                            <input type="text" id="event_name" name="event_name" required>
                        </div>
                        <div class="form-group">
                            <label for="event_date">Event Date*</label>
                            <input type="date" id="event_date" name="event_date" required>
                        </div>
                        <div class="form-group">
                            <label for="event_description">Description</label>
                            <textarea id="event_description" name="event_description" rows="4"></textarea>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                            <button type="submit" name="add_event" class="save-btn" id="submitBtn">Save Event</button>
                            <button type="submit" name="edit_event" class="save-btn" id="editBtn" style="display: none;">Update Event</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="inactive-message">
                <i class="fas fa-store-slash inactive-icon"></i>
                <h2>Farm Not Active</h2>
                <p>Your farm is currently inactive. Please contact the administrator to activate your farm account before managing products.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function openModal(isEdit = false) {
        document.getElementById('modalTitle').textContent = isEdit ? 'Edit Event' : 'Add New Event';
        document.getElementById('submitBtn').style.display = isEdit ? 'none' : 'block';
        document.getElementById('editBtn').style.display = isEdit ? 'block' : 'none';
        document.getElementById('eventModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('eventModal').classList.remove('show');
        document.getElementById('eventForm').reset();
    }

    async function editEvent(eventId) {
        try {
            const response = await fetch(`get_event.php?id=${eventId}`);
            const event = await response.json();
            
            document.getElementById('event_id').value = event.event_id;
            document.getElementById('event_name').value = event.event_name;
            document.getElementById('event_date').value = event.event_date;
            document.getElementById('event_description').value = event.event_description;
            
            openModal(true);
        } catch (error) {
            alert('Error loading event details');
        }
    }

    // Set minimum date to today
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('event_date').min = today;
    });

    async function toggleEventStatus(eventId, currentStatus) {
        const action = currentStatus == '1' ? 'deactivate' : 'activate';
        if (confirm(`Are you sure you want to ${action} this event?`)) {
            try {
                const response = await fetch(`event.php?toggle_status=1&id=${eventId}&status=${currentStatus}`);
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    window.location.reload(); // Reload to show updated status
                } else {
                    alert("Error: " + result.message);
                }
            } catch (error) {
                alert("Error: " + error.message);
            }
        }
    }

    // Handle alert dismissal
    const closeButtons = document.querySelectorAll('.close-alert');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const alert = this.parentElement;
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.filter-btn');
        const eventCards = document.querySelectorAll('.event-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                const filter = button.dataset.filter;

                eventCards.forEach(card => {
                    const isInactive = card.classList.contains('inactive-event');
                    
                    switch(filter) {
                        case 'all':
                            card.style.display = 'block';
                            break;
                        case 'active':
                            card.style.display = !isInactive ? 'block' : 'none';
                            break;
                        case 'inactive':
                            card.style.display = isInactive ? 'block' : 'none';
                            break;
                    }
                });
            });
        });
    });
</script>
    
</body>
</html>