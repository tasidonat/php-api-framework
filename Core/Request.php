<?php
namespace Core;

class Request
{
    private array $query = [];
    private array $request = [];
    private array $headers = [];
    private string $method = '';
    private string $uri = '';
    private array $files = [];
    private ?string $content = null;

    public function __construct()
    {
        $this->query = $_GET;
        $this->request = $_POST;
        $this->headers = $this->getHeaders();
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->files = $_FILES;
        $this->content = file_get_contents('php://input');
    }

    public function query(?string $key = null, $default = null)
    {
        if($key === null) {
            return $this->query;
        }

        return $this->query['key'] ?? $default;
    }

    public function input(?string $key = null, $default = null)
    {
        if ($this->isJson()) {
            $data = json_decode($this->content, true) ?? [];

            if ($key === null) {
                return array_merge($this->request, $data);
            }

            return $data[$key] ?? $this->request[$key] ?? $default;
        }

        if ($key === null) {
            return $this->request;
        }

        return $this->request[$key] ?? $default;
    }

    public function header(?string $key = null, $default = null)
    {
        if($key === null) {
            return $this->headers;
        }

        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);
        return $path ?? '/';
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    public function all(): array
    {
        if($this->isJson()) {
            $data = json_decode($this->content, true) ?? [];
            return array_merge($this->query, $this->request, $data);
        }

        return array_merge($this->query, $this->request);
    }

    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type');
        return $contentType && strpos($contentType, 'application/json') !== false;
    }

    private function getHeaders(): array
    {
        $headers = [];

        if(function_exists('getallheaders')) {
            $headersRaw = getallheaders();
            foreach ($headersRaw as $key => $value) {
                $headers[strtolower($key)] = $value;
            }

            return $headers;
        }

        foreach ($_SERVER as $key => $value) {
            if(strpos($key, 'HTTP_') === 0) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            } elseif(in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
