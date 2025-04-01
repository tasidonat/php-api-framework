<?php
namespace Core\Database;

class QueryBuilder
{
    private Connection $connection;
    private string $table = "";
    private array $selects = [];
    private array $wheres = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $joins = [];
    private array $bindings = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(string ...$columns): self
    {
        $this->selects = array_merge($this->selects, $columns);
        return $this;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        $this->bindings[] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'placeholders' => $placeholders
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => $direction
        ];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'inner',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'left',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    public function buildSelectQuery(): string
    {
        $columns = !empty($this->selects) ? implode(', ', $this->selects) : '*';

        $sql = "SELECT {$columns} FROM {$this->table}";

        // Joins
        if(!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $type = strtoupper($join['type']);
                $sql .= " {$type} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }

        // Where clauses
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ';

            $whereClauses = [];

            foreach ($this->wheres as $where) {
                if ($where['type'] === 'basic') {
                    $whereClauses[] = "{$where['column']} {$where['operator']} ?";
                } elseif ($where['type'] === 'in') {
                    $whereClauses[] = "{$where['column']} IN ({$where['placeholders']})";
                }
            }

            $sql .= implode(' AND ', $whereClauses);
        }

        // Order By
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ';
            
            $orderClauses = [];
            
            foreach ($this->orderBy as $order) {
                $orderClauses[] = "{$order['column']} " . strtoupper($order['direction']);
            }
            
            $sql .= implode(', ', $orderClauses);
        }

        // Limit and offset
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        return $this->connection->query($sql, $this->bindings);
    }

    public function first()
    {
        $this->limit(1);
        $result = $this->get();

        return $result[0] ?? null;
    }

    public function insert(array $data): int
    {
        return $this->connection->insert($this->table, $data);
    }

    public function update(array $data): int
    {
        $whereConditions = [];
        $whereBindings = [];

        foreach ($this->wheres as $where) {
            if($where['type' === 'basic']) {
                $whereConditions[$where['column']] = $where['value'];
                $whereBindings[] = $where['value'];
            }
        }

        return $this->connection->update($this->table, $data, $whereConditions);
    }

    public function delete(): int
    {
        $whereConditions = [];

        foreach ($this->wheres as $where) {
            if($where['type' === 'basic']) {
                $whereConditions[$where['column']] = $where['value'];
                $whereBindings[] = $where['value'];
            }
        }

        return $this->connection->delete($this->table, $whereConditions);
    }
}
