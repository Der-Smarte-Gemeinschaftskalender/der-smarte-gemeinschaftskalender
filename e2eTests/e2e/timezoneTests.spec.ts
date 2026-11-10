import { test, expect } from "@playwright/test";
import {
  loadEnv,
  login,
  navigateToApp,
  createSingleEvent,
  fillEventForm,
  submitSingleEvent,
  generateRandomTestName,
  editEventFromList,
  getFutureDate,
} from "./helpers/testHelpers";

test("single termin defaults to instance timezone", async ({ page }) => {
  const config = loadEnv();

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  // VITE_APP_TIMEZONE ist in der Testumgebung nicht gesetzt bzw. Europe/Berlin
  await expect(page.locator("select[name='timezone']")).toHaveValue("Europe/Berlin");
});

test("single termin keeps selected timezone", async ({ page }) => {
  const config = loadEnv();
  const eventName = generateRandomTestName("E2E Test Termin Zeitzone");

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  await fillEventForm(page, {
    name: eventName,
    description: `Das ist eine Beschreibung${eventName}`,
    date: {
      day: getFutureDate(),
      startHour: "18",
      startMinute: "00",
    },
  });
  await page.locator("select[name='timezone']").selectOption("America/New_York");
  await submitSingleEvent(page);

  // Die Zeitzone liegt in Mobilizon (options.timezone) und wird beim Bearbeiten wieder geladen,
  // die Uhrzeit bleibt die Ortszeit der gewählten Zeitzone.
  await editEventFromList(page, eventName);
  await expect(page.locator("select[name='timezone']")).toHaveValue("America/New_York");
  await expect(page.locator("input[placeholder='hh']").first()).toHaveValue("18");
  await expect(page.locator("input[placeholder='mm']").first()).toHaveValue("00");
});
