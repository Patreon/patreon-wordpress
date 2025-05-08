<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in(__DIR__)
;

return (new Config())
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
