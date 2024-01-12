<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class UserManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($username, $password) {
        $error = '';

        if (empty($username) || empty($password)) {
            $error = "Username and password are required.";
        } else {
            $stmt = $this->conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];

                    if ($row['role'] === 'admin') {
                        header('Location: add_room.php');
                        exit();
                    } else {
                        header('Location: index.php');
                        exit();
                    }
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "User not found";
            }

            $stmt->close();
        }

        return $error;
    }
}

session_start();
include "db.php";

if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'user')) {
    header('Location: index.php');
    exit();
}

$userManager = new UserManager($conn);
$error = '';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $error = $userManager->login($username, $password);
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Login</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">Login</h2>
        <div class="form-container">
            <form method="post" action="login.php" class="login-form">
                <?php if (!empty($error)) { ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php } ?>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password">
                <input type="submit" name="login" value="Login">
                <p>Not registered yet? <a href="register.php">Register here</a></p>
            </form>
        </div>
    </div>
</body>
</html>