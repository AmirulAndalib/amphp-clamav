<?php

$config = new Amp\CodeStyle\Config;
$config->getFinder()
    ->in(__DIR__ . '/examples')
    ->in(__DIR__ . '/lib')
    ->in(__DIR__ . '/tests');

$config->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');

return $config;
