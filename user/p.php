<?php 
session_start();
include '../databse/connect.php';

// Ensure user is logged in
// if (!isset($_SESSION['userid'])) {
//     die("Unauthorized access.");
// }

$userid =36;
$errors = [];
$data = [];

// Fetch existing user data
$stmt = "SELECT tbl_signup.username, tbl_signup.email, mobile, house, state, district, pin FROM tbl_signup 
         INNER JOIN tbl_login ON tbl_signup.userid = tbl_login.userid 
         WHERE tbl_signup.userid = ?";
$query = $conn->prepare($stmt);
if (!$query) {
    die("Query preparation failed: " . $conn->error);
}
$query->bind_param("i", $userid);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc() ?? [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    function validate($field, $value, $pattern, $errorMessage) {
        global $errors, $data;
        $value = trim($value);
        if (!preg_match($pattern, $value)) {
            $errors[$field] = $errorMessage;
        } else {
            $data[$field] = htmlspecialchars($value);
        }
    }

    validate("username", $_POST["username"], "/^[A-Za-z][A-Za-z\s.'-]{1,49}$/", "Invalid username.");
    validate("mobile", $_POST["mobile"], "/^[6-9]\d{9}$/", "Invalid mobile number.");
    validate("house", $_POST["house"], "/^[a-zA-Z0-9 .,-]+$/", "Invalid house name.");
    validate("pin", $_POST["pin"], "/^\d{6}$/", "Invalid PIN code.");

    if (empty($_POST["state"])) {
        $errors["state"] = "State is required.";
    } else {
        $data["state"] = htmlspecialchars($_POST["state"]);
    }

    if (empty($_POST["district"])) {
        $errors["district"] = "District is required.";
    } else {
        $data["district"] = htmlspecialchars($_POST["district"]);
    }

    // Validate password if provided
    if (!empty($_POST["password"])) {
        $password = $_POST["password"];
        $confirmPassword = $_POST["confirm-password"];
        if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/", $password)) {
            $errors["password"] = "Password must be at least 8 characters long and include a mix of uppercase, lowercase, numbers, and special characters.";
        } elseif ($password !== $confirmPassword) {
            $errors["confirm-password"] = "Passwords do not match.";
        } else {
            $data["password"] = $password;
        }
    }

    if (empty($errors)) {
        // Update user data in database
        $stmt1 = "UPDATE tbl_signup 
        SET username = ?, mobile = ?, house = ?, state = ?, district = ?, pin = ?
        WHERE userid = ?";

$query1 = $conn->prepare($stmt1);

if (!$query1) {
   die("Query preparation failed: " . $conn->error);
}

// Bind parameters (s = string, i = integer)
$query1->bind_param("ssssssi", 
   $data['username'], 
   $data['mobile'], 
   $data['house'], 
   $data['state'], 
   $data['district'], 
   $data['pin'],
   $userid
);

$stmt = "UPDATE tbl_login 
SET username=?
WHERE userid = ?";

$query = $conn->prepare($stmt);

if (!$query) {
die("Query preparation failed: " . $conn->error);
}

// Bind parameters (s = string, i = integer)
$query->bind_param("si", 
$data['username'], 
$userid
);

if ($query->execute()&&$query1->execute()) {
echo "Profile updated successfully!";
} else {
echo "Error updating profile: " . $query->error;
}

}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmfolio - Edit Profile</title>
    <link rel="stylesheet" href="c.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="edit.css">
</head>
<body>
    <div class="home-button-container">
        <a href="http://localhost/mini%20project/home/html5up-dimension/index.html" class="home-button">Home</a>
    </div>

    <div class="left-container">
        <h1 class="title">Edit Profile</h1>

        <form method="POST" action="" id="edit-profile-form">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? $user['username'] ?? '') ?>" required>
                <div class="error"><?= $errors['username'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="mobile"><i class="fas fa-phone"></i> Mobile Number</label>
                <input type="tel" id="mobile" name="mobile" value="<?= htmlspecialchars($_POST['mobile'] ?? $user['mobile'] ?? '') ?>" required>
                <div class="error"><?= $errors['mobile'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                <small>Email cannot be changed</small>
            </div>
            <div class="form-group">
                <label for="house"><i class="fas fa-home"></i> House Name</label>
                <input type="text" id="house" name="house" value="<?= htmlspecialchars($_POST['house'] ?? $user['house'] ?? '') ?>" required>
                <div class="error"><?= $errors['house'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="state"><i class="fas fa-map-marker-alt"></i> State</label>
                <input type="text" id="state" name="state" value="<?= htmlspecialchars($_POST['state'] ?? $user['state'] ?? '') ?>" required>
                <div class="error"><?= $errors['state'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="district"><i class="fas fa-map"></i> District</label>
                <input type="text" id="district" name="district" value="<?= htmlspecialchars($_POST['district'] ?? $user['district'] ?? '') ?>" required>
                <div class="error"><?= $errors['district'] ?? '' ?></div>
            </div>
            <div class="form-group">
                <label for="pin"><i class="fas fa-map-pin"></i> PIN Code</label>
                <input type="text" id="pin" name="pin" value="<?= htmlspecialchars($_POST['pin'] ?? $user['pin'] ?? '') ?>" required>
                <div class="error"><?= $errors['pin'] ?? '' ?></div>
            </div>
            <button type="submit">Update Profile</button>
        </form>
    </div>
</body>
</html>
