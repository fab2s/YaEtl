<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\YaEtlException;
use fab2s\YaEtl\Extractors\CallableExtractor;
use fab2s\YaEtl\Extractors\File\CsvExtractor;
use fab2s\YaEtl\Extractors\File\FileExtractorAbstract;
use fab2s\YaEtl\Extractors\File\LineExtractor;
use fab2s\YaEtl\Loaders\File\CsvLoader;
use fab2s\YaEtl\Transformers\CallableTransformer;
use fab2s\YaEtl\YaEtl;

/**
 * Class FileTest
 */
class FileTest extends \TestBase
{
    /**
     * @var array
     */
    protected $expectedCsv = [
        ['1', 'Sonsing', '思宇', 'Uganda', 'Kotido', "a\"6\nA'R`à1,;\h"],
        ['2', 'Cookley', '思宇', 'Poland', 'Leśna Podlaska', "a\"0L'F`àH,;f"],
        ['3', 'Prodder', '思宇', 'Yemen', 'Al Ḩazm', "o\"1H'O`à4,;c"],
        ['4', 'Alpha', '宇涵', 'China', 'Zhencheng', "d\"7N'Z`à4,;5"],
        ['5', 'Cardify', '昱漳', 'Philippines', "San\nCelestio", "y\"5H'O`àR,;e"],
        ['6', 'Tresom', '慧妍', 'Poland', 'Suwałki', "l\"1W'F`àP,;4"],
        ['7', 'Solarbreeze', '泽瀚', 'Brazil', 'Balsas', "t\"8H'H`àJ,;9"],
        ['8', 'Tampflex', '俞凯', 'Russia', 'Komsomol’sk', "k\"3A'P`àS,;8"],
        ['9', 'Cookley', '银含', 'Brazil', 'Itapecerica da Serra', "a\"1W'Z`à5,;k"],
        ['10', 'Rank', '彦歆', 'Armenia', 'Artsvanist', "a\"8K'A`àG,;c"],
    ];

    /**
     * @var array
     */
    protected $expectedCsvHeader = ['id', 'name', 'given_name', 'country', 'city', 'garbage'];

    /**
     * @dataProvider lineExtractorProvider
     *
     * @param string $srcPath
     * @param array  $expected
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function testLineExtractor($srcPath, array $expected)
    {
        $lineExtractor = new LineExtractor($srcPath);
        (new YaEtl)->from($lineExtractor)
            ->transform(new CallableTransformer(function ($line) use ($expected) {
                static $i = 0;
                $this->assertSame($expected['values'][$i], (int) trim($line));
                ++$i;
            }))
            ->exec();

        $this->assertSame($expected['encoding'], $lineExtractor->getEncoding());
    }

    /**
     * @dataProvider csvExtractorProvider
     *
     * @param string $srcPath
     * @param bool   $useHeader
     * @param array  $expected
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function testCsvExtractor($srcPath, $useHeader, array $expected)
    {
        $this->csvExtractorAssertions(new CsvExtractor($srcPath), $useHeader, $expected);
    }

    /**
     * @dataProvider csvExtractorProvider
     *
     * @param string $srcPath
     * @param bool   $useHeader
     * @param array  $expected
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function testCsvLoader($srcPath, $useHeader, array $expected)
    {
        $srcPath   = $this->getTmpFile();
        $sep       = $expected['sep'] ?: ',';
        $csvLoader = new CsvLoader($srcPath, $sep);
        $csvLoader->setUseSep((bool) $expected['sep']);
        if ($useHeader) {
            $csvLoader->setUseHeader(true)->setHeader($expected['header']);
        }

        if (!empty($expected['encoding'])) {
            $csvLoader->setUseBom(true)->setEncoding($expected['encoding']);
        }

        (new YaEtl)->from(new CallableExtractor(function () use ($expected) {
            return $expected['values'];
        }))->to($csvLoader)
            ->exec();

        // check if what we just wrote passes the read test
        $this->testCsvExtractor($srcPath, $useHeader, $expected);
    }

    /**
     * @return array
     */
    public function csvExtractorProvider()
    {
        return [
            [
                'src'       => __DIR__ . '/data/data_header_nl_eof.csv',
                'useHeader' => true,
                'expected'  => [
                    'values'   => $this->expectedCsv,
                    'header'   => $this->expectedCsvHeader,
                    'encoding' => null,
                    'sep'      => null,
                ],
            ],
            [
                'src'       => __DIR__ . '/data/data_header.csv',
                'useHeader' => true,
                'expected'  => [
                    'values'   => $this->expectedCsv,
                    'header'   => $this->expectedCsvHeader,
                    'encoding' => null,
                    'sep'      => null,
                ],
            ],
            [
                'src'       => __DIR__ . '/data/data.csv',
                'useHeader' => false,
                'expected'  => [
                    'values'   => $this->expectedCsv,
                    'header'   => null,
                    'encoding' => null,
                    'sep'      => null,
                ],
            ],
            [
                'src'       => __DIR__ . '/data/data_header_bom_utf8.csv',
                'useHeader' => true,
                'expected'  => [
                    'values'   => $this->expectedCsv,
                    'header'   => $this->expectedCsvHeader,
                    'encoding' => 'UTF-8',
                    'sep'      => null,
                ],
            ],
            [
                'src'       => __DIR__ . '/data/data_header_sep.csv',
                'useHeader' => true,
                'expected'  => [
                    'values'   => $this->expectedCsv,
                    'header'   => $this->expectedCsvHeader,
                    'encoding' => null,
                    'sep'      => ';',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function lineExtractorProvider()
    {
        return [
            [
                'src'      => __DIR__ . '/data/lines_nl_eof',
                'expected' => [
                    'values'   => range(1, 10),
                    'encoding' => null,
                ],
            ],
            [
                'src'      => __DIR__ . '/data/lines',
                'expected' => [
                    'values'   => range(1, 10),
                    'encoding' => null,
                ],
            ],
            [
                'src'      => __DIR__ . '/data/lines_bom_utf8',
                'expected' => [
                    'values'   => range(1, 10),
                    'encoding' => 'UTF-8',
                ],
            ],
        ];
    }

    /**
     * @param FileExtractorAbstract $csvExtractor
     * @param                       $useHeader
     * @param array                 $expected
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    protected function csvExtractorAssertions(FileExtractorAbstract $csvExtractor, $useHeader, array $expected)
    {
        /* @var  CsvExtractor $csvExtractor */
        $csvExtractor->setUseHeader($useHeader);

        (new YaEtl)->from($csvExtractor)
            ->transform(new CallableTransformer(function ($record) use ($csvExtractor, $expected, $useHeader) {
                static $i = 0;
                $expected = $useHeader ? array_combine($csvExtractor->getHeader(), $expected['values'][$i]) : $expected['values'][$i];
                $this->assertSame($expected, $record);
                ++$i;
            }))
            ->exec();

        $this->assertSame($expected['header'], $csvExtractor->getHeader());
        $this->assertSame($expected['encoding'], $csvExtractor->getEncoding());
    }

    /**
     * @return bool|string
     */
    protected function getTmpFile()
    {
        return tempnam(sys_get_temp_dir(), 'Cl_');
    }
}
