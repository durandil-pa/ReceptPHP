<?php
class Auth
{
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public static function loggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public static function logout()
    {
        $_SESSION = [];
        session_destroy();
    }
}
