<?php
require_once '../databse/connect.php';
session_start();

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
</head>
<body>
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
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($participant = $participants_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $participant['username']; ?></td>
                    <td><?php echo $participant['email']; ?></td>
                    <td><?php echo $participant['registration_date']; ?></td>
                    <td><?php echo $participant['status']; ?></td>
                    <td>
                        <select class="form-control status-select" 
                                data-participant-id="<?php echo $participant['participant_id']; ?>">
                            <option value="Pending" <?php echo $participant['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Confirmed" <?php echo $participant['status'] == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Cancelled" <?php echo $participant['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="../farm/event.php" class="btn btn-secondary">Back to Events</a>
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