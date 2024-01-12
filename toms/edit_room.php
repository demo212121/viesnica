<?php
session_start();

class RoomEditor {
    private $conn;

    public function __construct() {
        $this->conn = mysqli_connect("localhost", "root", "", "hotel");

        if (!$this->conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
    }

    public function editRoom($roomId, $newDescription, $newPrice) {
        if ($_SESSION['role'] !== 'admin') {
            echo "Unauthorized access!";
            exit();
        }

        $sql = "UPDATE numuri SET Apraksts = ?, Cena = ? WHERE NumuraID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssi", $newDescription, $newPrice, $roomId);
            mysqli_stmt_execute($stmt);
            echo "Room updated successfully!";
        } else {
            echo "Error: " . mysqli_error($this->conn);
        }
    }

    public function closeConnection() {
        mysqli_close($this->conn);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $editor = new RoomEditor();

    $roomId = $_POST['room_id'];
    $newDescription = $_POST['new_description'];
    $newPrice = $_POST['new_price'];

    $editor->editRoom($roomId, $newDescription, $newPrice);
    $editor->closeConnection();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Room</title>
</head>
<body>
    <h2>Edit Room</h2>
    <form action="edit_room.php" method="post">
        <label for="room_id">Room ID:</label><br>
        <input type="text" id="room_id" name="room_id" required><br>
        <label for="new_description">New Description:</label><br>
        <textarea id="new_description" name="new_description" required></textarea><br>
        <label for="new_price">New Price:</label><br>
        <input type="text" id="new_price" name="new_price" required><br>
        <input type="submit" value="Edit Room">
    </form>
</body>
</html>
