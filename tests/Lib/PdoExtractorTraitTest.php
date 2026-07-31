<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib;

use fab2s\YaEtl\Extractors\PdoExtractor;
use PDO;
use Pdo\Mysql;

/**
 * Class PdoExtractorTraitTest
 */
class PdoExtractorTraitTest extends TestBase
{
    public function test_buffered_query_attribute()
    {
        if (!extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql is required to resolve MYSQL_ATTR_USE_BUFFERED_QUERY');
        }

        $deprecations = [];
        set_error_handler(function (int $errno, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, E_DEPRECATED);

        try {
            $attribute = PdoExtractor::bufferedQueryAttribute();
        } finally {
            restore_error_handler();
        }

        // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY is deprecated since php 8.5
        $this->assertSame([], $deprecations);
        $this->assertSame(
            PHP_VERSION_ID >= 80400 ? Mysql::ATTR_USE_BUFFERED_QUERY : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
            $attribute
        );
    }
}
