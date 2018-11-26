<?php declare(strict_types=1);

return [
    'target_php_version' => '7.0',
    'directory_list' => [
        'src',
        'vendor/composer',
        'vendor/mikey179',
        'vendor/symfony',
    ],
    'exclude_analysis_directory_list' => [
        'vendor/',
    ],
    'warn_about_redundant_use_namespaced_class' => true,
    'color_issue_messages' => true,
    'check_docblock_signature_return_type_match' => true,
    'prefer_narrowed_phpdoc_param_type' => true,
    'prefer_narrowed_phpdoc_return_type' => true,
    'strict_method_checking' => true,
    'strict_return_checking' => true,
    'generic_types_enabled' => false,
    'simplify_ast' => false,
    'dead_code_detection' => true,
    'suppress_issue_types' => [
        'PhanUnreferencedPublicMethod',
        'PhanUnreferencedPublicClassConstant',
        'PhanUnusedPublicFinalMethodParameter',
        'PhanUnusedPublicMethodParameter',
        'PhanUnusedVariableCaughtException',
        'PhanUnusedVariableValueOfForeachWithKey',
    ],
    'plugins' => [
        'UnreachableCodePlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
        'InvalidVariableIssetPlugin',
        'NoAssertPlugin',
        'SleepCheckerPlugin',
        'DuplicateExpressionPlugin',
    ],
];