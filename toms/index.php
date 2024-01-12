<?php
session_start();

class RoomManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAvailableRooms() {
        $sql = "SELECT * FROM numuri WHERE reserved = 0 ORDER BY created_at DESC";
        $result = $this->conn->query($sql);

        return $result;
    }
}

include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$is_admin = $_SESSION['role'] === 'admin' ?? false;
$username = $_SESSION['username'];

$roomManager = new RoomManager($conn);
$result = $roomManager->getAvailableRooms();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Hotel</title>
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
    <div class="container">
        <?php
        if ($result && $result->num_rows > 0) {
            while ($room = $result->fetch_assoc()) {
                echo "<div class='product'>";
                echo "<div class='room'>";
                echo "<h3>" . $room['Nosaukums'] . "</h3>";
                echo "<p>" . $room['Apraksts'] . "</p>";
                echo "<p>$" . $room['Cena'] . "</p>";
                echo "<p><img src='" . $room['image'] . "' class='image'></p>";
                echo "</div>";
                echo "<div class='reserve'>";
                echo "<a href='reserve.php?room_id=" . $room['NumuraID'] . "'>";
                echo "<button class='button'>Reserve room</button>";
                echo "</a>";
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p>No available rooms found.</p>";
        }

        if ($result) {
            $result->close();
        }

        $conn->close();
        ?>
    </div>
</body>
</html>