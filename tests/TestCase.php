<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Nodes\ExecNodeInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;
use fab2s\YaEtl\Loaders\LoaderInterface;
use fab2s\YaEtl\Loaders\NoOpLoader;

// we need these two for phpunit to properly mock NoOpLoader
// doing this allows us to use phpunit awesome spies
interface TestLoaderInterface extends NodeInterface, ExecNodeInterface, LoaderInterface
{
}
class TestLoader extends NoOpLoader implements TestLoaderInterface
{
}

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use PHPUnit_Extensions_Database_TestCase_Trait;

    const FROM_TABLE             = 'fromTable';
    const JOIN_TABLE             = 'joinTable';
    const JOIN_RESULT_TABLE      = 'joinResultTable';
    const LEFT_JOIN_RESULT_TABLE = 'leftJoinResultTable';
    const TO_TABLE               = 'toTable';

    /**
     * @var array
     */
    protected $mocked = [];

    /**
     * should be even as it is divided by two in some providers
     *
     * @var int
     */
    protected $numRecords = 20;

    /**
     * @var \PDO
     */
    private static $pdo;

    /**
     * @var PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    private $conn;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    final public function getConnection()
    {
        if ($this->conn === null) {
            $this->conn = $this->createDefaultDBConnection($this->getPdo(), ':memory:');
        }

        return $this->conn;
    }

    /**
     * @return \PDO
     */
    public function getPdo()
    {
        if (self::$pdo == null) {
            self::$pdo = new PDO('sqlite::memory:');
            self::$pdo->query('CREATE DATABASE test; use test;');
            self::$pdo->query('CREATE TABLE ' . self::FROM_TABLE . '(
                id INTEGER PRIMARY KEY,
                join_id DEFAULT NULL
            );');
            self::$pdo->query('CREATE TABLE ' . self::JOIN_TABLE . '(
                id INTEGER,
                join_id INTEGER PRIMARY KEY,
                FOREIGN KEY (id) REFERENCES ' . self::FROM_TABLE . ' (id)
            );');
            self::$pdo->query('CREATE TABLE ' . self::JOIN_RESULT_TABLE . '(
                id INTEGER,
                join_id INTEGER PRIMARY KEY,
                FOREIGN KEY (id) REFERENCES ' . self::FROM_TABLE . ' (id)
            );');
            self::$pdo->query('CREATE TABLE ' . self::LEFT_JOIN_RESULT_TABLE . '(
                id INTEGER,
                join_id INTEGER,
                FOREIGN KEY (id) REFERENCES ' . self::FROM_TABLE . ' (id)
            );');
            self::$pdo->query('CREATE TABLE ' . self::TO_TABLE . '(
                id INTEGER,
                join_id INTEGER
            );');
        }

        return self::$pdo;
    }

    /**
     * We mock loader to just gather all records than made
     * their way up there and return it to have the whole
     * Flow to return it and allow input / output comparison
     * The $spy will allow us to inspect invocations and arguments
     *
     * @return array
     */
    public function getLoaderMock()
    {
        $stub = $this->getMockBuilder('TestLoader')
                ->setMethods(['exec'])
                ->getMock();

        $stubPdo = $this->getPdo();
        $stub->expects($spy = $this->any())
            ->method('exec')
            ->will($this->returnCallback(
                function ($param = null) use ($stubPdo) {
                    $insert = [
                        'id'      => $param['id'],
                        'join_id' => isset($param['join_id']) ? $param['join_id'] : 'null',
                    ];

                    $stubPdo->query('INSERT INTO ' . self::TO_TABLE . ' (' . implode(',', array_keys($insert)) . ') VALUES (' . implode(',', $insert) . ')');
                }
            ));

        return $this->registerMock($stub, $spy);
    }

    protected function getDataSet()
    {
        $max    = $this->numRecords;
        $result = [
            self::FROM_TABLE             => [],
            self::JOIN_TABLE             => [],
            self::JOIN_RESULT_TABLE      => [],
            self::LEFT_JOIN_RESULT_TABLE => [],
            self::TO_TABLE               => [],
        ];

        $from           = &$result[self::FROM_TABLE];
        $join           = &$result[self::JOIN_TABLE];
        $joinResult     = &$result[self::JOIN_RESULT_TABLE];
        $leftJoinResult = &$result[self::LEFT_JOIN_RESULT_TABLE];

        $keep = false;
        $j    = 1;
        for ($i = 1; $i <= $max; ++$i) {
            $from[] = [
                'id'      => $i,
                'join_id' => null,
            ];

            if ($keep) {
                $join[] = [
                    'id'      => $i,
                    'join_id' => $j,
                ];

                $leftJoinResult[] = [
                    'id'      => $i,
                    'join_id' => $j,
                ];

                $joinResult[] = [
                    'id'      => $i,
                    'join_id' => $j,
                ];

                ++$j;

                $keep = false;
            } else {
                $keep             = true;
                $leftJoinResult[] = [
                    'id'      => $i,
                    'join_id' => null,
                ];
            }
        }

        return new DbUnitArrayDataSet($result);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject              $mock
     * @param \PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount $spy
     *
     * @return array
     */
    protected function registerMock(\PHPUnit_Framework_MockObject_MockObject $mock, \PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount $spy)
    {
        $hash                = $this->getObjectHash($mock);
        $this->mocked[$hash] = [
            'mock' => $mock,
            'spy'  => $spy,
            'hash' => $hash,
        ];

        return $this->mocked[$hash];
    }

    /**
     * @param object $object
     *
     * @return string
     */
    protected function getObjectHash($object)
    {
        return \sha1(\spl_object_hash($object));
    }
}
