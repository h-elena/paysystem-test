<?php
declare(strict_types=1);

namespace PaySystem\Service\Cash;

use PaySystem\Helpers\Errors;
use PaySystem\Helpers\Files;

class Commission
{
    /**
     * Percent for cash in
     * @var float $percentIn
     */
    protected $percentIn;

    /**
     * Maximum value of the percent for cash in
     * @var float $maxValueInEUR
     */
    protected $maxValueInEUR;

    /**
     * Percent for cash out
     * @var float $percentOut
     */
    protected $percentOut;

    /**
     * Minimum value of the percent for cash out for legal persons
     * @var float $minValueOutLegalEUR
     */
    protected $minValueOutLegalEUR;

    /**
     * Maximum sum for cash out in week without percent for natural persons
     * @var float $maxSumFreeOnWeekOutNaturalEUR
     */
    protected $maxSumFreeOnWeekOutNaturalEUR;

    /**
     * Count free firsts operation in week
     * @var integer $countFreeOperationOutNatural
     */
    protected $countFreeOperationOutNatural;

    /** @var Errors $errors */
    public $errors;

    /**
     * Exchange rate euro to dollar
     * @var float $exchangeRateUSD
     */
    protected $exchangeRateUSD;

    /**
     * Exchange rate euro to yen
     * @var float $exchangeRateJPY
     */
    protected $exchangeRateJPY;

    public function __construct()
    {
        //these parameters change periodically. they should be in the database.
        $this->percentIn = 0.03;
        $this->maxValueInEUR = 5;
        $this->percentOut = 0.3;
        $this->minValueOutLegalEUR = 0.5;
        $this->maxSumFreeOnWeekOutNaturalEUR = 1000;
        $this->countFreeOperationOutNatural = 3;
        $this->exchangeRateUSD = 1.1497;
        $this->exchangeRateJPY = 129.53;
        //

        $this->errors = new Errors();
    }

    /**
     * Exchange sum in EUR
     *
     * @param float $sum
     * @param string $currency
     * @return float
     */
    public function exchangeInEUR(float $sum, string $currency): float
    {
        switch ($currency) {
            case 'EUR':
                return $sum;
            case 'USD':
                return $sum * (1 / $this->exchangeRateUSD);
            case 'JPY':
                return $sum * (1 / $this->exchangeRateJPY);
        }

        return 0;
    }

    /**
     * Exchange euro in other currency
     *
     * @param float $sum
     * @param string $currency
     * @return float
     */
    public function exchangeEUR(float $sum, string $currency): float
    {
        switch ($currency) {
            case 'EUR':
                return $sum;
            case 'USD':
                return $sum * $this->exchangeRateUSD;
            case 'JPY':
                return $sum * $this->exchangeRateJPY;
        }

        return 0;
    }

    /**
     * Calculate commission from cash in for person
     *
     * @param array $data
     * @return float
     */
    protected function calculateCashIn(array $data): float
    {
        $commission = $data[4] * ($this->percentIn / 100);

        if ($commission > $this->exchangeEUR($this->maxValueInEUR, $data[5])) {
            $commission = $this->exchangeEUR($this->maxValueInEUR, $data[5]);
        }

        return $commission;
    }

    /**
     * Calculate commission from cash out for legal person
     *
     * @param array $data
     * @return float
     */
    protected function calculateCashOutLegal(array $data): float
    {
        $commission = $data[4] * ($this->percentOut / 100);

        if ($commission < $this->exchangeEUR($this->minValueOutLegalEUR, $data[5])) {
            $commission = $this->exchangeEUR($this->minValueOutLegalEUR, $data[5]);
        }

        return $commission;

    }

    /**
     * Calculate commission from cash out for natural person
     *
     * @param array $data
     * @param array $operations
     * @return float
     * @throws \Exception
     */
    protected function calculateCashOutNatural(array $data, array $operations): float
    {
        $countDays = 0;
        $sumEUR = 0;

        if (!empty($operations[$data[1]])) {
            $startWeek = (new \DateTime($data[0]))->modify('monday this week');
            $endWeek = (new \DateTime($data[0]))->modify('sunday this week');

            foreach ($operations[$data[1]] as $key => $operation) {
                if ((new \DateTime($key))->diff($startWeek)->days >= 0 &&
                    (new \DateTime($key))->diff($endWeek)->days <= 6) {
                    $countDays++;
                    $sumEUR += $this->exchangeInEUR((float)$operation['sum'], $operation['currency']);

                    if ($countDays > $this->countFreeOperationOutNatural || $sumEUR > $this->maxSumFreeOnWeekOutNaturalEUR) {
                        break;
                    }
                }
            }
        }

        $data[4] = (float)$data[4];

        if ($countDays > 3 || $sumEUR > $this->maxSumFreeOnWeekOutNaturalEUR) {
            $commission = $data[4] * ($this->percentOut / 100);
        } elseif (($sumEUR + $this->exchangeInEUR($data[4], $data[5])) > $this->maxSumFreeOnWeekOutNaturalEUR) {
            $sum = $sumEUR + $this->exchangeInEUR($data[4], $data[5]) - $this->maxSumFreeOnWeekOutNaturalEUR;
            $commission = $this->exchangeEUR($sum, $data[5]) * ($this->percentOut / 100);
        } else {
            $commission = 0;
        }

        return $commission;
    }

    /**
     * Calculate commission
     *
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function calculate(array $data): array
    {
        $operations = [];
        $result = [];

        foreach ($data as $d) {
            $commission = 0;

            if ($d[3] == 'cash_in') {
                $commission = $this->calculateCashIn($d);
            } elseif ($d[3] == 'cash_out') {
                if ($d[2] == 'natural') {
                    $commission = $this->calculateCashOutNatural($d, $operations);
                    $operations[$d[1]][$d[0]] = ['sum' => $d[4], 'currency' => $d[5]];
                } elseif ($d[2] == 'legal') {
                    $commission = $this->calculateCashOutLegal($d);
                }
            }

            $result[] = number_format(($commission > 0 ? ceil($commission * 100) / 100 : 0), 2, '.', '');
        }

        return $result;
    }

    /**
     * Get file and calculate commission
     *
     * @param string $file
     * @return array|bool
     * @throws \Exception
     */
    public function get(string $file): array
    {
        $files = new Files();
        $data = $files->getDataFromCsvFile($file);

        if (empty($data)) {
            $this->errors->setError($files->errors->getErrors());

            return [];
        }

        return $this->calculate($data);
    }
}