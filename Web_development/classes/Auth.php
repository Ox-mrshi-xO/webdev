<?php
// classes/Auth.php
class Auth {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login(string $email, string $password): array {
        $email = $this->db->escape($email);
        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = '$email' AND is_active = 1");
        if (!$user) return ['success' => false, 'message' => 'Invalid email or password.'];
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        session_regenerate_id(true);
        return ['success' => true, 'role' => $user['role']];
    }

    public function logout(): void {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): void {
        if (empty($_SESSION['logged_in'])) {
            header('Location: ../login.php');
            exit;
        }
    }

    public static function checkAdmin(): void {
        self::check();
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: ../user/index.php');
            exit;
        }
    }

    public static function checkUser(): void {
        self::check();
        if ($_SESSION['user_role'] !== 'user') {
            header('Location: ../admin/index.php');
            exit;
        }
    }

    public static function isLoggedIn(): bool {
        return !empty($_SESSION['logged_in']);
    }

    public static function currentUser(): array {
        return [
            'id'    => $_SESSION['user_id']    ?? null,
            'name'  => $_SESSION['user_name']  ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role'  => $_SESSION['user_role']  ?? '',
        ];
    }
}
