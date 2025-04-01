<?php
namespace Core\Database\Adapters;

interface DatabaseAdapterInterface
{
    public function connect(array $config): void;
    public function disconnect(): void;
    public function query(string $sql, array $params = []): array;
    public function insert(string $table, array $data): int;
    public function update(string $table, array $data, array $conditions): int;
    public function delete(string $table, array $conditions): int;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
    public function lastInsertId(): string;
}
