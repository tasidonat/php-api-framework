<?php
namespace App\Models;

use Core\App;
use Core\Database\QueryBuilder;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if(in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, $value): void
    {
        if(in_array($key, $this->fillable)) {
            $this->attributes[$key] = $value;
        }
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function getAttributes(): array
    {
        return array_diff_key($this->attributes, array_flip($this->hidden));
    }

    public function toArray(): array
    {
        return $this->getAttributes();
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function newQuery(): QueryBuilder
    {
        return App::getInstance()->db($this->table);
    }

    public function save(): bool
    {
        if(isset($this->attributes[$this->primaryKey])) {
            return $this->update();
        }

        return $this->insert();
    }

    protected function insert(): bool
    {
        $id = $this->newQuery()->insert($this->attributes);

        if($id) {
            $this->attributes[$this->primaryKey] = $id;
            return true;
        }

        return false;
    }

    protected function update(): bool
    {
        $id = $this->attributes[$this->primaryKey];

        $affected = $this->newQuery()
            ->where($this->primaryKey, '=', $id)
            ->update($this->attributes);

        return $affected > 0;
    }

    public function delete(): bool
    {
        if(!isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        $id = $this->attributes[$this->primaryKey];

        $affected = $this->newQuery()
            ->where($this->primaryKey, '=', $id)
            ->delete();

        return $affected > 0;
    }

    public static function find($id)
    {
        $model = new static();

        $attributes = $model->newQuery()
            ->where($model->primaryKey, '=', $id)
            ->first();

        if(!$attributes) {
            return null;
        }

        return new static($attributes);
    }

    public static function all(): array
    {
        $model = new static();

        $items = $model->newQuery()->get();

        $models = [];

        foreach ($items as $attributes) {
            $models[] = new static($attributes);
        }

        return $models;
    }

    public static function where(string $column, string $operator, $value): QueryBuilder
    {
        $model = new static();

        return $model->newQuery()->where($column, $operator, $value);
    }
}
