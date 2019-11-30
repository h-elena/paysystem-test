<?php
declare(strict_types=1);

namespace PaySystem\Helpers;

class Files
{
    /** @var Errors $errors */
    public $errors;

    public function __construct()
    {
        $this->errors = new Errors();
    }

    /**
     * Get data from file
     *
     * @param string $file - full path to file
     * @return array
     */
    public function getDataFromCsvFile($file): array
    {
        $result = [];

        if (file_exists($file) && (new \SplFileInfo($file))->getExtension() == 'csv') {
            $handle = @fopen($file, 'r');

            if ($handle) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $result[] = $data;
                }

                fclose($handle);
            } else {
                $this->errors->setError('The file is not open');
            }
        } else {
            $this->errors->setError('The file is not exist');
        }

        return $result;
    }
}