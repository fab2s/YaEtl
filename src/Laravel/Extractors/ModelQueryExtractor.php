<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Laravel\Extractors;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Extractors\DbExtractorAbstract;
use fab2s\YaEtl\YaEtlException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ModelQueryExtractor
 */
class ModelQueryExtractor extends DbExtractor
{
    /**
     * Instantiate the ModelQueryExtractor
     *
     * @param Builder|null $extractQuery
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function __construct(?Builder $extractQuery = null)
    {
        if ($extractQuery !== null) {
            $this->setExtractQuery($extractQuery);
        }

        parent::__construct();
    }

    /**
     * Set the extract query
     *
     * @param Builder $extractQuery
     *
     * @throws YaEtlException
     *
     * @return static
     */
    public function setExtractQuery($extractQuery): DbExtractorAbstract
    {
        if (!($extractQuery instanceof Builder)) {
            throw new YaEtlException('Argument 1 passed to ' . __METHOD__ . ' must be an instance of ' . Builder::class . ', ' . \gettype($extractQuery) . ' given');
        }

        return parent::setExtractQuery($extractQuery->toBase());
    }
}
