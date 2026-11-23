import prettier from 'eslint-config-prettier';
import vue from 'eslint-plugin-vue';

import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';

export default defineConfigWithVueTs(
    vue.configs['flat/essential'],
    vueTsConfigs.recommended,
    {
        // Generated code is not linted: assets/js/design-system/ui/* comes from the shadcn-vue CLI.
        ignores: ['vendor', 'node_modules', 'build', 'temp', 'tmp', 'logs', 'upload', 'assets/js/design-system/ui/*'],
    },
    {
        rules: {
            'vue/multi-word-component-names': 'off',
        },
    },
    {
        // Application code uses the design system through its public entry point (`@/design-system`) only.
        files: ['assets/js/**/*.{ts,vue}'],
        ignores: ['assets/js/design-system/**'],
        rules: {
            'no-restricted-imports': [
                'error',
                {
                    patterns: [
                        {
                            group: ['@/design-system/ui', '@/design-system/ui/**'],
                            message: 'Private design-system primitives are not application APIs. Import from "@/design-system" instead.',
                        },
                    ],
                },
            ],
        },
    },
    {
        // The design system is domain-independent: no pages, application components, layouts or Inertia.
        files: ['assets/js/design-system/**/*.{ts,vue}'],
        rules: {
            'no-restricted-imports': [
                'error',
                {
                    patterns: [
                        {
                            group: ['@inertiajs/*', '@/pages/*', '@/components/*', '@/layouts/*'],
                            message: 'Keep application composition outside the design system and pass data or content through props and slots instead.',
                        },
                    ],
                },
            ],
        },
    },
    prettier,
);
