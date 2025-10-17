import eslint from "@eslint/js";
import eslintConfigPrettier from "eslint-config-prettier";
import eslintPluginVue from "eslint-plugin-vue";
import globals from "globals";
import typescriptEslint from "typescript-eslint";

export default typescriptEslint.config(
  { ignores: ["*.d.ts", "**/coverage", "**/dist"] },
  {
    extends: [
      eslint.configs.recommended,
      ...typescriptEslint.configs.recommended,
      ...eslintPluginVue.configs["flat/recommended"],
    ],
    files: ["**/*.{ts,vue}"],
    rules: {
      // override/add rules settings here, such as:
      // 'vue/no-unused-vars': 'error'
      "vue/multi-word-component-names": "off",
      "@typescript-eslint/no-empty-object-type": "off",
    },
    languageOptions: {
      sourceType: "module",
      globals: {
        ...globals.browser,
      },
      ecmaVersion: "latest",
      parserOptions: {
        parser: typescriptEslint.parser,
      },
    },
  },
  eslintConfigPrettier
);
