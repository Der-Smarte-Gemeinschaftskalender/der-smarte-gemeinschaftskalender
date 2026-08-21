import { defineConfig, devices } from '@playwright/test';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// import dotenv from 'dotenv';
// import path from 'path';
// dotenv.config({ path: path.resolve(__dirname, '.env') });

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: './e2e',
  /* Serientermine werden Termin für Termin in Mobilizon angelegt - das dauert länger als eine Minute */
  timeout: 120000,
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Alle Worker teilen sich EIN Backend und dessen Mobilizon-Ratelimit (60 createEvent/min pro IP).
     Mehr Parallelität macht den Lauf nicht schneller, nur roter: 6 Worker = 12/48 rot, 2 Worker = 2/48
     bei praktisch gleicher Laufzeit (4,7 vs. 5,0 min). */
  workers: process.env.CI ? 1 : 2,
  /* Die Standard-5s reichen für diese App nicht: Jeder Schritt lädt einen Route-Chunk und geht
     über das Backend an Mobilizon. Unter Last liefen daran reihenweise Tests auf, die fachlich in
     Ordnung waren (Login-Dashboard, Formular aus Vorlage, Dialoge). */
  expect: { timeout: 15000 },
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: 'html',
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    // baseURL: 'http://localhost:3000',

    /* Ohne diese Limits laufen hängende Klicks und Navigationen in den Test-Timeout und die
       Fehlermeldung nennt nur "Test timeout of ...ms exceeded" statt Locator bzw. URL. */
    actionTimeout: 15000,
    navigationTimeout: 30000,

    /* Force German browser locale so i18n defaults to DE in tests */
    locale: 'de-DE',
    timezoneId: 'Europe/Berlin',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },

    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },

    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },

    /* Test against mobile viewports. */
    // {
    //   name: 'Mobile Chrome',
    //   use: { ...devices['Pixel 5'] },
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: { ...devices['iPhone 12'] },
    // },

    /* Test against branded browsers. */
    // {
    //   name: 'Microsoft Edge',
    //   use: { ...devices['Desktop Edge'], channel: 'msedge' },
    // },
    // {
    //   name: 'Google Chrome',
    //   use: { ...devices['Desktop Chrome'], channel: 'chrome' },
    // },
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'npm run start',
  //   url: 'http://localhost:3000',
  //   reuseExistingServer: !process.env.CI,
  // },
});
