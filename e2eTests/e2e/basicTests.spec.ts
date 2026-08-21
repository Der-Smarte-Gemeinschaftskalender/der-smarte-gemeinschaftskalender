import { test, expect } from "@playwright/test";
import {
  loadEnv,
  login,
  navigateToApp,
  createSingleEvent,
  createSerialEvent,
  fillEventForm,
  submitSingleEvent,
  submitSerialEvent,
  viewEventFromList,
  verifyEventDetails,
  register,
  generateRandomTestName,
  viewSerialEventFromList,
} from "./helpers/testHelpers";

test("availability", async ({ page }) => {
  const config = loadEnv();
  await page.goto(config.siteUrl);
  // Der Seitentitel wird pro Instanz gebrandet (demo-dev2 z.B. "Veranstaltungen im Amt Süderbrarup").
  // Nur wenn SITE_TITLE gesetzt ist, wird exakt geprüft - sonst reicht als Smoke-Test, dass die
  // App überhaupt ausgeliefert und gerendert wird.
  await expect(page).toHaveTitle(config.siteTitle ?? /.+/);
  await expect(page.getByRole("link").getByText("Zum internen Bereich")).toBeVisible();
});

test("signup", async ({ page }) => {
  const config = loadEnv();
  await register(page, config);
});

test("basic single termin", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName("E2E Basic Test Termin");

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
  };

  await fillEventForm(page, eventData);
  await submitSingleEvent(page);
  await viewEventFromList(page, eventName);
  await verifyEventDetails(page, eventData);
});

test("basic serial termin", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName("E2E Basic Serial Test Termin");

  await login(page, config);
  await navigateToApp(page, config);
  await createSerialEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
  };

  await fillEventForm(page, eventData);
  await submitSerialEvent(page);
  await viewSerialEventFromList(page, eventName);
  await page.getByTitle('Ansehen').click();
  await verifyEventDetails(page, eventData);
});

test("basic brandkit", async ({ page }) => {
  const config = loadEnv();

  await login(page, config);
  await navigateToApp(page, config);

  await page.getByText("Markenkit").first().click();

  await expect(page).toHaveURL(/.*\/material-generator\/markenkit/);

  await page.waitForLoadState("networkidle");

  const fontDropdown = page.locator('[name="selectedFontHeadline"]');
  await expect(fontDropdown).toBeVisible();

  const currentValue = await fontDropdown.inputValue();

  if (currentValue === "Arial" || currentValue === "arial") {
    await fontDropdown.selectOption("Times New Roman");
  } else {
    await fontDropdown.selectOption("Arial");
  }

  await page.getByRole("button", { name: "Speichern" }).click();

  await expect(page.getByText("Gespeichert").first()).toBeVisible();
});