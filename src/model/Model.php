<?php

namespace Velto\Core\Model;

use PDO;
use DateTimeImmutable;
use Velto\Core\Env\Env;


#[\AllowDynamicProperties]

class Model
{

    protected PDO $pdo;
    protected string $table;
    protected array $fillable = [];
    protected array $conditions = [];
    protected array $order = [];
    protected bool $timestamps = true;
    protected ?string $searchError = null;


    public function __construct()
    {
        $this->init();
        $this->bootTimestamps();
    }

    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        if (method_exists($this, $key)) {
            $relation = $this->{$key}();
            $this->{$key} = $relation;
            return $relation;
        }

        return null;
    }

    public function __clone()
    {
        $this->conditions = $this->conditions ?? [];
    }
    public function hasError(): bool
    {
        return $this->searchError !== null;
    }

    public function getError(): ?string
    {
        return $this->searchError;
    }

    public function belongsTo(string $relatedClass, string $foreignKey, string $ownerKey): ?object
    {
        if (!isset($this->{$foreignKey})) {
            return null;
        }

        return (new $relatedClass)->where($ownerKey, $this->{$foreignKey})->first();
    }

    public function hasOne(string $relatedClass, string $foreignKey, string $localKey): ?object
    {
        if (!isset($this->{$localKey})) {
            return null;
        }

        return (new $relatedClass)->where($foreignKey, $this->{$localKey})->first();
    }

    public function hasMany(string $relatedClass, string $foreignKey, string $localKey): array
    {
        if (!isset($this->{$localKey})) {
            return [];
        }

        return (new $relatedClass)->where($foreignKey, $this->{$localKey})->get();
    }

    protected function init(): void
    {
        if (!isset($this->pdo)) {
            $driver = Env::get('DB_CONNECTION');
            $host = Env::get('DB_HOST');
            $dbname = Env::get('DB_DATABASE');
            $username = Env::get('DB_USERNAME');
            $password = Env::get('DB_PASSWORD');

            switch ($driver) {
                case 'mysql':
                    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
                    break;
                case 'pgsql':
                    $dsn = "pgsql:host=$host;dbname=$dbname";
                    break;
                default: // sqlite
                    $fullPath = BASE_PATH . '/' . $dbname;
                    $dir = dirname($fullPath);

                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    $dsn = 'sqlite:' . $fullPath;
                    $username = null;
                    $password = null;
                    break;
            }

            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    protected function bootTimestamps(): void
    {
        if ($this->timestamps && !in_array('created_at', $this->fillable)) {
            $this->fillable[] = 'created_at';
        }
        if ($this->timestamps && !in_array('updated_at', $this->fillable)) {
            $this->fillable[] = 'updated_at';
        }
    }

    public static function findBy(string $column, $value): ?static
    {
        return (new static())->findByColumn($column, $value);
    }

    protected function findByColumn(string $column, $value): ?static
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$column} = :value");
        $stmt->bindValue(':value', $value);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) return null;

        $model = new static();
        foreach ($result as $key => $val) {
            $model->$key = $val;
        }

        return $model;
    }

    public static function create(array $data): bool
    {
        return (new static())->insertWithTimestamps($data);
    }

    protected function insertWithTimestamps(array $data): bool
    {
        if ($this->timestamps) {
            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }
        return $this->insert($data);
    }

    public static function insert(array $rows): bool
    {
        $instance = new static;

        if (empty($rows)) return false;

        if (!isset($rows[0])) {
            return $instance->insertSingle($rows);
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', $columns);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholders));

        $values = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $values[] = $row[$col] ?? null;
            }
        }

        $sql = "INSERT INTO {$instance->table} ($columnList) VALUES $allPlaceholders";
        $stmt = $instance->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    protected function insertSingle(array $data): bool
    {
        $fillableData = array_intersect_key($data, array_flip($this->fillable));
        $columns = implode(', ', array_keys($fillableData));
        $placeholders = implode(', ', array_map(fn($v) => ":$v", array_keys($fillableData)));

        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");

        foreach ($fillableData as $key => $value) {
            if ($this->isIntegerColumn($key)) {
                if ($value === '') $value = null;
                $paramType = PDO::PARAM_INT;
            } elseif (is_null($value)) {
                $paramType = PDO::PARAM_NULL;
            } else {
                $paramType = PDO::PARAM_STR;
            }

            $stmt->bindValue(":$key", $value, $paramType);
        }

        return $stmt->execute();
    }

    protected function isIntegerColumn(string $column): bool
    {
        $integerColumns = ['id', 'email_verified'];
        return in_array($column, $integerColumns);
    }

    public function update(array|string $dataOrColumn, mixed $value = null): bool
    {
        // Jika string, maka anggap format update('column', 'value')
        if (is_string($dataOrColumn) && $value !== null) {
            $data = [$dataOrColumn => $value];
        }
        // Jika array, maka itu array data
        elseif (is_array($dataOrColumn)) {
            $data = $dataOrColumn;
        } else {
            throw new \InvalidArgumentException("Invalid arguments passed to update()");
        }

        return $this->updateRecordWithTimestamps($this->conditions ?? [], $data);
    }

    public static function updateBy(string $column, $value, array $data): bool
    {
        $instance = new static();
        $where = [$column => $value];
        return $instance->updateRecordWithTimestamps($where, $data);
    }

    protected function updateRecordWithTimestamps($where, array $data): bool
    {
        if ($this->timestamps) {
            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $data['updated_at'] = $now;
        }
        return $this->updateRecord($where, $data);
    }

    protected function updateRecord($where, array $data): bool
    {
        $fillableData = array_intersect_key($data, array_flip($this->fillable));
        if (empty($fillableData)) {
            return false;
        }

        $setParts = [];
        foreach ($fillableData as $key => $value) {
            $setParts[] = "$key = :set_$key";
        }
        $setClause = implode(', ', $setParts);
        $params = [];
        $whereClause = '';

        if (isset($where[0]) && isset($where[0]['column']) && isset($where[0]['operator'])) {
            $parts = [];
            foreach ($where as $index => $condition) {
                $paramKey = ":where_{$condition['column']}_{$index}";
                $parts[] = "{$condition['column']} {$condition['operator']} $paramKey";
                $params[$paramKey] = $condition['value'];
            }
            $whereClause = implode(' AND ', $parts);
        }
        elseif (is_array($where)) {
            $parts = [];
            foreach ($where as $key => $value) {
                $paramKey = ':where_' . $key;
                $parts[] = "$key = $paramKey";
                $params[$paramKey] = $value;
            }
            $whereClause = implode(' AND ', $parts);
        }
        elseif (is_scalar($where)) {
            $whereClause = 'id = :where_id';
            $params[':where_id'] = $where;
        } else {
            return false;
        }

        foreach ($fillableData as $key => $value) {
            $params[":set_$key"] = $value;
        }

        $sql = "UPDATE {$this->table} SET $setClause WHERE $whereClause";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function where(string $column, $operatorOrValue, $value = null): static
    {
        $instance = new static();
        return $instance->whereInternal('AND', $column, $operatorOrValue, $value);
    }

    public function andWhere(string $column, $operatorOrValue, $value = null): static
    {
        return $this->whereInternal('AND', $column, $operatorOrValue, $value);
    }
    
    public function orWhere(string $column, $operatorOrValue, $value = null): static
    {
        return $this->whereInternal('OR', $column, $operatorOrValue, $value);
    }

    protected function whereInternal(string $type, string $column, $operatorOrValue, $value = null): static
    {
        $new = clone $this;

        if ($value === null) {
            $new->conditions[] = [
                'type' => $type,
                'column' => $column,
                'operator' => '=',
                'value' => $operatorOrValue
            ];
        } else {
            $new->conditions[] = [
                'type' => $type,
                'column' => $column,
                'operator' => strtoupper($operatorOrValue),
                'value' => $value
            ];
        }

        return $new;
    }

    public function delete(): bool
    {
        if (!empty($this->conditions)) {
            $compiled = [];
            foreach ($this->conditions as $condition) {
                if ($condition['type'] === 'AND') {
                    $compiled[$condition['column']] = $condition['value'];
                }
            }
    
            if (empty($compiled)) {
                throw new \Exception("Delete requires at least one condition.");
            }
    
            return $this->deleteRecord($compiled);
        }
    
        $primaryKey = $this->primaryKey ?? 'id';
    
        if (isset($this->{$primaryKey})) {
            return $this->deleteRecord($this->{$primaryKey});
        }
    
        throw new \Exception("Delete operation requires a WHERE clause or an instance with primary key.");
    }

    protected function deleteRecord($where): bool
    {
        if (is_scalar($where)) {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
            return $stmt->execute([':id' => $where]);
        }

        if (is_array($where)) {
            $parts = [];
            $params = [];

            foreach ($where as $key => $value) {
                $paramKey = ":$key";
                $parts[] = "$key = $paramKey";
                $params[$paramKey] = $value;
            }

            $whereClause = implode(' AND ', $parts);
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE $whereClause");
            return $stmt->execute($params);
        }

        return false;
    }

    public function get(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($this->conditions)) {
            $clauses = [];
            foreach ($this->conditions as $index => $cond) {
                if (!is_array($cond) || !isset($cond['column'], $cond['value'], $cond['type'])) {
                    continue;
                }

                $paramKey = ":param$index";
                $prefix = $index === 0 ? '' : $cond['type'] . ' ';
                $operator = $cond['operator'] ?? '=';
                $clauses[] = "$prefix{$cond['column']} $operator $paramKey";
                $params[$paramKey] = $cond['value'];
            }
            if ($clauses) {
                $sql .= ' WHERE ' . implode(' ', $clauses);
            }
        }

        if ($this->orderByColumn) {
            $sql .= " ORDER BY {$this->orderByColumn} {$this->orderDirection}";
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (!$data) return [];

        $caller = get_called_class();
        if (is_subclass_of($caller, \Velto\Core\Model\Model::class)) {
            $models = [];
            $ref = new \ReflectionClass($caller);
            foreach ($data as $row) {
                $model = new $caller();
                foreach ((array) $row as $key => $value) {
                    if ($ref->hasProperty($key)) {
                        $prop = $ref->getProperty($key);
                        $type = $prop->getType();
                        if (!$type || ($type instanceof \ReflectionNamedType && $type->allowsNull()) || $value !== null) {
                            $model->{$key} = $value;
                        }
                    } else {
                        $model->{$key} = $value;
                    }
                }
                $models[] = $model;
            }
            return $models;
        }

        return $data;
    }

    public function first(): ?object
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($this->conditions)) {
            $clauses = [];
            foreach ($this->conditions as $index => $cond) {
                $paramKey = ":param$index";
                $prefix = $index === 0 ? '' : $cond['type'] . ' ';
                $clauses[] = "$prefix{$cond['column']} {$cond['operator']} $paramKey";
                $params[$paramKey] = $cond['value'];
            }
            $sql .= ' WHERE ' . implode(' ', $clauses);
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$data) return null;

        $caller = get_called_class();
        $model = new $caller();
        foreach ((array) $data as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }

    public static function firstWhere(array $conditions): ?object
    {
        $instance = new static();

        foreach ($conditions as $column => $value) {
            $instance->where($column, $value);
        }

        return $instance->first();
    }
    
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($this->conditions)) {
            $clauses = [];
            foreach ($this->conditions as $index => $cond) {
                $paramKey = ":param$index";
                $prefix = $index === 0 ? '' : $cond['type'] . ' ';
                $clauses[] = "$prefix{$cond['column']} = $paramKey";
                $params[$paramKey] = $cond['value'];
            }
            $sql .= ' WHERE ' . implode(' ', $clauses);
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        return (int) ($result->count ?? 0);
    }

    public function paginate(int $perPage = 10, ?int $page = null): PaginatedResult
    {
        $page = $page ?? ($_GET['page'] ?? 1);
        $offset = ($page - 1) * $perPage;
        $params = [];

        $sql = "SELECT * FROM {$this->table}";
        $countSql = "SELECT COUNT(*) FROM {$this->table}";

        if (!empty($this->conditions)) {
            $clauses = [];
            foreach ($this->conditions as $index => $cond) {
                $paramKey = ":param$index";
                $prefix = $index === 0 ? '' : strtoupper($cond['type']) . ' ';
                $clauses[] = "$prefix{$cond['column']} {$cond['operator']} $paramKey";
                $params[$paramKey] = $cond['value'];
            }
            $where = ' WHERE ' . implode(' ', $clauses);
            $sql .= $where;
            $countSql .= $where;
        }

        $countStmt = $this->pdo->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        if ($this->orderByColumn) {
            $sql .= " ORDER BY {$this->orderByColumn} {$this->orderDirection}";
        }

        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $stmt->setFetchMode(PDO::FETCH_CLASS, static::class);
        $rows = $stmt->fetchAll() ?: [];

        return new PaginatedResult($rows, $page, $perPage, $total);
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderByColumn = $column;
        $this->orderDirection = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        return $this;
    }

    public function asc(string $column = 'id'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    public function desc(string $column = 'id'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    public static function search(string $term, array $columns = []): static
    {
        $term = trim($term);
        $instance = new static;

        // Empty input
        if ($term === '') {
            $instance->searchError = 'Search term cannot be empty.';
            return $instance;
        }

        // Too short
        if (mb_strlen($term) < 2) {
            $instance->searchError = 'Search term must be at least 2 characters.';
            return $instance;
        }

        // Dangerous characters or SQL injection keywords
        if (preg_match('/[\'"=;<>]|--|\b(SELECT|DROP|INSERT|DELETE|UPDATE|OR|AND)\b/i', $term)) {
            $instance->searchError = 'The search input contains disallowed characters.';
            return $instance;
        }

        // Sanitize basic
        $term = strip_tags($term);
        $term = htmlspecialchars($term, ENT_QUOTES, 'UTF-8');
        $term = str_replace(['%', '_'], ['\%', '\_'], $term);

        // Get searchable columns
        if (empty($columns) && property_exists($instance, 'searchable')) {
            $columns = $instance->searchable;
        }

        if (empty($columns)) {
            $instance->searchError = 'No searchable columns are defined.';
            return $instance;
        }

        // Apply search conditions
        $first = true;
        foreach ($columns as $column) {
            if ($first) {
                $instance = $instance->where($column, 'LIKE', "%$term%");
                $first = false;
            } else {
                $instance = $instance->orWhere($column, 'LIKE', "%$term%");
            }
        }

        return $instance;
    }

}


class PaginatedResult implements \IteratorAggregate, \Countable
{
    public array $items;
    public int $current_page;
    public int $per_page;
    public int $total;
    public int $last_page;

    public function __construct(array $items, int $currentPage, int $perPage, int $total)
    {
        $this->items = $items;
        $this->current_page = $currentPage;
        $this->per_page = $perPage;
        $this->total = $total;
        $this->last_page = (int) ceil($total / $perPage);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function __get($name)
    {
        return $this->{$name} ?? null;
    }
}
