<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/packages/core/src',
        __DIR__ . '/packages/core/tests',
        __DIR__ . '/packages/http/src',
        __DIR__ . '/packages/http/tests',
        __DIR__ . '/packages/routing/src',
        __DIR__ . '/packages/routing/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12'                            => true,
        '@PHP82Migration'                   => true,
        '@PHP80Migration:risky'             => true,
        'strict_param'                      => true,
        'declare_strict_types'              => true,
        'array_syntax'                      => ['syntax' => 'short'],
        'ordered_imports'                   => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'                 => true,
        'trailing_comma_in_multiline'       => true,
        'phpdoc_scalar'                     => true,
        'unary_operator_spaces'             => true,
        'binary_operator_spaces'            => true,
        'blank_line_before_statement'       => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing'    => true,
        'phpdoc_var_without_name'           => true,
        'class_attributes_separation'       => [
            'elements' => ['method' => 'one', 'property' => 'one'],
        ],
        'method_argument_space'             => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'single_trait_insert_per_statement' => true,
        'no_extra_blank_lines'              => [
            'tokens' => ['extra', 'throw', 'use'],
        ],
        'fully_qualified_strict_types'      => true,
        'global_namespace_import'           => [
            'import_classes'    => true,
            'import_constants'  => false,
            'import_functions'  => false,
        ],
    ])
    ->setFinder($finder);
