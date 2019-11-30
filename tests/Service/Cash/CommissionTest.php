<?php
declare(strict_types=1);

namespace PaySystem\Tests\Service\Cash;

use PHPUnit\Framework\TestCase;
use PaySystem\Service\Cash\Commission;

class CommissionTest extends TestCase
{
    /**
     * @var Commission
     */
    private $commission;

    public function setUp()
    {
        $this->commission = new Commission();
    }

    public function dataProviderForCalculateTesting(): array
    {
        return [
            'calculate cash in for natural person' => [[['2016-01-05', 1, 'natural', 'cash_in', 212.25, 'EUR']], [0.07]],
            'calculate cash out for natural person' => [[['2016-01-05', 4, 'natural', 'cash_out', 1000.00, 'EUR']], [0.00]],
            'calculate cash in for legal person' => [[['2016-01-10', 2, 'legal', 'cash_in', 1000000.00, 'EUR']], [5.00]],
            'calculate cash out for legal person' => [[['2016-01-07', 1, 'natural', 'cash_out', 1000.00, 'EUR']], [0.00]]
        ];
    }

    /**
     * @param array $data
     * @param array $expectation
     * @throws \Exception
     *
     * @dataProvider dataProviderForCalculateTesting
     */
    public function testCalculate(array $data, array $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->commission->calculate($data)
        );
    }

    public function dataProviderForExchangeInEURTesting(): array
    {
        return [
            'calculate EUR in EUR' => [5, 'EUR', 5],
            'calculate USD in EUR' => [5, 'USD', 4.35],
            'calculate JPY in EUR' => [5, 'JPY', 0.04],
            'calculate RUB in EUR' => [5, 'RUB', 0]
        ];
    }

    /**
     * @param float $sum
     * @param string $currency
     * @param string $expectation
     *
     * @dataProvider dataProviderForExchangeInEURTesting
     */
    public function testExchangeInEUR(float $sum, string $currency, string $expectation)
    {
        $this->assertEquals(
            $expectation,
            round($this->commission->exchangeInEUR($sum, $currency), 2)
        );
    }

    public function dataProviderForExchangeEURTesting(): array
    {
        return [
            'calculate EUR in EUR' => [5, 'EUR', 5],
            'calculate USD in EUR' => [5, 'USD', 5.75],
            'calculate JPY in EUR' => [5, 'JPY', 647.65],
            'calculate RUB in EUR' => [5, 'RUB', 0]
        ];
    }

    /**
     * @param float $sum
     * @param string $currency
     * @param string $expectation
     *
     * @dataProvider dataProviderForExchangeEURTesting
     */
    public function testExchangeEUR(float $sum, string $currency, string $expectation)
    {
        $this->assertEquals(
            $expectation,
            round($this->commission->exchangeEUR($sum, $currency), 2)
        );
    }

    public function dataProviderForGetTesting(): array
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
     * @dataProvider dataProviderForGetTesting
     */
    public function testGet(string $file, string $expectation)
    {
        $this->commission->get($file);

        $this->assertEquals(
            $expectation,
            $this->commission->errors->getErrors()
        );
    }
}