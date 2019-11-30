<?php
declare(strict_types=1);

namespace PaySystem\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use PaySystem\Helpers\Files;

class FilesTest extends TestCase
{
    /**
     * @var Files
     */
    private $file;

    public function setUp()
    {
        $this->file = new Files();
    }

    public function dataProviderForGetDataFromCsvFileTesting(): array
    {
        return [
            'calculate from not exist file' => ['test.txt', 'The file is not exist']
        ];
    }

    /**
     * @param string $file
     * @param string $expectation
     * @throws \Exception
     *
     * @dataProvider dataProviderForGetDataFromCsvFileTesting
     */
    public function testGetDataFromCsvFile(string $file, string $expectation)
    {
        $this->file->getDataFromCsvFile($file);

        $this->assertEquals(
            $expectation,
            $this->file->errors->getErrors()
        );
    }
}