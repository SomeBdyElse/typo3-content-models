<?php

$extensionRoot = dirname(__DIR__, 2);

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->addRules([
    'single_line_empty_body' => false,
]);
$config->getFinder()
    ->in($extensionRoot)
    ->exclude(['Classes/Model'])
;

return $config;
