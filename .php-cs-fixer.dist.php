<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->notPath('DependencyInjection/Configuration.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => false,
        'native_function_invocation' => false,
    ])

    ->setFinder($finder);
