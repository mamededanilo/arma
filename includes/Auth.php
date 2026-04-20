<?php
class Auth {
    public static function attempt(string $username, string $password): bool {
        $pdo = Database::connect();
        $st = $pdo->prepare('SELECT * FROM arma_users WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        $u = $st->fetch();
        if (!$u) return false;
        if (!password_verify($password, $u['password_hash'])) return false;
        $_SESSION['uid'] = $u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['role'] = $u['role'];
        $_SESSION['must_change_password'] = (int)$u['must_change_password'] === 1;
        Audit::log($u['username'], 'LOGIN', 'Usuário autenticado');
        return true;
    }

    public static function logout(): void {
        if (!empty($_SESSION['username'])) {
            Audit::log($_SESSION['username'], 'LOGOUT', 'Sessão encerrada');
        }
        session_destroy();
    }

    public static function check(): bool {
        return !empty($_SESSION['uid']);
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            exit('Acesso negado');
        }
    }

    public static function currentUser(): array {
        return [
            'id' => $_SESSION['uid'] ?? null,
            'username' => $_SESSION['username'] ?? '',
            'role' => $_SESSION['role'] ?? 'padrao',
            'must_change_password' => $_SESSION['must_change_password'] ?? false,
        ];
    }

    public static function changePassword(int $uid, string $newPassword): void {
        $pdo = Database::connect();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $st = $pdo->prepare('UPDATE arma_users SET password_hash = ?, must_change_password = 0 WHERE id = ?');
        $st->execute([$hash, $uid]);
        $_SESSION['must_change_password'] = false;
    }
}
