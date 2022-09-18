<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Laravel;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

abstract class LaravelTestCase extends TestCase
{
    /**
     * @var int
     */
    protected $seedNum = 3;

    /**
     * @var array
     */
    protected $testModelSeedData = [];

    /**
     * @var array
     */
    protected $testModelJoinSeedData = [];

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestModelTable()
            ->seedTestModelTable();
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function createTestModelTable()
    {
        Schema::dropIfExists(TestModel::TABLE);
        Schema::create(TestModel::TABLE, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        return $this;
    }

    protected function createTestJoinModelTable()
    {
        Schema::dropIfExists(TestJoinModel::TABLE);
        Schema::create(TestJoinModel::TABLE, function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('model_id')->unique();
            $table->string('join');
        });

        return $this;
    }

    protected function seedTestModelTable()
    {
        TestModel::insert($this->getTestModelSeedData());

        return $this;
    }

    protected function seedTestJoinModelTable(bool $every = true)
    {
        TestJoinModel::insert($this->getTestJoinModelSeedData($every));

        return $this;
    }

    protected function getTestModelSeedData(): array
    {
        if (isset($this->testModelSeedData[$this->seedNum])) {
            return $this->testModelSeedData[$this->seedNum];
        }

        $result = [];
        for ($i = 1; $i <= $this->seedNum; ++$i) {
            $result[] = [
                'name' => "name_$i",
            ];
        }

        return $this->testModelSeedData[$this->seedNum] = $result;
    }

    protected function getTestJoinModelSeedData(bool $every = true): array
    {
        if (isset($this->testModelJoinSeedData[$this->seedNum][$every])) {
            return $this->testModelJoinSeedData[$this->seedNum][$every];
        }

        $result  = [];
        $counter = 0;
        foreach ($this->getTestModelSeedData() as $testModel) {
            ++$counter;
            if (!$every && $counter % 2) {
                continue;
            }

            $result[] = [
                'model_id' => $counter,
                'join'     => "join_$counter",
            ];
        }

        return $this->testModelJoinSeedData[$this->seedNum][$every] = $result;
    }

    protected function getExpectedTestModelData(): array
    {
        $counter = 0;

        return array_map(
            function (array $value) use (&$counter) {
                return [
                    'id'         => ++$counter,
                    'name'       => $value['name'],
                ];
            },
            $this->getTestModelSeedData()
        );
    }

    protected function getExpectedTestJoinModelData(bool $every = true): array
    {
        $counter = 0;
        $modelId = 0;
        $result  = [];
        foreach ($this->getTestJoinModelSeedData() as $testModel) {
            ++$modelId;
            if (!$every && $modelId % 2) {
                continue;
            }

            ++$counter;
            $result[] = [
                'id'       => $counter,
                'model_id' => $modelId,
                'join'     => "join_$modelId",
            ];
        }

        return $result;
    }
}
