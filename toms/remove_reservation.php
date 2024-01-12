<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'])) {
    $reservationID = $_POST['reservation_id'];

    // Get room ID for the reservation being removed
    $getRoomIDQuery = "SELECT room_id FROM reservations WHERE reservation_id = ?";
    if ($stmt = $conn->prepare($getRoomIDQuery)) {
        $stmt->bind_param("i", $reservationID);
        $stmt->execute();
        $stmt->bind_result($roomID);
        $stmt->fetch();
        $stmt->close();

        if ($roomID !== null) {
            // Start a transaction for atomicity
            mysqli_begin_transaction($conn);

            // Delete the reservation
            $deleteQuery = "DELETE FROM reservations WHERE reservation_id = ?";
            if ($stmt = $conn->prepare($deleteQuery)) {
                $stmt->bind_param("i", $reservationID);
                $stmt->execute();
                $stmt->close();

                // Mark the room as available again
                $updateRoomQuery = "UPDATE numuri SET reserved = 0 WHERE NumuraID = ?";
                if ($stmt = $conn->prepare($updateRoomQuery)) {
                    $stmt->bind_param("i", $roomID);
                    $stmt->execute();
                    $stmt->close();

                    // Commit the transaction
                    mysqli_commit($conn);

                    $_SESSION['reservation_removed'] = true;
                    header("Location: my_reservations.php"); // Redirect after successful removal
                    exit();
                } else {
                    mysqli_rollback($conn); // Rollback if update query fails
                    $_SESSION['remove_error'] = "Error updating room availability";
                }
            } else {
                mysqli_rollback($conn); // Rollback if delete query fails
                $_SESSION['remove_error'] = "Error removing reservation";
            }
        } else {
            $_SESSION['remove_error'] = "Invalid reservation ID";
        }
    } else {
        $_SESSION['remove_error'] = "Error fetching reservation details";
    }
}

// Redirect back to my_reservations.php if errors occur
header("Location: my_reservations.php");
exit();
?>