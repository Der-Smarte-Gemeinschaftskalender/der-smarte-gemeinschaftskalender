import { test, expect } from "@playwright/test";
import {
  loadEnv,
  login,
  navigateToApp,
  createSingleEvent,
  fillEventForm,
  submitSingleEvent,
  viewEventFromList,
  verifyEventDetails,
  generateRandomTestName,
  editEventFromList,
  editEventForm,
  saveEventChanges,
  copyEventFromList,
  expectErrorMessage,
  getFutureDate,
} from "./helpers/testHelpers";

test("single termin with location", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName("E2E Test Termin with location");

  if (!config.testAddress || !config.confirmationTestAddress) {
    throw new Error("TEST_ADDRESS or CONFIRMATION_TEST_ADDRESS is not defined");
  }

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    location: {
      address: config.testAddress,
      confirmationAddress: config.confirmationTestAddress,
    },
  };

  await fillEventForm(page, eventData);
  await submitSingleEvent(page);
  await viewEventFromList(page, eventName);
  await verifyEventDetails(page, eventData);
});

test("single termin with category and tags", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName(
    "E2E test termin with category and tags"
  );

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    category: "Natur & Abenteuer",
    tags: ["Tag1", "Tag2"],
  };

  await fillEventForm(page, eventData);
  await submitSingleEvent(page);
  await viewEventFromList(page, eventName);
  await verifyEventDetails(page, eventData);
});

test("single termin with custom date", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName("E2E test termin with custom date");

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    date: {
      day: getFutureDate(4),
      startHour: "12",
      startMinute: "15",
      durationHours: "2",
      durationMinutes: "45",
    },
  };

  await fillEventForm(page, eventData);
  await submitSingleEvent(page);
  await viewEventFromList(page, eventName);
  await verifyEventDetails(page, eventData);
});

test("single termin with editing", async ({ page }) => {
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
  await editEventFromList(page, eventName);

  const newName = `${eventName} - bearbeitet`;
  const newDescription = `Das ist eine bearbeitete Beschreibung${eventName}`;

  const editData = {
    name: newName,
    description: newDescription,
  };

  await editEventForm(page, editData);
  await saveEventChanges(page);
  await verifyEventDetails(page, editData);

  await page.locator(".kern-icon--edit").first().click();
  await expect(page).toHaveURL(/.*\/app\/created-events\/.*\/edit/);

  const secondEditName = `${eventName} - zweite Bearbeitung`;
  const secondEditDescription = `Das ist eine zweite bearbeitete Beschreibung${eventName}`;

  const secondEditData = {
    name: secondEditName,
    description: secondEditDescription,
  };

  await editEventForm(page, secondEditData);
  await saveEventChanges(page);
  await page.reload();
  await verifyEventDetails(page, secondEditData);
});

test("single termin with image", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName("E2E test termin with image");

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    imagePath: "./e2e/testFiles/test-image.png",
  };

  await fillEventForm(page, eventData);
  await submitSingleEvent(page);
  await viewEventFromList(page, eventName);
  await verifyEventDetails(page, eventData);
});

test("single termin with url", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName("E2E Test Termin with URL");

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  const eventDataWithoutUrl = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    externalUrl: "keine-gültige-url",
  };

  await fillEventForm(page, eventDataWithoutUrl);

  await page.locator("input[type='radio'][value='EXTERNAL']").check();
  await page.getByRole("button").getByText("Einzeltermin anlegen").click();

  await expectErrorMessage(
    page,
    "Bei Angabe einer externen Teilnahme-URL bitte eine gültige URL eingeben."
  );

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    externalUrl: "https://54gradsoftware.de",
  };

  await page.locator("#externalParticipationUrl").fill(eventData.externalUrl);
  await submitSingleEvent(page);
  await viewEventFromList(page, eventName);
  await verifyEventDetails(page, eventData);
});

test("single termin use as template", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName("E2E test termin use as template");

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
  };

  await fillEventForm(page, eventData);
  await submitSingleEvent(page);
  await copyEventFromList(page, eventName);

  await expect(page.locator("#name")).toHaveValue(eventName);
  await page.locator("#name").fill(`${eventName} - kopie`);

  await expect(page.locator("#description")).toHaveValue(eventData.description);
  await page.locator("#description").fill(`${eventData.description} - kopie`);

  await submitSingleEvent(page);

  await viewEventFromList(page, `${eventName} - kopie`);

  const copiedEventData = {
    name: `${eventName} - kopie`,
    description: `${eventData.description} - kopie`,
  };

  await verifyEventDetails(page, copiedEventData);
});

test("single termin with location and custom date", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName(
    "E2E test termin with location and custom date"
  );

  if (!config.testAddress || !config.confirmationTestAddress) {
    throw new Error("TEST_ADDRESS or CONFIRMATION_TEST_ADDRESS is not defined");
  }

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  const eventData = {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    location: {
      address: config.testAddress,
      confirmationAddress: config.confirmationTestAddress,
    },
    date: {
      day: getFutureDate(4),
      startHour: "14",
      startMinute: "30",
      durationHours: "3",
      durationMinutes: "00",
    },
  };

  await fillEventForm(page, eventData);
  await submitSingleEvent(page);
  await page.waitForLoadState("networkidle");

  await page.getByText("Veranstaltungen").first().click();
  await page.waitForLoadState("networkidle");
  await expect(page).toHaveURL(/.*\/search/);
  await expect(page.getByText(eventName).first()).toBeVisible();
});
