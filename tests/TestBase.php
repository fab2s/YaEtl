<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Nodes\ExecNodeInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;
use fab2s\YaEtl\Loaders\LoaderInterface;
use fab2s\YaEtl\Loaders\NoOpLoader;

// we need these two for phpunit to mock NoOpLoader

/**
 * Interface TestLoaderInterface
 */
interface TestLoaderInterface extends NodeInterface, ExecNodeInterface, LoaderInterface
{
}

/**
 * Class TestLoader
 */
class TestLoader extends NoOpLoader implements TestLoaderInterface
{
}

/**
 * Class TestBase
 */
abstract class TestBase extends \PHPUnit\Framework\TestCase
{
    const FROM_TABLE = 'fromTable';
    const JOIN_TABLE = 'joinTable';
    const TO_TABLE   = 'toTable';

    /**
     * should be even as it is divided by two in some providers
     *
     * @var int
     */
    protected $numRecords = 42;

    /**
     * @var array
     */
    protected $expectedJoinRecords = [];

    /**
     * @var \PDO
     */
    protected static $pdo;

    /**
     * @return \PDO
     */
    public function getPdo()
    {
        if (static::$pdo === null) {
            static::$pdo = new \PDO('sqlite::memory:');

            static::$pdo->query('CREATE TABLE ' . self::FROM_TABLE . '(
                    id INTEGER PRIMARY KEY,
                    join_id DEFAULT NULL
                );'
            );

            static::$pdo->query('CREATE TABLE ' . self::JOIN_TABLE . '(
                    id INTEGER,
                    join_id INTEGER PRIMARY KEY,
                    FOREIGN KEY (id) REFERENCES ' . self::FROM_TABLE . ' (id)
                );'
            );

            static::$pdo->query('CREATE TABLE ' . self::TO_TABLE . '(
                    id INTEGER,
                    join_id INTEGER
                );'
            );
        }

        return static::$pdo;
    }

    /**
     * We mock loader to just gather all records than made
     * their way up there and return it to have the whole
     * Flow to return it and allow input / output comparison
     * The $spy will allow us to inspect invocations and arguments
     *
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public function getLoaderMock()
    {
        $stub = $this->getMockBuilder('TestLoader')
                ->setMethods(['exec'])
                ->getMock();

        $stub->expects($spy = $this->any())
            ->method('exec')
            ->will($this->returnCallback(
                function (array $param) {
                    $insert = [
                        'id'      => $param['id'],
                        'join_id' => $param['join_id'] ?? 'null',
                    ];

                    $this->getPdo()->query('INSERT INTO ' . self::TO_TABLE . ' (' . implode(',', array_keys($insert)) . ') VALUES (' . implode(',', $insert) . ')');
                }
            ));

        return $stub;
    }

    /**
     * @param string $table
     *
     * @return static
     */
    protected function populateTable(string $table): self
    {
        $keep = true;
        $j    = 0;
        for ($i = 1; $i <= $this->numRecords; ++$i) {
            switch ($table) {
                case self::FROM_TABLE:
                    $insert = [
                        'id'      => "$i",
                        'join_id' => 'null',
                    ];
                    break;
                case self::JOIN_TABLE:
                    if (!$keep) {
                        $keep = true;
                        break;
                    }

                    ++$j;
                    $insert = [
                        'id'      => "$i",
                        'join_id' => "$j",
                    ];

                    $this->expectedJoinRecords[$i] = $insert;

                    $keep = false;
                    break;
            }

            $this->getPdo()->query('INSERT OR IGNORE INTO ' . $table . ' (' . implode(',', array_keys($insert)) . ') VALUES (' . implode(',', $insert) . ')');
        }

        return $this;
    }

    protected function resetResultTable()
    {
        $this->getPdo()->query('DELETE FROM ' . self::TO_TABLE);

        return $this;
    }

    /**
     * @param string $table
     *
     * @return int
     */
    protected function getTableCount(string $table): int
    {
        return (int) $this->getPdo()->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }

    /**
     * @param string $table
     *
     * @return array
     */
    protected function getTableAll(string $table): array
    {
        return $this->getPdo()->query("SELECT * FROM $table ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
    }
}
