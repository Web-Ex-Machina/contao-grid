<?php

$header = <<<EOF
GRID for Contao Open Source CMS
Copyright (c) 2015-2024 Web ex Machina

@category ContaoBundle
@package  Web-Ex-Machina/contao-grid
@author   Web ex Machina <contact@webexmachina.fr>
@link     https://github.com/Web-Ex-Machina/contao-grid/
EOF;
// To make it work, add "--path-mode": "intersection" in your "php_cs_fixer_additional_args"
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor'])
;

$config = new PhpCsFixer\Config();

return $config->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP71Migration' => true,
        '@PHP71Migration:risky' => true,
        '@PHPUnit60Migration:risky' => true,
        'align_multiline_comment' => true,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'comment_to_phpdoc' => true,
        'compact_nullable_typehint' => true,
        'fully_qualified_strict_types' => true,
        'header_comment' => ['header' => $header, 'comment_type' => 'PHPDoc'],
        'heredoc_to_nowdoc' => true,
        'linebreak_after_opening_tag' => true,
        'list_syntax' => ['syntax' => 'short'],
        'multiline_comment_opening_closing' => true,
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'new_line_for_chained_calls',
        ],
        'native_function_invocation' => [
            'include' => ['@compiler_optimized'],
            'strict' => true,
        ],
        'no_alternative_syntax' => true,
        'no_binary_string' => true,
        'no_null_property_initialization' => true,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'php_unit_method_casing' => true,
        'php_unit_strict' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'phpdoc_var_annotation_correct_order' => true,
        'return_assignment' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'string_line_ending' => true,
        'void_return' => true,
    ])
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder($finder)
;