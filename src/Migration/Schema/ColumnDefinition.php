<?php

namespace Velto\Core\Migration\Schema;

class ColumnDefinition
{
    public $name;
    public $type;
    public $length;
    public $unique = false;
    public $nullable = false;
    public $default = null;

    public function __construct($name, $type, $length = null)
    {
        $this->name = $name;
        $this->type = strtoupper($type);
        $this->length = $length;
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

    public function toSQL()
    {
        $type = $this->type;

        if ($type === 'BOOLEAN') {
            $type = 'TINYINT(1)';
        }

        $parts = [];

        if ($this->length) {
            $parts[] = "{$this->name} {$type}({$this->length})";
        } else {
            $parts[] = "{$this->name} {$type}";
        }

        if ($this->nullable) {
            $parts[] = "NULL";
        } else {
            $parts[] = "NOT NULL";
        }

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
