<?php

if (!file_exists(__DIR__.'/src')) {
    exit(0);
}

$finder = (new \PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
;

return (new \PhpCsFixer\Config())
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'header_comment' => [
            'header' => <<<EOF
Spiral DataGrid Bundle
Copyright (c) ThePHPGuys <https://thephpguys.com/>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF
        ]
    ))
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'protected_to_private' => false,
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced', 'strict' => true],
        'single_line_throw' => false,
        'non_printable_character' => false,
        'blank_line_between_import_groups' => false,
        'no_trailing_comma_in_singleline' => false,
        'nullable_type_declaration_for_default_null_value' => true,
    ))
    ;
