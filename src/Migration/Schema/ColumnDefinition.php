<?php

namespace Velto\Core\Migration\Schema;

class ColumnDefinition
{
    public $name;
    public $type;
    public $length;
    public $enumValues = null;
    public $unique = false;
    public $nullable = false;
    public $default = null;

    public function __construct($name, $type, $length = null)
    {
        $this->name = $name;
        $this->type = strtoupper($type);
        $this->length = $length;
    }

    public function enum(array $values)
    {
        $this->type = 'ENUM';
        $this->enumValues = array_map(function ($val) {
            return "'" . addslashes($val) . "'";
        }, $values);
        return $this;
    }

    public function unique()
    {
        $this->unique = true;
        return $this;
    }

    public function nullable()
    {
        $this->nullable = true;
        return $this;
    }

    public function default($value)
    {
        $this->default = $value;
        return $this;
    }

    public function toSQL(string $driver = 'mysql')
    {
        $parts = [];
    
        // ENUM support
        if ($this->type === 'ENUM' && is_array($this->enumValues)) {
            if ($driver === 'sqlite') {
                // Simulasikan ENUM sebagai TEXT di SQLite
                $typeSQL = "TEXT";
            } else {
                $typeSQL = "ENUM(" . implode(',', $this->enumValues) . ")";
            }
        } elseif ($this->type === 'BOOLEAN') {
            $typeSQL = ($driver === 'sqlite') ? "INTEGER" : "TINYINT(1)";
        } elseif ($this->length) {
            $typeSQL = "{$this->type}({$this->length})";
        } else {
            $typeSQL = $this->type;
        }
    
        $parts[] = "{$this->name} {$typeSQL}";
        $parts[] = $this->nullable ? "NULL" : "NOT NULL";
    
        if (!is_null($this->default)) {
            if (is_string($this->default)) {
                $default = "'" . addslashes($this->default) . "'";
            } elseif (is_bool($this->default)) {
                $default = $this->default ? '1' : '0';
            } else {
                $default = $this->default;
            }
    
            $parts[] = "DEFAULT {$default}";
        }
    
        if ($this->unique) {
            $parts[] = "UNIQUE";
        }
    
        return implode(" ", $parts);
    }
    
}
