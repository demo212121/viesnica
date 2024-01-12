<?php
session_start();
include "db.php";

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$error = '';
$room = [];

class ReservationHandler {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function fetchRoomDetails($roomId) {
        $room = [];
        if ($stmt = $this->conn->prepare("SELECT * FROM numuri WHERE NumuraID = ?")) {
            $stmt->bind_param("i", $roomId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                header("Location: index.php");
                exit();
            }

            $room = $result->fetch_assoc();
            $stmt->close();
        } else {
            echo "Error in SQL query";
            exit();
        }

        return $room;
    }

    public function isDateValid($date) {
        return (strtotime($date) >= strtotime(date('Y-m-d')));
    }

    public function makeReservation($formData, $room) {
        $error = '';

        if (
            empty($formData['name']) ||
            empty($formData['email']) ||
            empty($formData['phone']) ||
            empty($formData['date']) ||
            empty($formData['credit_card'])
        ) {
            $error = "All fields are required";
        } elseif (!$this->isDateValid($formData['date'])) {
            $error = "Please select a date that is today or in the future";
        } else {
            $roomID = $formData['room_id'];

            $updateQuery = "UPDATE numuri SET reserved = reserved + 1 WHERE NumuraID = ?";
            if ($stmt = $this->conn->prepare($updateQuery)) {
                $stmt->bind_param("i", $roomID);
                $stmt->execute();
                $stmt->close();
            } else {
                echo "Error updating reservation count in the database";
                exit();
            }

            $insertQuery = "INSERT INTO reservations (room_id, name, email, username, phone, date, credit_card, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = $this->conn->prepare($insertQuery)) {
                $stmt->bind_param(
                    "isssssss",
                    $formData['room_id'],
                    $formData['name'],
                    $formData['email'],
                    $_SESSION['username'],
                    $formData['phone'],
                    $formData['date'],
                    $formData['credit_card'],
                    $formData['image_path']
                );

                $stmt->execute();
                $stmt->close();
            } else {
                echo "Error inserting reservation data into the database";
                exit();
            }

            $_SESSION['reservation_success'] = true;
            $_SESSION['reservation_id'] = $this->conn->insert_id; // Store the reservation ID

            header("Location: my_reservations.php"); // Redirect to my_reservations.php
            exit();
        }

        return $error;
    }
}

$reservationHandler = new ReservationHandler($conn);

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['room_id'])) {
    $room = $reservationHandler->fetchRoomDetails($_GET['room_id']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $formData = $_POST;
    $error = $reservationHandler->makeReservation($formData, $room);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations</title>
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
<div class="container">
    <?php
    $reservationManager = new ReservationManager($conn);
    $reservations = $reservationManager->getUserReservations($username);

    if (!empty($reservations)) {
        foreach ($reservations as $row) {
            echo "<div class='product'>";
            echo "<h3>Reservation Details</h3>";
            echo "<p class='room'>Room Name: " . $row['Nosaukums'] . "</p>";
            echo "<p class='room'>Reservation Date: " . $row['reservation_date'] . "</p>";
            echo "<img class='image' src='" . $row['image'] . "' alt='Room Image'>";
            echo "<form action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "' method='POST'>";
            echo "<input type='hidden' name='reservation_id' value='" . $row['reservation_id'] . "'>";
            echo "<input type='submit' value='Remove Reservation'>";
            echo "</form>";
            echo "</div>";
        }
    } else {
        echo "<p class='room'>No reservations found for this user.</p>";
    }
    ?>
</div>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'])) {
    $reservationID = $_POST['reservation_id'];
    $username = $_SESSION['username'];

    $reservationManager = new ReservationManager($conn);
    $reservationManager->removeReservation($reservationID, $username);
}
$conn->close();
?>
</div>
</body>
</html>