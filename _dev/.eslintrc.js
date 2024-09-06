module.exports = {
	root: true,
	env: {
		browser: true,
		node: true,
		es6: true,
	},

	parserOptions: {
		parser: '@babel/eslint-parser',
		requireConfigFile: false,
		ecmaVersion: 10,
		sourceType: "module",
	},

	globals: {
		google: true,
		document: true,
		navigator: false,
		window: true,
	},
	plugins: ['import'],
	rules: {
		'class-methods-use-this': 0,
		'func-names': 0,
		'import/no-extraneous-dependencies': [
			'error',
			{
				devDependencies: ['tests/**/*.js', '.webpack/**/*.js'],
			},
		],
		'max-len': ['error', {code: 150}],
		'no-alert': 0,
		'no-bitwise': 0,
		'no-new': 0,
		'max-classes-per-file': 0,
		'no-param-reassign': ['error', {props: false}],
		'no-restricted-globals': [
			'error',
			{
				name: 'global',
				message: 'Use window variable instead.',
			},
		],
		'prefer-destructuring': ['error', {object: true, array: false}],
	},
	settings: {
		'import/resolver': 'webpack',
	},
};
