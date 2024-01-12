<?php
class RegistrationManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function registerUser($username, $password) {
        $error = '';
        $successMessage = '';

        if (empty($username) || empty($password)) {
            $error = "Username and password are required.";
        } else {
            $stmt_check = $this->conn->prepare("SELECT username FROM users WHERE username = ?");
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            if ($result->num_rows > 0) {
                $error = "Username already exists. Please choose a different username.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'user';

                $stmt_insert = $this->conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("sss", $username, $hashed_password, $role);

                if ($stmt_insert->execute()) {
                    $successMessage = "Registration successful!";
                } else {
                    $error = "Error: " . $stmt_insert->error;
                }

                $stmt_insert->close();
            }
            $stmt_check->close();
        }

        return [$error, $successMessage];
    }
}

include "db.php";
$registrationManager = new RegistrationManager($conn);
$error = '';
$success_message = '';

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    list($error, $success_message) = $registrationManager->registerUser($username, $password);
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registration</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="registration-container">
        <h2>Registration</h2>
        <?php if (!empty($success_message)) { ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php } ?>
        <?php if (!empty($error)) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>
        <form method="post" action="register.php" class="registration-form">
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username"><br>
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password"><br>
            <input type="submit" name="register" value="Register">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>
</body>
</html>
