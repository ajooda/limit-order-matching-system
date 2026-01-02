import vue from "eslint-plugin-vue";
import vueParser from "vue-eslint-parser";
import globals from "globals";

export default [
    {
        files: ["resources/**/*.{js,jsx,ts,tsx,vue}"],
        ignores: [
            "vendor/**",
            "storage/**",
            "bootstrap/cache/**",
            "public/**",
            "node_modules/**",
            "dist/**",
            "build/**",
        ],
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                ecmaVersion: "latest",
                sourceType: "module",
            },
            globals: {
                ...globals.browser, 
            },
        },
        plugins: { vue },
        rules: {
            ...vue.configs["flat/recommended"][0].rules,

            "no-console": "off",

            // Keep these
            "no-undef": "error",
            "no-unused-vars": ["warn", { argsIgnorePattern: "^_" }],
        },
    },
];
