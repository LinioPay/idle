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
        'increment_style' => ['style' => 'pre'],
        'phpdoc_align' => ['align' => 'left'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'ordered_class_elements' =>  [
            'order' =>
                [
                    'use_trait', 'constant_public', 'constant_protected', 'constant_private', 'property_public',
                    'property_protected', 'property_private', 'construct', 'destruct', 'magic', 'phpunit',
                    'method_public', 'method_public_abstract', 'method_protected_abstract', 'method_protected',
                    'method_private', 'method_public_abstract_static', 'method_protected_abstract_static',
                    'method_public_static', 'method_protected_static',
                ],
            'sort_algorithm' => 'alpha'
        ],
    ])
    ->setFinder($finder);
