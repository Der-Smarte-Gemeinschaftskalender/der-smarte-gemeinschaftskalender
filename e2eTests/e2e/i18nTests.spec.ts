import { test, expect } from "@playwright/test";
import { loadEnv, login, navigateToApp, createSingleEvent } from "./helpers/testHelpers";

test("internal area can be switched to english", async ({ page }) => {
  const config = loadEnv();

  await login(page, config);
  await navigateToApp(page, config);
  await createSingleEvent(page);

  await page.getByRole("button", { name: /Sprache wechseln/ }).first().click();
  await page.getByText("English", { exact: true }).first().click();

  await expect(page.getByRole("heading", { name: "Create single event" })).toBeVisible();
  await expect(page.locator("html")).toHaveAttribute("lang", "en");

  // Validierungsmeldungen (zod) folgen der gewählten Sprache
  await page.locator("#name").fill("ab");
  await page.getByRole("button").getByText("Create single event").click();
  await expect(page.getByText("The name must be at least 3 characters long.")).toBeVisible();

  // Sprache bleibt beim Wechsel in den öffentlichen Bereich und zurück erhalten
  await page.goto(config.siteUrl);
  await navigateToApp(page, config);
  await expect(page.getByRole("heading", { name: "Dashboard" })).toBeVisible();
  await expect(page.getByRole("link", { name: "Single events" }).or(page.getByText("Single events")).first()).toBeVisible();

  await page.getByRole("button", { name: /Change language/ }).first().click();
  await page.getByText("Deutsch", { exact: true }).first().click();
  await expect(page.getByText("Einzeltermine").first()).toBeVisible();
});

test.describe("browser language detection", () => {
  test.use({ locale: "en-US" });

  test("follows the browser until the user picks a language", async ({ page }) => {
    const config = loadEnv();

    await page.goto(config.siteUrl);
    await expect(page.locator("html")).toHaveAttribute("lang", "en");

    // Only an explicit choice in the language switch is remembered, otherwise a later change of
    // the browser language would be ignored.
    expect(await page.evaluate(() => window.localStorage.getItem("locale"))).toBeNull();

    await page.getByRole("button", { name: /Change language/ }).first().click();
    await page.getByText("Deutsch", { exact: true }).first().click();
    await expect(page.locator("html")).toHaveAttribute("lang", "de");
    expect(await page.evaluate(() => window.localStorage.getItem("locale"))).toBe("de");

    await page.reload();
    await expect(page.locator("html")).toHaveAttribute("lang", "de");
  });
});

test("email language can be changed in the profile without changing the UI language", async ({ page }) => {
  const config = loadEnv();

  await login(page, config);
  await page.goto(`${config.siteUrl}/app/profile`);

  const profileForm = page.locator("form", { has: page.locator('select[name="locale"]') });
  const emailLanguage = profileForm.locator('select[name="locale"]');

  await emailLanguage.selectOption("en");
  await profileForm.getByRole("button", { name: "Änderungen speichern" }).click();
  await expect(page.getByText("Profil erfolgreich aktualisiert")).toBeVisible();

  // Die E-Mail-Sprache ändert nicht die Sprache der Oberfläche
  await expect(page.locator("html")).toHaveAttribute("lang", "de");

  await page.reload();
  await expect(emailLanguage).toHaveValue("en");
  await expect(profileForm.getByText("E-Mail-Sprache:")).toBeVisible();

  // Zurücksetzen, damit andere Tests weiterhin deutsche E-Mails erhalten
  await emailLanguage.selectOption("de");
  await profileForm.getByRole("button", { name: "Änderungen speichern" }).click();
  await expect(page.getByText("Profil erfolgreich aktualisiert")).toBeVisible();
});
