// prettier.config.js, .prettierrc.js, prettier.config.mjs, or .prettierrc.mjs

/**
 * @see https://prettier.io/docs/configuration
 * @type {import("prettier").Config}
 */
const config = {
  plugins: [
    require.resolve("@zackad/prettier-plugin-twig"),
    "prettier-plugin-tailwindcss",
  ],
  printWidth: 150,
  tailwindStylesheet: "./src/main.css",
  tailwindFunctions: ["html_cva"],
};

export default config;
