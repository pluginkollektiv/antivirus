import wordpress from '@wordpress/eslint-plugin';

export default [
	...wordpress.configs.recommended,
	{
		languageOptions: {
			globals: {
				ajaxurl: 'readonly',
				av_settings: 'readonly',
			},
		},
		rules: {
			camelcase: 'off',
		},
	},
];
