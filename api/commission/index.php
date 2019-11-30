<?php
include_once __DIR__ . '/../../vendor/autoload.php';

use PaySystem\Service\Cash\Commission;

if (!empty($argv[1])) {
    $commission = new Commission();
    $data = $commission->get($argv[1]);

    if (!empty($data)) {
        foreach ($data as $d) {
            echo $d . PHP_EOL;
        }
    } else {
        echo $commission->errors->getErrors();
    }
} else {
    echo 'Empty name file.';
}