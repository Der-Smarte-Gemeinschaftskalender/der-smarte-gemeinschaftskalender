import { test, expect } from "@playwright/test";
import {
  loadEnv,
  login,
  navigateToApp,
  createSerialEvent,
  fillEventForm,
  fillSerialEventFormWithCustomDate,
  submitSerialEvent,
  viewSerialEventFromList,
  verifyEventDetails,
  verifySerialEventDates,
  copySerialEventFromList,
  generateRandomTestName,
  getFutureDate,
} from "./helpers/testHelpers";

test("serial termin with location", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName(
    "E2E Serial Test Termin with location"
  );

  if (!config.testAddress || !config.confirmationTestAddress) {
    throw new Error("TEST_ADDRESS or CONFIRMATION_TEST_ADDRESS is not defined");
  }

  await login(page, config);
  await navigateToApp(page, config);
  await createSerialEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    location: {
      address: config.testAddress,
      confirmationAddress: config.confirmationTestAddress,
    },
  };

  await fillEventForm(page, eventData);
  await submitSerialEvent(page);
  await viewSerialEventFromList(page, eventName);
  await page.getByText("Ansehen").click();
  await verifyEventDetails(page, eventData);
});

test("serial termin with category and tags", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName(
    "E2E serial test termin with category and tags"
  );

  await login(page, config);
  await navigateToApp(page, config);
  await createSerialEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    category: "Natur & Abenteuer",
    tags: ["Tag1", "Tag2"],
  };

  await fillEventForm(page, eventData);
  await submitSerialEvent(page);
  await viewSerialEventFromList(page, eventName);
  await page.getByText("Ansehen").click();
  await verifyEventDetails(page, eventData);
});

test("serial termin with custom date", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName(
    "E2E serial test termin with custom date"
  );

  await login(page, config);
  await navigateToApp(page, config);
  await createSerialEvent(page);

  const futureDate = getFutureDate(4);
  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    endDate: futureDate,
    interval: "Monatlich",
    date: {
      day: "",
      startHour: "12",
      startMinute: "15",
      durationHours: "2",
      durationMinutes: "45",
    },
  };

  await fillSerialEventFormWithCustomDate(page, eventData);
  await submitSerialEvent(page);
  await viewSerialEventFromList(page, eventName);

  await expect(page.getByText(futureDate)).toBeVisible();
  await expect(page.getByText("Monatlich")).toBeVisible();

  const dateRows = await page
    .locator("tbody.kern-table__body tr.kern-table__row")
    .count();
  if (dateRows < 3) {
    throw new Error(
      `Expected at least 3 date rows for a 4-month span, but found ${dateRows} date rows`
    );
  }

  await page.getByText("Ansehen").first().click();
  await expect(
    page.getByRole("heading", { name: eventName }).first()
  ).toBeVisible();
});

test("serial termin use as template", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName(
    "E2E serial test termin use as template"
  );

  await login(page, config);
  await navigateToApp(page, config);
  await createSerialEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
  };

  await fillEventForm(page, eventData);
  await submitSerialEvent(page);

  await page.waitForLoadState("networkidle");

  await page.getByText("Serientermine").first().click();

  await expect(page).toHaveURL(/.*\/app\/series-events$/);

  await expect(page.getByText(eventName).first()).toBeVisible();
  await page.waitForTimeout(5000);

  await copySerialEventFromList(page, eventName);

  await expect(page.locator("#name")).toHaveValue(eventName);
  await page.locator("#name").fill(`${eventName} - kopie`);

  await expect(page.locator("#description")).toHaveValue(eventData.description);
  await page.locator("#description").fill(`${eventData.description} - kopie`);

  await submitSerialEvent(page);

  await viewSerialEventFromList(page, `${eventName} - kopie`);

  await page.getByText("Ansehen").first().click();

  const copiedEventData = {
    name: `${eventName} - kopie`,
    description: `${eventData.description} - kopie`,
  };

  await verifyEventDetails(page, copiedEventData);
});