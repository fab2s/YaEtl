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
    protected $seedNum = 50;

    /**
     * @var array
     */
    protected $testModelSeedData = [];

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
            $table->timestamps();
        });

        return $this;
    }

    protected function seedTestModelTable()
    {
        TestModel::insert(
            $this->getTestModelSeedData()
        );

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

    protected function getExpectedTestModelData(): array
    {
        $counter = 0;

        return array_map(
            function (array $value) use (&$counter) {
                return [
                    'id'         => ++$counter,
                    'name'       => $value['name'],
                    'created_at' => null,
                    'updated_at' => null,
                ];
            },
            $this->getTestModelSeedData()
        );
    }
}
