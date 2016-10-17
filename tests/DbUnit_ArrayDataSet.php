<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

class DbUnit_ArrayDataSet extends \PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{
    /**
     * @var array
     */
    protected $tables = [];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $tableName => $rows) {
            $columns = [];
            if (isset($rows[0])) {
                $columns = array_keys($rows[0]);
            }

            $metaData = new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData($tableName, $columns);
            $table    = new PHPUnit_Extensions_Database_DataSet_DefaultTable($metaData);

            foreach ($rows as $row) {
                $table->addRow($row);
            }

            $this->tables[$tableName] = $table;
        }
    }

    /**
     * @param string $tableName
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function getTable($tableName)
    {
        if (!isset($this->tables[$tableName])) {
            throw new InvalidArgumentException("$tableName is not a table in the current database.");
        }

        return $this->tables[$tableName];
    }

    /**
     * @param bool $reverse
     *
     * @return \PHPUnit_Extensions_Database_DataSet_DefaultTableIterator
     */
    protected function createIterator($reverse = false)
    {
        return new PHPUnit_Extensions_Database_DataSet_DefaultTableIterator($this->tables, $reverse);
    }
}
