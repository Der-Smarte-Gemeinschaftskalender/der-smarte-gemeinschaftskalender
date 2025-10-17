import { Page, expect } from "@playwright/test";
import dotenv from "dotenv";

export interface TestConfig {
  siteUrl: string;
  adminEmail?: string;
  adminPassword?: string;
  userEmail?: string;
  userPassword?: string;
  testAddress?: string;
  confirmationTestAddress?: string;
}

export function loadEnv(): TestConfig {
  try {
    dotenv.config();
    const siteUrl = process.env.SITE_URL;
    const adminEmail = process.env.ADMIN_EMAIL;
    const adminPassword = process.env.ADMIN_PASSWORD;
    const userEmail = process.env.USER_EMAIL;
    const userPassword = process.env.USER_PASSWORD;
    const testAddress = process.env.TEST_ADDRESS;
    const confirmationTestAddress = process.env.CONFIRMATION_TEST_ADDRESS;
    
    if (!siteUrl) {
      throw new Error("SITE_URL is not defined");
    }
    
    return {
      siteUrl,
      adminEmail,
      adminPassword,
      userEmail,
      userPassword,
      testAddress,
      confirmationTestAddress,
    };
  } catch (error) {
    throw new Error("Could not load .env file");
  }
}

export function createUUID(): string {
  return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function (c) {
    const r = (Math.random() * 16) | 0;
    const v = c === "x" ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

export function generateRandomTestName(prefix: string): string {
  return `${prefix} ${Math.floor(Math.random() * 100000)}`;
}

export async function login(page: Page, config: TestConfig, userType: 'admin' | 'user' = 'admin'): Promise<void> {
  const email = userType === 'admin' ? config.adminEmail : config.userEmail;
  const password = userType === 'admin' ? config.adminPassword : config.userPassword;
  
  if (!email || !password) {
    throw new Error(`${userType.toUpperCase()}_EMAIL or ${userType.toUpperCase()}_PASSWORD is not defined`);
  }

  await page.goto(config.siteUrl);
  await page.getByRole("button").getByText("Intern").click();
  await expect(page).toHaveURL(/.*\/login/);
  
  await page.locator("#email").fill(email);
  await page.locator("#password").fill(password);
  await page.getByRole("button", { name: "Einloggen" }).click();
  
  await expect(page.getByRole("heading", { name: "Dashboard" })).toBeVisible();
}

export async function navigateToApp(page: Page, config: TestConfig): Promise<void> {
  await page.goto(`${config.siteUrl}/app`);
}

export async function createSingleEvent(page: Page): Promise<void> {
  await page.getByRole("button").getByText("Einzeltermin").click();
  await page.waitForLoadState("networkidle");
  await expect(page.getByRole("heading", { name: "Einzeltermin erstellen" })).toBeVisible();
  await expect(page).toHaveURL(/.*\/app\/single-events\/create/);
}

export async function createSerialEvent(page: Page): Promise<void> {
  await page.getByRole("button").getByText("Serientermin").click();
  await page.waitForLoadState("networkidle");
  await expect(page.getByRole("heading", { name: "Serientermin erstellen" })).toBeVisible();
  await expect(page).toHaveURL(/.*\/app\/series-events\/create/);
}

export interface EventFormData {
  name: string;
  description: string;
  category?: string;
  tags?: string[];
  imagePath?: string;
  externalUrl?: string;
  date?: {
    day: string;
    startHour: string;
    startMinute: string;
    durationHours?: string;
    durationMinutes?: string;
  };
  location?: {
    address: string;
    confirmationAddress: string;
  };
}

export async function fillEventForm(page: Page, data: EventFormData): Promise<void> {
  await page.locator("#name").fill(data.name);
  await page.locator("#description").fill(data.description);

  if (data.category) {
    await page.locator("select[name='category']").selectOption(data.category);
  }

  if (data.tags && data.tags.length > 0) {
    for (let i = 0; i < data.tags.length; i++) {
      await page.locator("#tags").fill(data.tags[i]);
      if (i === data.tags.length - 1) {
        await page.getByRole("button").getByText("Hinzufügen").click();
      } else {
        await page.locator("#tags").press("Enter");
      }
    }
  }

  if (data.imagePath) {
    await page.locator("#picture").setInputFiles(data.imagePath);
    await expect(page.getByAltText("Ereignisvorschau")).toBeVisible();
  }

  if (data.externalUrl) {
    await page.locator("input[type='radio'][value='EXTERNAL']").check();
    await page.locator("#externalParticipationUrl").fill(data.externalUrl);
  }

  if (data.date) {
    await page.locator("#start").pressSequentially(data.date.day, { delay: 150 });
    await page.locator("input[placeholder='hh']").first().pressSequentially(data.date.startHour, { delay: 150 });
    await page.locator("input[placeholder='mm']").first().pressSequentially(data.date.startMinute, { delay: 150 });
    
    if (data.date.durationHours) {
      await page.locator("input[placeholder='hh']").nth(1).pressSequentially(data.date.durationHours);
    }
    if (data.date.durationMinutes) {
      await page.locator("input[placeholder='mm']").nth(1).pressSequentially(data.date.durationMinutes);
    }
  }

  if (data.location) {
    await page.locator(".leaflet-container").scrollIntoViewIfNeeded();
    await page.waitForTimeout(2000);
    
    await page.locator("#physicalAddress").click();
    await page.locator("#physicalAddress").clear();
    await page.locator("#physicalAddress").pressSequentially(data.location.address, { delay: 150 });
    
    await page.waitForTimeout(3000);
    await page.waitForSelector("ul.z-10.w-full.border.list-none.absolute:visible", { timeout: 10000 });
    await page.waitForSelector(`text=${data.location.confirmationAddress}`, { timeout: 10000 });
    await page.getByText(data.location.confirmationAddress).click();
  }
}

export async function submitSingleEvent(page: Page): Promise<void> {
  await page.getByRole("button").getByText("Einzeltermin anlegen").click();
  await expect(page).toHaveURL(/.*\/app\/single-events/);
}

export async function submitSerialEvent(page: Page): Promise<void> {
  await page.getByRole("button").getByText("Serientermin anlegen").click();
  await expect(page).toHaveURL(/.*\/app\/series-events/);
}

export async function viewEventFromList(page: Page, eventName: string): Promise<void> {
  const eventRow = page.getByRole("row", { name: new RegExp(eventName) }).first();
  await eventRow.getByRole("button", { name: "Zum Termin" }).first().click();
  await expect(page).toHaveURL(/.*\/events\/.*/);
}

export async function editEventFromList(page: Page, eventName: string): Promise<void> {
  const eventRow = page.getByRole("row", { name: new RegExp(eventName) }).first();
  await eventRow.getByRole("button", { name: "Bearbeiten" }).first().click();
  await expect(page).toHaveURL(/.*\/app\/created-events\/.*\/edit/);
}

export async function copyEventFromList(page: Page, eventName: string): Promise<void> {
  const eventRow = page.getByRole("row", { name: new RegExp(eventName) }).first();
  await eventRow.locator(".kern-icon--content-copy").first().click();
  await expect(page).toHaveURL(/.*\/app\/single-events\/create\?templateEventId=.*/);
}

export async function copySerialEventFromList(page: Page, eventName: string): Promise<void> {
  await page.waitForLoadState("networkidle");
  
  const rows = await page.locator("tr.kern-table__row").all();
  for (const row of rows) {
    const rowText = await row.textContent();
    if (rowText && rowText.includes(eventName)) {
      await row.locator(".kern-icon--content-copy").first().click();
      break;
    }
  }
  await expect(page).toHaveURL(/.*\/app\/series-events\/create\?templateEventId=.*/);
}

export async function verifyEventDetails(page: Page, data: EventFormData): Promise<void> {
  await expect(page.getByRole("heading", { name: data.name }).first()).toBeVisible();
  await expect(page.getByText(data.description)).toBeVisible();

  if (data.category) {
    await expect(page.getByText(data.category)).toBeVisible();
  }

  if (data.tags) {
    for (const tag of data.tags) {
      await expect(page.getByText(tag)).toBeVisible();
    }
  }

  if (data.imagePath) {
    const imageName = data.imagePath.split('/').pop()?.split('.')[0];
    if (imageName) {
      await expect(page.locator(`img[src*="${imageName}"]`).first()).toBeVisible();
    }
  }

  if (data.externalUrl) {
    await expect(page.getByText("Externe Anmeldung")).toBeVisible();
    await expect(page.getByText("Jetzt anmelden")).toBeVisible();
    await expect(page.locator(`a[href="${data.externalUrl}"]`)).toBeVisible();
  }

  if (data.location) {
    const addressParts = data.location.address.split(" ");
    const addressPart1 = `${addressParts[0]} ${addressParts[1]}`;
    const addressPart2 = `${addressParts[2]} ${addressParts[3]}`;
    await expect(page.getByText(addressPart1)).toBeVisible();
    await expect(page.getByText(addressPart2)).toBeVisible();
    await expect(page.getByRole("button", { name: "Google Maps" })).toBeVisible();
    await expect(page.getByRole("button", { name: "Apple Karten" })).toBeVisible();
    await expect(page.getByRole("button", { name: "Geokoordinaten öffnen" })).toBeVisible();
  }
}

export async function editEventForm(page: Page, data: EventFormData): Promise<void> {
  await page.waitForLoadState("networkidle");

  if (data.name) {
    await page.locator("#name").click();
    await page.locator("#name").scrollIntoViewIfNeeded();
    await page.locator("#name").fill("");
    await page.locator("#name").pressSequentially(data.name);
  }

  if (data.description) {
    await page.locator("#description").click();
    await page.locator("#description").scrollIntoViewIfNeeded();
    await page.locator("#description").fill("");
    await page.locator("#description").pressSequentially(data.description);
  }

  if (data.name) {
    await expect(page.locator("#name")).toHaveValue(data.name);
  }
  if (data.description) {
    await expect(page.locator("#description")).toHaveValue(data.description);
  }
}

export async function saveEventChanges(page: Page): Promise<void> {
  await Promise.all([
    page.waitForURL(/.*\/events\/.*/, { timeout: 15000 }),
    page.getByRole("button").getByText("Änderung Speichern").click(),
  ]);
}

export async function register(page: Page, config: TestConfig, email?: string): Promise<void> {
  const testEmail = email || `e2e_test_${createUUID()}@54gradsoftware.de`;
  const testPassword = "NichtSicher1234!";

  await page.goto(config.siteUrl);
  await page.getByRole("button").getByText("Intern").click();
  await expect(page).toHaveURL(/.*\/login/);

  await page.getByRole("link", { name: "Noch kein Konto? Jetzt registrieren!" }).click();
  await expect(page).toHaveURL(/.*\/register/);

  await page.locator("#email").fill(testEmail);
  await page.getByLabel("Passwort").first().fill(testPassword);
  await page.getByLabel("Passwort").nth(1).fill(testPassword);

  await page.getByRole("button", { name: "Jetzt registrieren" }).click();
  await expect(page.getByText("Erfolg").first()).toBeVisible();
}

export function getFutureDate(monthsInFuture: number = 4): string {
  const futureDate = new Date();
  futureDate.setMonth(futureDate.getMonth() + monthsInFuture);
  return futureDate.toLocaleDateString("de-DE", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

export async function expectErrorMessage(page: Page, errorText: string): Promise<void> {
  await expect(page.getByText(errorText)).toBeVisible();
}

export async function viewSerialEventFromList(page: Page, eventName: string): Promise<void> {
  await page.waitForLoadState("networkidle");
  
  const rows = await page.locator("tr.kern-table__row").all();
  for (const row of rows) {
    const rowText = await row.textContent();
    if (rowText && rowText.includes(eventName)) {
      await row.locator("button").filter({ hasText: "Ansehen" }).click();
      break;
    }
  }

  await expect(page).toHaveURL(/.*\/app\/series-events\/.*/);
  await expect(page.getByText(eventName).first()).toBeVisible();
}

export async function fillSerialEventFormWithCustomDate(page: Page, data: EventFormData & { 
  endDate?: string; 
  interval?: string; 
}): Promise<void> {
  await page.locator("#name").fill(data.name);
  await page.locator("#description").fill(data.description);

  if (data.category) {
    await page.locator("select[name='category']").selectOption(data.category);
  }

  if (data.tags && data.tags.length > 0) {
    for (let i = 0; i < data.tags.length; i++) {
      await page.locator("#tags").fill(data.tags[i]);
      if (i === data.tags.length - 1) {
        await page.getByRole("button").getByText("Hinzufügen").click();
      } else {
        await page.locator("#tags").press("Enter");
      }
    }
  }

  if (data.endDate) {
    await page.locator("#end").pressSequentially(data.endDate, { delay: 150 });
  }

  if (data.date) {
    await page.locator("input[placeholder='hh']").first().pressSequentially(data.date.startHour, { delay: 150 });
    await page.locator("input[placeholder='mm']").first().pressSequentially(data.date.startMinute, { delay: 150 });
    
    if (data.date.durationHours) {
      await page.locator("input[placeholder='hh']").nth(1).pressSequentially(data.date.durationHours);
    }
    if (data.date.durationMinutes) {
      await page.locator("input[placeholder='mm']").nth(1).pressSequentially(data.date.durationMinutes);
    }
  }

  if (data.interval) {
    await page.locator("select[name='intervall']").selectOption(data.interval);
  }
}

export async function verifySerialEventDates(page: Page, startDate: Date, endDate: Date, interval: string): Promise<void> {
  const currentDate = new Date(startDate);
  const targetDate = new Date(endDate);
  
  let expectedCount = 0;
  if (interval === "Monatlich") {
    let monthDiff = targetDate.getMonth() - currentDate.getMonth() + 
                    12 * (targetDate.getFullYear() - currentDate.getFullYear());
    
    if (targetDate.getDate() < currentDate.getDate()) {
      monthDiff--;
    }
    
    expectedCount = monthDiff + 1;
  }

  await page.waitForLoadState("networkidle");
  
  let dateRows = 0;
  
  const specificSelector = "tbody.kern-table__body tr.kern-table__row";
  const specificElements = await page.locator(specificSelector).count();
  
  if (specificElements > 0) {
    dateRows = specificElements;
  } else {
    const generalSelector = "tr.kern-table__row";
    const generalElements = await page.locator(generalSelector).count();
    
    if (generalElements > 0) {
      dateRows = generalElements;
    } else {
      console.log("No date rows found. Page URL:", await page.url());
      console.log("Page title:", await page.title());
      
      const anyRows = await page.locator("tr").count();
      console.log("Total tr elements found:", anyRows);
      
      throw new Error(`No date rows found on the page. Expected table with individual event dates.`);
    }
  }

  console.log(`Date calculation: Start: ${currentDate.toISOString()}, End: ${targetDate.toISOString()}`);
  console.log(`Expected ${expectedCount} date rows, found ${dateRows} date rows`);

  if (dateRows !== expectedCount) {
    throw new Error(`Expected ${expectedCount} date rows, but found ${dateRows} date rows`);
  }
}

