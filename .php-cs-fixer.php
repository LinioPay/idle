<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return  (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_unsets' => true,
        'concat_space' => ['spacing' => 'one'],
        'return_type_declaration' => ['space_before' => 'one'],
        'no_unreachable_default_argument_value' => false,
        'yoda_style' => false,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'increment_style' => ['style' => 'post'],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_no_alias_tag' => false,
        'ordered_class_elements' =>  [
            'order' =>
                [
                    'use_trait', 'constant_public', 'constant_protected', 'constant_private', 'property_public',
                    'property_private', 'property_protected', 'construct', 'destruct', 'magic', 'phpunit',
                    'method_public_abstract', 'method_protected_abstract', 'method_public_abstract_static',
                    'method_protected_abstract_static', 'method_public', 'method_protected', 'method_private',
                    'method_public_static', 'method_protected_static',
                ]
        ],
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
    ])
    ->setFinder($finder);
