<?php
namespace Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $content = '';

    public function __construct($content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = array_merge(['Content-Type' => 'application/json'], $headers);
    }

    public function setContent($content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function json($data): self
    {
        $this->content = json_encode($data);
        return $this->header('Content-Type', 'application/json');
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo $this->content;
        exit;
    }

    public static function ok($data = null): self
    {
        return (new self())->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public static function created($data = null): self
    {
        return (new self('', 201))->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public static function noContent(): self
    {
        return new self('', 204);
    }

    public static function badRequest($data = 'Bad Request'): self
    {
        if (is_string($data)) {
            $data = [
                'status' => 'error',
                'message' => $data
            ];
        }

        return (new self('', 400))->json($data);
    }

    public static function unauthorized($data = 'Unauthorized'): self
    {
        if (is_string($data)) {
            $data = [
                'status' => 'error',
                'message' => $data
            ];
        }

        return (new self('', 401))->json($data);
    }

    public static function forbidden($data = 'Forbidden'): self
    {
        if (is_string($data)) {
            $data = [
                'status' => 'error',
                'message' => $data
            ];
        }

        return (new self('', 403))->json($data);
    }

    public static function notFound($data = 'Not Found'): self
    {
        if (is_string($data)) {
            $data = [
                'status' => 'error',
                'message' => $data
            ];
        }

        return (new self('', 404))->json($data);
    }

    public static function error($data = 'Server Error', int $status = 500): self
    {
        if (is_string($data)) {
            $data = [
                'status' => 'error',
                'message' => $data
            ];
        } elseif (is_array($data) && $status !== 500) {
            return (new self('', $status))->json($data);
        }

        return (new self('', $status))->json($data);
    }
}
