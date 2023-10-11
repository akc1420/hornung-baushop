const { defineConfig } = require("cypress");

module.exports = defineConfig({
  viewportHeight: 1080,
  viewportWidth: 1920,
  watchForFileChanges: false,
  requestTimeout: 30000,
  responseTimeout: 60000,
  defaultCommandTimeout: 30000,
  salesChannelName: "Storefront",
  useDarkTheme: false,
  video: false,
  useShopwareTheme: true,
  theme: "dark",
  screenshotsFolder: "./../app/build/artifacts/e2e/screenshots",
  reporter: "cypress-multi-reporters",

  reporterOptions: {
    configFile: "reporter-config.json",
  },

  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
});
