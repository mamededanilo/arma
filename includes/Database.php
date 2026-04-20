<?php
class Database {
    private static ?PDO $pdo = null;

    public static function connect(?array $cfg = null): PDO {
        if (self::$pdo) return self::$pdo;
        $cfg = $cfg ?? Config::load();
        $driver = $cfg['db_driver']; // mysql | pgsql
        $host = $cfg['db_host'];
        $port = $cfg['db_port'];
        $name = $cfg['db_name'];
        $user = $cfg['db_user'];
        $pass = $cfg['db_pass'];

        if ($driver === 'mysql') {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        } elseif ($driver === 'pgsql') {
            $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
        } else {
            throw new RuntimeException("Driver não suportado: {$driver}");
        }

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return self::$pdo;
    }

    public static function detectDrivers(): array {
        $available = PDO::getAvailableDrivers();
        return [
            'mysql' => in_array('mysql', $available, true),
            'pgsql' => in_array('pgsql', $available, true),
        ];
    }

    public static function testConnection(string $driver, string $host, string $port, string $name, string $user, string $pass): array {
        try {
            if ($driver === 'mysql') {
                $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            } else {
                $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
            }
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->query('SELECT 1');
            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
