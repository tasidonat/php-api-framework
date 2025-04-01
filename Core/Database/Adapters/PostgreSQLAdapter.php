<?php
namespace Core\Database\Adapters;

class PostgreSQLAdapter implements DatabaseAdapterInterface
{
    private ?\PDO $connection = null;

    public function connect(array $config): void
    {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        try {
            $this->connection = new \PDO($dsn, $config['username'], $config['password'], $options);
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
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
        $placeholders = [];

        $i = 1;
        foreach ($data as $key => $value) {
            $placeholders[] = '$' . $i++;
        }

        $placeholdersStr = implode(', ', $placeholders);

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholdersStr})";

        $statement = $this->connection->prepare($sql);
        $statement->execute(array_values($data));

        return $statement->rowCount();
    }

    public function update(string $table, array $data, array $conditions): int
    {
        $setStatements = [];
        $params = [];
        $i = 1;

        foreach ($data as $column => $value) {
            $setStatements[] = "{$column} = \${$i}";
            $params[] = $value;
            $i++;
        }

        $whereStatements = [];
        foreach ($conditions as $column => $value) {
            $whereStatements[] = "{$column} = \${$i}";
            $params[] = $value;
            $i++;
        }

        $setSql = implode(', ', $setStatements);
        $whereSql = implode(' AND ', $whereStatements);

        $sql = "UPDATE {$table} SET {$setSql} WHERE {$whereSql}";

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    public function delete(string $table, array $conditions): int
    {
        $whereStatements = [];
        $i = 1;
        $params = [];

        foreach ($conditions as $column => $value) {
            $whereStatements[] = "{$column} = \${$i}";
            $params[] = $value;
            $i++;
        }

        $whereSql = implode(' AND ', $whereStatements);

        $sql = "DELETE FROM {$table} WHERE {$whereSql}";

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

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
