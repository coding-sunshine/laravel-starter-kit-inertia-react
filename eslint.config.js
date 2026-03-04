import js from '@eslint/js';
import eslintReact from '@eslint-react/eslint-plugin';
import prettier from 'eslint-config-prettier/flat';
import globals from 'globals';
import typescript from 'typescript-eslint';

/** @type {import('eslint').Linter.Config[]} */
export default [
    {
        ignores: ['demo/**', 'resources/js/actions/**', 'vendor/**', 'node_modules/**', 'public/**', 'bootstrap/ssr/**', 'tailwind.config.js'],
    },
    js.configs.recommended,
    ...typescript.configs.recommended,
    {
        ...eslintReact.configs['recommended-typescript'],
        languageOptions: {
            globals: {
                ...globals.browser,
            },
        },
    },
    prettier, // Turn off all rules that might conflict with Prettier
    {
        rules: {
            '@typescript-eslint/no-unused-vars': [
                'error',
                {
                    argsIgnorePattern: '^_',
                    varsIgnorePattern: '^_',
                    caughtErrorsIgnorePattern: '^_',
                },
            ],
        },
    },
];
