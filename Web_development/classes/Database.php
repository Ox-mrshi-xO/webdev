<?php
// classes/Database.php
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die(json_encode(['error' => 'Database connection failed: ' . $this->conn->connect_error]));
        }
        $this->conn->set_charset('utf8mb4');
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli {
        return $this->conn;
    }

    public function escape(string $value): string {
        return $this->conn->real_escape_string($value);
    }

    public function query(string $sql): mysqli_result|bool {
        return $this->conn->query($sql);
    }

    public function prepare(string $sql): mysqli_stmt|false {
        return $this->conn->prepare($sql);
    }

    public function lastInsertId(): int {
        return $this->conn->insert_id;
    }

    public function affectedRows(): int {
        return $this->conn->affected_rows;
    }

    public function fetchAll(string $sql): array {
        $result = $this->conn->query($sql);
        if (!$result) return [];
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function fetchOne(string $sql): ?array {
        $result = $this->conn->query($sql);
        if (!$result || $result->num_rows === 0) return null;
        return $result->fetch_assoc();
    }

    public function fetchCount(string $sql): int {
        $result = $this->conn->query($sql);
        if (!$result) return 0;
        $row = $result->fetch_row();
        return (int)($row[0] ?? 0);
    }
}
