<?php
session_start();
include "db.php"; // Include your database connection file

class ReservationHandler {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function isAdmin() {
        // Your admin role check logic here
        // Replace this with your actual admin role check logic
        return ($_SESSION['role'] === 'admin');
    }

    public function createRoom($formData) {
        $insertRoomQuery = "INSERT INTO numuri (Nosaukums, Apraksts, Cena, image) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($insertRoomQuery);
        $stmt->bind_param("ssds", $formData['room_name'], $formData['description'], $formData['price'], $formData['image_path']);
        $stmt->execute();
        $stmt->close();
    }
}

$reservationHandler = new ReservationHandler($conn);

$is_admin = $reservationHandler->isAdmin();
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $formData = $_POST;

    if (
        empty($_POST['room_name']) ||
        empty($_POST['description']) ||
        empty($_POST['price'])
    ) {
        $error = "All fields are required";
    } else {
        // Handle image upload
        $targetDir = "uploads/"; // Change this directory as needed
        $targetFile = $targetDir . basename($_FILES['image']['name']);

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $formData['image_path'] = $targetFile;

            $reservationHandler->createRoom($formData);

            // Redirect to the main page with a success parameter
            header("Location: add_room.php?success=true");
            exit();
        } else {
            $error = "Error uploading image";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Room</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Available Rooms</h1>
        <div class="navigation">
            <a href="index.php" class="button">Home</a>
            <a href="my_reservations.php" class="button">My Reservations</a>
            <?php
            if ($is_admin) {
                echo "<a href='add_room.php' class='button'>Add A New Room</a>";
            }
            ?>
        </div>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
</div>
    </div>
    <div class="success-message <?php if (isset($_GET['success']) && $_GET['success'] === 'true') echo 'success'; ?>">
        <?php
        // Check if room added successfully using GET parameter
        if (isset($_GET['success']) && $_GET['success'] === 'true') {
            echo 'Room added successfully!';
        }
        ?>
    </div>
    <div class="add-room-container">
        <h2>Add New Room</h2>
        <form action="add_room.php" method="post" enctype="multipart/form-data">
            <label for="room_name">Room Name:</label><br>
            <input type="text" id="room_name" name="room_name" required><br>
            <label for="description">Description:</label><br>
            <textarea style="resize:none; width:280px; height:100px;" id="description" name="description" required></textarea><br>
            <label for="price">Price:</label><br>
            <input type="text" id="price" name="price" required><br>
            <label for="image">Upload Image:</label><br>
            <input type="file" id="image" name="image" accept="image/*" required><br>
            <input type="submit" value="Add Room">
        </form>
        <a href="index.php">Go to main page</a>
    </div>
</body>
</html>
