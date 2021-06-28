<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(__DIR__ . '/tests/Fixtures/config')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_unsets' => true,
        'concat_space' => ['spacing' => 'one'],
        'return_type_declaration' => ['space_before' => 'one'],
        'no_unreachable_default_argument_value' => false,
        'yoda_style' => false,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'increment_style' => ['style' => 'pre'],
    ])
    ->setFinder($finder);
