<?php

// For php-cs-fixer 3.95.27
// https://github.com/PHP-CS-Fixer/PHP-CS-Fixer#usage
// https://mlocati.github.io/php-cs-fixer-configurator/#version:3.95
return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS3x0'                               => true,
        'combine_consecutive_unsets'               => true,
        'binary_operator_spaces'                   => [
            'operators' => [
                '='  => 'align_single_space_minimal',
                '=>' => 'align_single_space_minimal_by_scope',
            ],
        ],
        'class_attributes_separation'              => [
            'elements' => [
                'method' => 'one',
            ],
        ],
        'braces_position'                          => [
            'allow_single_line_anonymous_functions'     => true,
            'allow_single_line_empty_anonymous_classes' => true,
        ],
        'no_unused_imports'                        => true,
        'ordered_imports'                          => [
            'imports_order'  => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'whitespace_after_comma_in_array'          => true,
        'no_superfluous_elseif'                    => true,
        'no_useless_else'                          => true,
        'nullable_type_declaration'                => [
            'syntax' => 'union',
        ],
        'ordered_types'                            => [
            'sort_algorithm'  => 'alpha',
            'null_adjustment' => 'always_last',
        ],
        'phpdoc_align'                             => true,
        'phpdoc_indent'                            => true,
        'assign_null_coalescing_to_coalesce_equal' => true,
        'heredoc_indentation'                      => true,
        'no_whitespace_before_comma_in_array'      => [
            'after_heredoc' => true,
        ],
        'octal_notation'                           => true,
        'ternary_to_null_coalescing'               => true,
    ])
    ->setLineEnding("\n")
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([
                __DIR__ . '/app',
                __DIR__ . '/tests',
            ]),
    )
;
