<?php
class SessionManager {
    public static function logoutAndRedirect($loginPage = "login.php") {
        session_start();
        $_SESSION = array();
        session_destroy();

        header("Location: $loginPage");
        exit();
    }
}

SessionManager::logoutAndRedirect();
?>