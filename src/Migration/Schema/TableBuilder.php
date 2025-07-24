<?php

namespace Velto\Core\Migration\Schema;

class TableBuilder
{
    protected $columns = [];
    protected $driver;

    public function __construct($driver = 'mysql')
    {
        $this->driver = strtolower($driver);
    }

    protected array $constraints = [];

    public function unique(array $columns)
    {
        if ($this->driver === 'sqlite') {
            $this->constraints[] = "UNIQUE (" . implode(', ', $columns) . ")";
        } else {
            $name = 'unique_' . implode('_', $columns);
            $this->constraints[] = "CONSTRAINT {$name} UNIQUE (" . implode(', ', $columns) . ")";
        }
    }


    public function getConstraints(): array
    {
        return $this->constraints;
    }


    public function id($name = 'id')
    {
        if ($this->driver === 'sqlite') {
            $this->columns[] = "{$name} INTEGER PRIMARY KEY AUTOINCREMENT";
        } else {
            $this->columns[] = "{$name} INT PRIMARY KEY AUTO_INCREMENT";
        }
    }

    public function string($name, $length = 255)
    {
        return $this->addColumn(new ColumnDefinition($name, 'VARCHAR', $length));
    }

    public function text($name)
    {
        return $this->addColumn(new ColumnDefinition($name, 'TEXT'));
    }

    public function mediumText($name)
    {
        return $this->addColumn(new ColumnDefinition($name, 'MEDIUMTEXT'));
    }

    public function longText($name)
    {
        return $this->addColumn(new ColumnDefinition($name, 'LONGTEXT'));
    }

    public function integer($name)
    {
        return $this->addColumn(new ColumnDefinition($name, 'INTEGER'));
    }

    public function boolean($name)
    {
        return $this->addColumn(new ColumnDefinition($name, 'BOOLEAN'));
    }

    public function float($name, $precision = 8, $scale = 2)
    {
        return $this->addColumn(new ColumnDefinition($name, 'FLOAT'));
    }

    public function date($name)
    {
        return $this->addColumn(new ColumnDefinition($name, 'DATE'));
    }

    public function datetime($name)
    {
        return $this->addColumn(new ColumnDefinition($name, 'DATETIME'));
    }

    public function enum($name, array $values)
    {
        $column = new ColumnDefinition($name, 'ENUM');
        $column->enum($values);
        return $this->addColumn($column);
    }

    public function timestamps()
    {
        $this->datetime('created_at');
        $this->datetime('updated_at');
    }

    protected function addColumn(ColumnDefinition $column)
    {
        $this->columns[] = $column;
        return $column;
    }

    public function getColumnsSQL()
    {
        return implode(", ", array_map(function ($col) {
            return is_string($col) ? $col : $col->toSQL($this->driver);
        }, $this->columns));
    }

}
