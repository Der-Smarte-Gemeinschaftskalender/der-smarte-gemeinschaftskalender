import * as fs from "fs";
import * as path from "path";
import { Page, expect } from "@playwright/test";

export function generateTestICalFile(testIdentifier?: string): {
  eventName: string;
  eventStart: Date;
} {
  const now = new Date();
  const eventDate = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);

  const randomSuffix = Math.floor(Math.random() * 10000)
    .toString()
    .padStart(3, "0");
  const eventName = `Test Event ${randomSuffix}`;

  const baseDescription =
    "This is a test event generated for E2E testing purposes. It was created automatically with a semi-random name.";
  const description = testIdentifier
    ? `${baseDescription} Test ID: ${testIdentifier}`
    : baseDescription;

  const formatDateForICS = (date: Date): string => {
    return date
      .toISOString()
      .replace(/[-:]/g, "")
      .replace(/\.\d{3}/, "");
  };

  const eventStart = new Date(eventDate);
  eventStart.setHours(10, 0, 0, 0);

  const eventEnd = new Date(eventStart);
  eventEnd.setHours(12, 0, 0, 0);

  const uid = `test-event-${Date.now()}@e2e-tests.com`;

  const icsContent = `BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//E2E Tests//E2E Test Calendar//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
UID:${uid}
DTSTART:${formatDateForICS(eventStart)}
DTEND:${formatDateForICS(eventEnd)}
SUMMARY:${eventName}
DESCRIPTION:${description}
LOCATION:Test Location
STATUS:CONFIRMED
TRANSP:OPAQUE
BEGIN:VALARM
ACTION:DISPLAY
DESCRIPTION:Reminder
TRIGGER:-PT15M
END:VALARM
END:VEVENT
END:VCALENDAR`;

  const filePath = path.join(__dirname, "../testFiles/test.ics");
  fs.writeFileSync(filePath, icsContent, "utf8");

  console.log(
    `Generated ICS file with event: "${eventName}" scheduled for ${eventStart.toLocaleString()}`
  );

  return { eventName, eventStart };
}

export function getTestICalFilePath(): string {
  return path.join(__dirname, "../testFiles/test.ics");
}

export async function uploadICalFile(page: Page, filePath: string): Promise<void> {
  if (!fs.existsSync(filePath)) {
    throw new Error(`File not found: ${filePath}`);
  }

  console.log(filePath);

  await page.locator('input[type="file"]').setInputFiles(filePath);
}
