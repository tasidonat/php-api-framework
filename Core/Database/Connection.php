<?php
namespace Core\Database;

use Core\Database\Adapters\DatabaseAdapterInterface;
use Core\Database\Adapters\MySQLAdapter;
use Core\Database\Adapters\PostgreSQLAdapter;
use Core\Database\Adapters\SQLiteAdapter;

class Connection
{
    private static ?Connection $instance = null;
    private ?DatabaseAdapterInterface $adapter = null;

    private function __construct() {}

    private function __clone() {}

    public static function getInstace(): self
    {
        if(self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function connect(array $config): void
    {
        $driver = $config['driver'] ?? '';

        $this->adapter = match($driver) {
            'mysql' => new MySQLAdapter(),
            'pgsql' => new PostgreSQLAdapter(),
            'sqlite' => new SQLiteAdapter(),
            default => throw new \Exception("Unsupported database driver: {$driver}")
        };

        $this->adapter->connect($config);
    }

    public function getAdapter(): DatabaseAdapterInterface
    {
        if($this->adapter === null) {
            throw new \Exception("Database connection not established");
        }

        return $this->adapter;
    }

    public function __call(string $method, array $arguments)
    {
        if($this->adapter === null) {
            throw new \Exception("Database connection not established");
        }

        return $this->adapter->$method(...$arguments);
    }
}
