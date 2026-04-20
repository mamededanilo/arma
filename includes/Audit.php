<?php
class Audit {
    public static function log(string $user, string $action, string $details): void {
        try {
            $pdo = Database::connect();
            $st = $pdo->prepare('INSERT INTO arma_audit_logs (username, action, details, created_at) VALUES (?, ?, ?, ?)');
            $st->execute([$user, $action, $details, date('Y-m-d H:i:s')]);
        } catch (Throwable $e) {
            error_log('Audit failure: ' . $e->getMessage());
        }
    }
}
