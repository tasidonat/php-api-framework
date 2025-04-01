<?php
namespace Core;

use Core\Database\Connection;
use Core\Database\QueryBuilder;

class App
{
    private static ?App $instance = null;
    private Router $router;
    private Request $request;
    private array $config = [];

    private function __construct()
    {
        $this->router = new Router();
        $this->request = new Request();
    }

    public static function getInstance(): self
    {
        if(self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function bootstrap(string $basePath): self
    {
        Environment::load($basePath . '/.env');

        $this->loadConfig($basePath);

        $this->setupDatabase();

        return $this;
    }

    public function loadConfig(string $basePath): void
    {
        $configFiles = glob($basePath . '/config/*.php');
        
        foreach ($configFiles as $file) {
            $name = basename($file, '.php');
            $this->config[$name] = require $file;
        }
    }

    public function setupDatabase(): void
    {
        $config = $this->config['database'] ?? [];

        if(!empty($config)) {
            $connection = Connection::getInstace();
            $connection->connect($config);
        }
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getConfig(?string $key = null, $default = null)
    {
        if($key === null) {
            return $this->config;
        }

        if(strpos($key, '.') !== false) {
            $parts = explode(".", $key);
            $config = $this->config;
            
            foreach ($parts as $part) {
                if(!isset($config[$part])) {
                    return $default;
                }

                $config = $config[$part];
            }

            return $config;
        }

        return $this->config[$key] ?? $default;
    }

    public function db(?string $table = null): QueryBuilder
    {
        $db = new QueryBuilder(Connection::getInstace());

        if($table !== null) {
            $db->table($table);
        }

        return $db;
    }

    public function run(): void
    {
        $response = $this->router->dispatch($this->request);
        $response->send();
    }
}
