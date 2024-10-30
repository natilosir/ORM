<?php
class DB {
    private static $table;
    private static $query;
    private static $params = [];

    public static function Table($tableName) {
        self::$table = $tableName;
        self::$query = "SELECT * FROM " . self::$table;
        return new self;
    }

    public function where($column, $value) {
        self::$query .= " WHERE $column = ?";
        self::$params[] = $value;
        return $this;
    }

    public function orWhere($column, $value) {
        self::$query .= " OR $column = ?";
        self::$params[] = $value;
        return $this;
    }

    public function andWhere($column, $value) {
        self::$query .= " AND $column = ?";
        self::$params[] = $value;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        self::$query .= " ORDER BY $column $direction";
        return $this;
    }

    public function limit($number) {
        self::$query .= " LIMIT $number";
        return $this;
    }

    public function get() {
        $stmt = DBConnection::getInstance()->prepare(self::$query);
        $stmt->execute(self::$params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count() {
        $stmt = DBConnection::getInstance()->prepare("SELECT COUNT(*) FROM " . self::$table . self::$query);
        $stmt->execute(self::$params);
        return $stmt->fetchColumn();
    }

    public function search($conditions) {
        $whereClauses = [];
        foreach ($conditions as $column => $value) {
            $whereClauses[] = "$column = ?";
            self::$params[] = $value;
        }
        self::$query .= " WHERE " . implode(' AND ', $whereClauses);
        return $this;
    }

    public function query($sql) {
        $stmt = DBConnection::getInstance()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($data) {
        if (is_array($data)) {
            $columns = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_fill(0, count($data), '?'));
            $stmt = DBConnection::getInstance()->prepare("INSERT INTO " . self::$table . " ($columns) VALUES ($placeholders)");
            $stmt->execute(array_values($data));
        } else {
            $stmt = DBConnection::getInstance()->prepare("INSERT INTO " . self::$table . " ($data[0]) VALUES (?)");
            $stmt->execute([$data[1]]);
        }
    }

    public function update($id, $data) {
        $setClauses = [];
        foreach ($data as $column => $value) {
            $setClauses[] = "$column = ?";
            self::$params[] = $value;
        }
        $stmt = DBConnection::getInstance()->prepare("UPDATE " . self::$table . " SET " . implode(', ', $setClauses) . " WHERE id = ?");
        $self::$params[] = $id;
        $stmt->execute(self::$params);
    }

    public function delete($id) {
        $stmt = DBConnection::getInstance()->prepare("DELETE FROM " . self::$table . " WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?><?php
class DB {
    private static $table;
    private static $query;
    private static $params = [];

    public static function Table($tableName) {
        self::$table = $tableName;
        self::$query = "SELECT * FROM " . self::$table;
        return new self;
    }

    public function where($column, $value) {
        self::$query .= " WHERE $column = ?";
        self::$params[] = $value;
        return $this;
    }

    public function orWhere($column, $value) {
        self::$query .= " OR $column = ?";
        self::$params[] = $value;
        return $this;
    }

    public function andWhere($column, $value) {
        self::$query .= " AND $column = ?";
        self::$params[] = $value;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        self::$query .= " ORDER BY $column $direction";
        return $this;
    }

    public function limit($number) {
        self::$query .= " LIMIT $number";
        return $this;
    }

    public function get() {
        $stmt = DBConnection::getInstance()->prepare(self::$query);
        $stmt->execute(self::$params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count() {
        $stmt = DBConnection::getInstance()->prepare("SELECT COUNT(*) FROM " . self::$table . self::$query);
        $stmt->execute(self::$params);
        return $stmt->fetchColumn();
    }

    public function search($conditions) {
        $whereClauses = [];
        foreach ($conditions as $column => $value) {
            $whereClauses[] = "$column = ?";
            self::$params[] = $value;
        }
        self::$query .= " WHERE " . implode(' AND ', $whereClauses);
        return $this;
    }

    public function query($sql) {
        $stmt = DBConnection::getInstance()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($data) {
        if (is_array($data)) {
            $columns = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_fill(0, count($data), '?'));
            $stmt = DBConnection::getInstance()->prepare("INSERT INTO " . self::$table . " ($columns) VALUES ($placeholders)");
            $stmt->execute(array_values($data));
        } else {
            $stmt = DBConnection::getInstance()->prepare("INSERT INTO " . self::$table . " ($data[0]) VALUES (?)");
            $stmt->execute([$data[1]]);
        }
    }

    public function update($id, $data) {
        $setClauses = [];
        foreach ($data as $column => $value) {
            $setClauses[] = "$column = ?";
            self::$params[] = $value;
        }
        $stmt = DBConnection::getInstance()->prepare("UPDATE " . self::$table . " SET " . implode(', ', $setClauses) . " WHERE id = ?");
        $self::$params[] = $id;
        $stmt->execute(self::$params);
    }

    public function delete($id) {
        $stmt = DBConnection::getInstance()->prepare("DELETE FROM " . self::$table . " WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>