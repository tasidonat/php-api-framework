<?php
namespace Core\Database\Adapters;

class MySQLAdapter implements DatabaseAdapterInterface
{
    private ?\PDO $connection = null;

    public function connect(array $config): void
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->connection = new \PDO($dsn, $config['username'], $config['password'], $options);
        } catch(\PDOException $e) {
            throw new \Exception("Datavase connection failed: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }

    public function query(string $sql, array $params = []): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $statement = $this->connection->prepare($sql);
        $statement->execute(array_values($data));

        return $statement->rowCount();
    }

    public function update(string $table, array $data, array $conditions): int
    {
        $setStatements = [];
        foreach (array_keys($data) as $column) {
            $setStatements[] = "{$column} = ?";
        }

        $whereStatements = [];
        foreach (array_keys($conditions) as $column) {
            $whereStatements[] = "{$column} = ?";
        }

        $setSql = implode(', ', $setStatements);
        $whereSql = implode(' AND ', $whereStatements);

        $sql = "UPDATE {$table} SET {$setSql} WHERE {$whereSql}";

        $params = array_merge(array_values($data), array_values($conditions));

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    public function delete(string $table, array $conditions): int
    {
        $whereStatements = [];
        foreach (array_keys($conditions) as $column) {
            $whereStatements[] = "{$column} = ?";
        }

        $whereSql = implode(' AND ', $whereStatements);

        $sql = "DELETE FROM {$table} WHERE {$whereSql}";

        $statement = $this->connection->prepare($sql);
        $statement->execute(array_values($conditions));

        return $statement->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}
