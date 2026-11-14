<?php

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__ . '/src')
	->name('*.php')
	->ignoreDotFiles(true)
	->ignoreVCS(true);

return (new PhpCsFixer\Config())
	->setRiskyAllowed(true)
	->setRules([
		'align_multiline_comment' => [
			'comment_type' => 'phpdocs_only'
		],
		'array_indentation' => true,
		'array_syntax' => [
			'syntax' => 'short'
		],
		'binary_operator_spaces' => [
			'default' => 'single_space'
		],
		'blank_line_after_namespace' => true,
		'blank_line_after_opening_tag' => true,
		'blank_line_before_statement' => [
			'statements' => [
				'declare'
			]
		],
		'cast_spaces' => [
			'space' => 'single'
		],
		'concat_space' => [
			'spacing' => 'one'
		],
		'declare_strict_types' => true,
		'elseif' => true,
		'fully_qualified_strict_types' => true,
		'global_namespace_import' => [
			'import_constants' => true,
			'import_functions' => true,
			'import_classes' => null,
		],
		'indentation_type' => true,
		'native_constant_invocation' => [
			'scope' => 'namespaced'
		],
		'native_function_invocation' => [
			'scope' => 'namespaced',
			'include' => ['@all'],
		],
		'new_with_braces' => [
			'named_class' => true,
			'anonymous_class' => false,
		],
		'no_closing_tag' => true,
		'no_empty_phpdoc' => true,
		'no_extra_blank_lines' => true,
		'no_leading_import_slash' => true,
		'no_superfluous_phpdoc_tags' => [
			'allow_mixed' => true,
		],
		'no_trailing_whitespace' => true,
		'no_trailing_whitespace_in_comment' => true,
		'no_unused_imports' => true,
		'no_whitespace_in_blank_line' => true,
		'nullable_type_declaration_for_default_null_value' => true,
		'ordered_imports' => [
			'imports_order' => [
				'class',
				'function',
				'const',
			],
			'sort_algorithm' => 'alpha'
		],
		'phpdoc_align' => [
			'align' => 'vertical',
			'tags' => [
				'param',
			]
		],
		'phpdoc_line_span' => [
			'property' => 'single',
			'method' => null,
			'const' => null
		],
		'phpdoc_trim' => true,
		'phpdoc_trim_consecutive_blank_line_separation' => true,
		'return_type_declaration' => [
			'space_before' => 'none'
		],
		'single_blank_line_at_eof' => true,
		'single_import_per_statement' => true,
		'single_quote' => true,
		'strict_param' => true,
		'trailing_comma_in_multiline' => [
			'elements' => ['arrays', 'arguments', 'parameters'],
		],
		'unary_operator_spaces' => true,
		'visibility_required' => [
			'elements' => ['property', 'method', 'const'],
		],
		'void_return' => true,
	])
	->setFinder($finder)
	->setIndent("\t")
	->setLineEnding("\n")
	->setUsingCache(true)
	->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');