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

  const downloadPromise = page.waitForEvent('download', { timeout: 10000 });

  await page.waitForTimeout(3000);
  await page.getByRole('button', { name: 'Herunterladen' }).first().click();
  await page.waitForTimeout(1000);
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

test("navigate to events without login and verify category filter", async ({
  page,
}) => {
    const config = loadEnv();

    await page.goto(config.siteUrl);

    await page.getByRole('link', { name: 'Veranstaltungen' }).first().click();
    await page.waitForLoadState('networkidle');

    // click the first card title link (element is hidden due to CSS, use JS click)
    await page.waitForTimeout(2000);
    await page
        .locator('a.kern-card__title')
        .first()
        .evaluate((el) => (el as HTMLElement).click());
    //await page.getByRole("button", { name: "Ansehen" }).first().click();

    await page.waitForTimeout(1000);

    await page.waitForLoadState('networkidle');

    // Welche Kategorie der erste Treffer hat, hängt von den Daten der Instanz ab - deshalb den
    // Kategorie-Chip des Termins auslesen statt eine feste Kategorie zu erwarten.
    const categorySection = page
        .locator('div', { has: page.getByRole('heading', { name: 'Kategorie', exact: true }) })
        .last();
    const categoryButton = categorySection.getByRole('button').first();
    const categoryLabel = ((await categoryButton.textContent()) ?? '').trim();
    expect(categoryLabel, 'Termin ohne Kategorie-Chip').toBeTruthy();

    await categoryButton.click();
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/.*\/search/);
    await page.waitForLoadState('networkidle');

    // Der Chip muss den passenden Filter in der Suche setzen (Label der Checkbox = Kategoriename).
    // Die Filterliste steckt in einem zugeklappten Accordion, ist also nicht im A11y-Baum -
    // deshalb über das Label statt über getByRole.
    const categoryFilter = page.locator(
        `.kern-form-check:has(label:text-is("${categoryLabel}")) input[type="checkbox"]`
    );
    await expect(categoryFilter).toBeChecked();
});