import { test, expect } from "@playwright/test";
import {
  loadEnv,
  login,
  navigateToApp,
  createSingleEvent,
  fillEventForm,
  submitSingleEvent,
  generateRandomTestName,
} from "./helpers/testHelpers";
import {
  generateTestICalFile,
  getTestICalFilePath,
  uploadICalFile,
} from "./helpers/icalHelper";

test("werbemittel generator test", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName("E2E Werbemittel Generator Test");

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
  };

  await fillEventForm(page, eventData);
  await submitSingleEvent(page);

  const eventRow = page
    .getByRole("row", { name: new RegExp(eventName) })
    .first();
  await eventRow.getByRole("button", { name: "Werbemittel" }).first().click();

  await expect(page).toHaveURL(/.*\/material-generator\/events\/.*/);
  await page.waitForLoadState("networkidle");

  const downloadPromise = page.waitForEvent("download");
  await page.getByText("Design herunterladen").click();
  const download = await downloadPromise;

  if (!download) {
    throw new Error("Download event not triggered");
  }

  const downloadPath = await download.path();
  if (!downloadPath) {
    throw new Error("Download failed - no file created");
  }
});

test("upload ical file", async ({ page }) => {
  const config = loadEnv();
  const testName = generateRandomTestName("E2E ICal Upload Test");

  const { eventName: importedEventName, eventStart } =
    generateTestICalFile(testName);

  await login(page, config);
  await navigateToApp(page, config);

  const filePath = getTestICalFilePath();
  await page.waitForLoadState("networkidle");

  await page.getByRole("button", { name: "Kalenderdatei " }).click();
  await expect(page.getByText("Kalenderdatei hochladen")).toBeVisible();
  await uploadICalFile(page, filePath);

  await page.waitForLoadState("networkidle");
  await expect(page.getByText("No file chosen")).toBeHidden();

  await page.getByRole("button", { name: "Veranstaltungen anzeigen" }).click();

  await page.waitForLoadState("networkidle");

  await expect(page.getByText(importedEventName)).toBeVisible();

  // dd.mm.yyyy
  const formattedDate = eventStart
    .toLocaleDateString("de-DE", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    })
    .replace(/\./g, ".");
  await expect(page.getByText(formattedDate).first()).toBeVisible();

  await page.getByText("Vorschautermine anlegen").click();

  await expect(page).toHaveURL(/.*\/uploaded-events/);
});

test("navigate to events without login and verify Kunst category URL", async ({
  page,
}) => {
  const config = loadEnv();

  await page.goto(config.siteUrl);

  await page.getByRole("link", { name: "Veranstaltungen" }).first().click();
  await page.waitForLoadState("networkidle");

  await page.getByRole("button", { name: "Ansehen" }).first().click();
  await page.waitForLoadState("networkidle");

  await page.getByText("Kunst").first().click();
  await page.waitForLoadState("networkidle");

  await expect(page).toHaveURL(/.*\/search/);

  // expect the checkbox for "ARTS" to be checked
  await expect(
    page.locator('input[type="checkbox"][value="ARTS"]')
  ).toBeChecked();
});