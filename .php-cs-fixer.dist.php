<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'docker-data'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setFinder($finder)
    ->setRules([
        'indentation_type' => true,
        'constant_case' => ['case' => 'lower'],
        'elseif' => true,
        'braces_position' => [
            'classes_opening_brace' => 'same_line',
            'functions_opening_brace' => 'same_line',
            'anonymous_classes_opening_brace' => 'same_line',
            'anonymous_functions_opening_brace' => 'same_line',
            'control_structures_opening_brace' => 'same_line',
        ],
        'control_structure_braces' => true,
        'function_declaration' => true,
        'no_spaces_after_function_name' => true,
        'spaces_inside_parentheses' => ['space' => 'none'],
        'single_space_around_construct' => true,
        'method_argument_space' => ['on_multiline' => 'ignore'],
        'array_indentation' => true,
        'trim_array_spaces' => true,
        'binary_operator_spaces' => ['default' => 'single_space'],
        'concat_space' => ['spacing' => 'one'],
        'cast_spaces' => ['space' => 'none'],
        'unary_operator_spaces' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
        'no_multiple_statements_per_line' => true,
        'no_closing_tag' => true,
    ]);
