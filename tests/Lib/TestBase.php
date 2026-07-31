<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib;

use Closure;
use fab2s\NodalFlow\Nodes\ExecNodeInterface;
use fab2s\YaEtl\Loaders\LoaderInterface;
use fab2s\YaEtl\Loaders\NoOpLoader;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

// we need these two for phpunit to mock NoOpLoader

/**
 * Interface TestLoaderInterface
 */
interface TestLoaderInterface extends ExecNodeInterface, LoaderInterface {}

/**
 * Class TestLoader
 */
class TestLoader extends NoOpLoader implements TestLoaderInterface {}

/**
 * Class InsertLoader - A concrete loader that inserts into the test table
 */
class InsertLoader extends NoOpLoader
{
    public function exec($param = null)
    {
        $insert = [
            'id'      => $param['id'],
            'join_id' => $param['join_id'] ?? 'null',
        ];

        TestBase::getPdo()->query('INSERT INTO ' . TestBase::TO_TABLE . ' (' . implode(',', array_keys($insert)) . ') VALUES (' . implode(',', $insert) . ')');
    }
}

/**
 * Class TestBase
 */
abstract class TestBase extends TestCase
{
    const FROM_TABLE = 'fromTable';
    const JOIN_TABLE = 'joinTable';
    const TO_TABLE   = 'toTable';

    /**
     * should be even as it is divided by two in some providers
     */
    protected static int $numRecords            = 42;
    protected static array $expectedJoinRecords = [];

    /**
     * @var PDO
     */
    protected static $pdo;

    /**
     * Check if SQLite PDO driver is available.
     */
    public static function hasSqliteDriver(): bool
    {
        return in_array('sqlite', PDO::getAvailableDrivers(), true);
    }

    public static function getPdo(): PDO
    {
        if (static::$pdo === null) {
            static::$pdo = new PDO('sqlite::memory:');

            static::$pdo->query(
                'CREATE TABLE ' . self::FROM_TABLE . '(
                    id INTEGER PRIMARY KEY,
                    join_id DEFAULT NULL
                );',
            );

            static::$pdo->query(
                'CREATE TABLE ' . self::JOIN_TABLE . '(
                    id INTEGER,
                    join_id INTEGER PRIMARY KEY,
                    FOREIGN KEY (id) REFERENCES ' . self::FROM_TABLE . ' (id)
                );',
            );

            static::$pdo->query(
                'CREATE TABLE ' . self::TO_TABLE . '(
                    id INTEGER,
                    join_id INTEGER
                );',
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
     * @return MockObject
     */
    public function getLoaderMock()
    {
        $stub = $this->getMockBuilder(TestLoader::class)
            ->setMethods(['exec'])
            ->getMock()
        ;

        $stub->expects($spy = $this->any())
            ->method('exec')
            ->will($this->returnCallback(
                function (array $param) {
                    $insert = [
                        'id'      => $param['id'],
                        'join_id' => $param['join_id'] ?? 'null',
                    ];

                    $this->getPdo()->query('INSERT INTO ' . self::TO_TABLE . ' (' . implode(',', array_keys($insert)) . ') VALUES (' . implode(',', $insert) . ')');
                },
            ))
        ;

        return $stub;
    }

    /**
     * @return static
     */
    protected function populateTable(string $table): self
    {
        $keep = true;
        $j    = 0;
        for ($i = 1; $i <= self::$numRecords; $i++) {
            switch ($table) {
                case self::FROM_TABLE:
                    $insert = [
                        'id'      => "$i",
                        'join_id' => 'null',
                    ];
                    break;
                case self::JOIN_TABLE:
                    if (! $keep) {
                        $keep = true;
                        break;
                    }

                    $j++;
                    $insert = [
                        'id'      => "$i",
                        'join_id' => "$j",
                    ];

                    self::$expectedJoinRecords[$i] = $insert;

                    $keep = false;
                    break;
            }

            self::getPdo()->query('INSERT OR IGNORE INTO ' . $table . ' (' . implode(',', array_keys($insert)) . ') VALUES (' . implode(',', $insert) . ')');
        }

        return $this;
    }

    protected function resetResultTable()
    {
        $this->getPdo()->query('DELETE FROM ' . self::TO_TABLE);

        return $this;
    }

    protected function getTableCount(string $table): int
    {
        return (int) $this->getPdo()->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }

    protected function getTableAll(string $table): array
    {
        return $this->getPdo()->query("SELECT * FROM $table ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    protected static function getTraversableClosure(int $count, int $start = 1): Closure
    {
        $start = max(1, $start);
        $count = $start === 1 ? $count : $count + $start - 1;

        return function () use ($count, $start) {
            for ($i = $start; $i <= $count; $i++) {
                yield $i;
            }
        };
    }
}
