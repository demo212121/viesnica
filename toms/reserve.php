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

            header("Location: " . $_SERVER['REQUEST_URI']);
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
    <title>Reserve Room</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="header">
        <h1>Available Rooms</h1>
        <div class="navigation">
            <a href="my_reservations.php" class="button">My Reservations</a>
            <a href="index.php" class="button">Home</a>
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
        <?php if (empty($room) && $_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['room_id'])) { ?>
            <p>Room details not available.</p>
        <?php } else { ?>
            <?php if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($error)) { ?>
                <?php if (!empty($room) && isset($room['Nosaukums']) && isset($room['Apraksts']) && isset($room['Cena'])) { ?>
                <?php } ?>
                <div class="reservation-form">
                    <h2>Reservation Details</h2>
                    <?php if (!empty($error)) { ?>
                        <div class="error"><?php echo $error; ?></div>
                    <?php } ?>
                    <form action="" method="POST">
                        <input type="hidden" name="room_id" value="<?php echo isset($_GET['room_id']) ? $_GET['room_id'] : ''; ?>">
                        <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($imagePath); ?>">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($formData['name']) ? htmlspecialchars($formData['name']) : ''; ?>"><br><br>
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($formData['email']) ? htmlspecialchars($formData['email']) : ''; ?>"><br><br>
                        <label for="phone">Phone Number:</label>
                        <input type="text" id="phone" name="phone" value="<?php echo isset($formData['phone']) ? htmlspecialchars($formData['phone']) : ''; ?>"><br><br>
                        <label for="date">Date of Reservation:</label>
                        <input type="date" id="date" name="date" value="<?php echo isset($formData['date']) ? htmlspecialchars($formData['date']) : ''; ?>" min="<?php echo date('Y-m-d'); ?>"><br><br>
                        <label for="credit_card">Credit Card Number:</label>
                        <input type="text" id="credit_card" name="credit_card" value="<?php echo isset($formData['credit_card']) ? htmlspecialchars($formData['credit_card']) : ''; ?>"><br><br>
                        <input type="submit" value="Reserve">
                    </form>
                    <a href="index.php">Go back</a>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
    <?php
    if (isset($_SESSION['reservation_success']) && $_SESSION['reservation_success'] === true) {
        echo "<script>alert('Reservation is successful');</script>";
        unset($_SESSION['reservation_success']);
    }
    ?>
</body>
</html>