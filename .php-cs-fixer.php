<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/app')
    ->in(__DIR__.'/tests')
    ->exclude('vendor')
    ->exclude('storage');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'ordered_imports' => true,
    ])
    ->setFinder($finder);
