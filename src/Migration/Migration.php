<?php

namespace Velto\Core\Migration;

use PDO;
use Velto\Core\Migration\Schema\TableBuilder;
use Veltophp\VeltoCli\Config\Helpers;

class Migration
{
    protected $db;
    protected $driver;

    public function __construct()
    {
        $this->db = Helpers::getPdoConnection(BASE_PATH);
        $this->driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function createTable($tableName, callable $callback)
    {
        $builder = new TableBuilder($this->driver);
        $callback($builder);
        $columns = $builder->getColumnsSQL();
        $constraints = $builder->getConstraints();

        $allParts = array_filter(array_merge(
            explode(',', $columns),
            $constraints
        ), fn($line) => trim($line) !== '');

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (" . implode(', ', $allParts) . ")";
        $this->db->exec($sql);
    }

    public function dropTable($tableName)
    {
        $sql = "DROP TABLE IF EXISTS {$tableName}";
        $this->db->exec($sql);
    }
}
