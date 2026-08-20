<?php

/**
 * Model dasar.
 * Memberikan akses PDO + helper query umum.
 */

class Model
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    /** Ambil semua baris. */
    protected function all(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Ambil satu baris. */
    protected function one(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Ambil nilai tunggal. */
    protected function value(string $sql, array $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /** Eksekusi tanpa hasil (INSERT/UPDATE/DELETE). */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected function lastId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }
}
