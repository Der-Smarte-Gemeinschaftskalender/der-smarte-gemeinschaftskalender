import { test, expect } from '@playwright/test';
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
    copySerialEventFromList,
    generateRandomTestName,
    getFutureDate,
    setupMonthlyTemplateControls,
    assertMonthlyTemplateControls,
    setupWeeklyTemplateControls,
    assertWeeklyTemplateControls,
} from './helpers/testHelpers';

test('serial termin with location', async ({ page }) => {
    const config = loadEnv();
    const eventName = generateRandomTestName('E2E Serial Test Termin with location');

    if (!config.testAddress || !config.confirmationTestAddress) {
        throw new Error('TEST_ADDRESS or CONFIRMATION_TEST_ADDRESS is not defined');
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
    await page.waitForLoadState('networkidle');
    await viewSerialEventFromList(page, eventName);
    await page.getByLabel('Ansehen').click();
    await verifyEventDetails(page, eventData);
});

test('serial termin with category and tags', async ({ page }) => {
    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial test termin with category and tags');

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    const eventData = {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
        category: 'Natur & Abenteuer',
        tags: ['Tag1', 'Tag2'],
    };

    await fillEventForm(page, eventData);
    await submitSerialEvent(page);
    await viewSerialEventFromList(page, eventName);
    await page.getByLabel('Ansehen').click();
    await verifyEventDetails(page, eventData);
});

test('serial termin with custom date', async ({ page }) => {
    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial test termin with custom date');

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    const futureDate = getFutureDate(4);
    const eventData = {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
        endDate: futureDate,
        interval: 'Monatlich',
        date: {
            day: '',
            startHour: '12',
            startMinute: '15',
            durationHours: '2',
            durationMinutes: '45',
        },
    };

    await fillSerialEventFormWithCustomDate(page, eventData);
    await submitSerialEvent(page);
    await viewSerialEventFromList(page, eventName);

    await expect(page.getByText(futureDate)).toBeVisible();
    await expect(page.getByText('Monatlich')).toBeVisible();

    const dateRows = await page.locator('tbody.kern-table__body tr.kern-table__row').count();
    if (dateRows < 3) {
        throw new Error(`Expected at least 3 date rows for a 4-month span, but found ${dateRows} date rows`);
    }

    await page.getByLabel('Ansehen').first().click();
    await expect(page.getByRole('heading', { name: eventName }).first()).toBeVisible();
});

test('serial termin use as template with monthly', async ({ page }) => {
    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial monthly template');
    const copyName = `${eventName} - kopie`;

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    const eventData = {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
    };

    await fillEventForm(page, eventData);

    // Extend the end date so at least one matching "2./3. Dienstag" falls inside the range
    const monthlyEndDate = new Date();
    monthlyEndDate.setMonth(monthlyEndDate.getMonth() + 3);
    await page.locator('#end').fill(monthlyEndDate.toISOString().split('T')[0]);

    const hasMonthlyControls = await setupMonthlyTemplateControls(page);
    test.skip(!hasMonthlyControls, 'Monthly series controls not enabled on this instance');

    await submitSerialEvent(page);
    await page.waitForLoadState('networkidle');

    // After submit we land directly on the show page of the created series event
    await expect(page).toHaveURL(/.*\/app\/series-events\/\d+/);
    await expect(page.getByText(eventName).first()).toBeVisible();
    await expect(page.getByText('Woche(n)')).toBeVisible();
    await expect(page.getByText('2. (Zweite), 3. (Dritte)')).toBeVisible();
    await expect(page.getByText('Dienstag')).toBeVisible();

    // Navigate back to the list explicitly and copy the series event as template
    await page.goto(`${config.siteUrl}/app/series-events`);
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/.*\/app\/series-events$/);
    await expect(page.getByText(eventName).first()).toBeVisible();

    await copySerialEventFromList(page, eventName);
    await page.waitForLoadState('networkidle');

    // Verify the create form is pre-filled from the template
    await expect(page.locator('#name')).toHaveValue(eventName);
    await assertMonthlyTemplateControls(page);

    await page.locator('#name').fill(copyName);
    await page.locator('.ProseMirror').fill(`${eventData.description} - kopie`);

    await submitSerialEvent(page);
    await page.waitForLoadState('networkidle');

    // The copy now has its own show page with the same recurrence settings
    await expect(page).toHaveURL(/.*\/app\/series-events\/\d+/);
    await expect(page.getByText(copyName).first()).toBeVisible();
    await expect(page.getByText('Woche(n)')).toBeVisible();
    await expect(page.getByText('2. (Zweite), 3. (Dritte)')).toBeVisible();
    await expect(page.getByText('Dienstag')).toBeVisible();
});

test('serial termin use as template with weekly', async ({ page }) => {
    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial weekly template');
    const copyName = `${eventName} - kopie`;

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    const eventData = {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
    };

    await fillEventForm(page, eventData);

    // Extend the end date so at least one Dienstag falls inside the range
    const weeklyEndDate = new Date();
    weeklyEndDate.setDate(weeklyEndDate.getDate() + 14);
    await page.locator('#end').fill(weeklyEndDate.toISOString().split('T')[0]);

    const hasWeeklyControls = await setupWeeklyTemplateControls(page);
    test.skip(!hasWeeklyControls, 'Weekly series controls not enabled on this instance');

    await submitSerialEvent(page);
    await page.waitForLoadState('networkidle');

    // After submit we land directly on the show page of the created series event
    await expect(page).toHaveURL(/.*\/app\/series-events\/\d+/);
    await expect(page.getByText(eventName).first()).toBeVisible();
    await expect(page.getByText('Wochentag')).toBeVisible();
    await expect(page.getByText('Dienstag')).toBeVisible();

    // Navigate back to the list explicitly and copy the series event as template
    await page.goto(`${config.siteUrl}/app/series-events`);
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/.*\/app\/series-events$/);
    await expect(page.getByText(eventName).first()).toBeVisible();

    await copySerialEventFromList(page, eventName);
    await page.waitForLoadState('networkidle');

    // Verify the create form is pre-filled from the template
    await expect(page.locator('#name')).toHaveValue(eventName);
    await assertWeeklyTemplateControls(page);

    await page.locator('#name').fill(copyName);
    await page.locator('.ProseMirror').fill(`${eventData.description} - kopie`);

    await submitSerialEvent(page);
    await page.waitForLoadState('networkidle');

    // The copy now has its own show page with the same recurrence settings
    await expect(page).toHaveURL(/.*\/app\/series-events\/\d+/);
    await expect(page.getByText(copyName).first()).toBeVisible();
    await expect(page.getByText('Wochentag')).toBeVisible();
    await expect(page.getByText('Dienstag')).toBeVisible();
});

test('serial termin use as template with holidays', async ({ page }) => {
    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial holidays template');
    const copyName = `${eventName} - kopie`;
    const holidayState = 'be';

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    const eventData = {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
    };

    await fillEventForm(page, eventData);

    // Extend the end date so there is room for at least one non-holiday, non-vacation day
    const holidaysEndDate = new Date();
    holidaysEndDate.setDate(holidaysEndDate.getDate() + 21);
    await page.locator('#end').fill(holidaysEndDate.toISOString().split('T')[0]);

    const holidaysCheckbox = page.locator('input[name="holidaysEnabled"]');
    const schoolHolidaysCheckbox = page.locator('input[name="schoolHolidaysEnabled"]');
    const stateSelect = page.locator('select[name="state"]');
    const hasHolidayControls = await holidaysCheckbox.isVisible();
    test.skip(!hasHolidayControls, 'Holiday controls not enabled on this instance');

    await holidaysCheckbox.check();
    await schoolHolidaysCheckbox.check();
    await expect(stateSelect).toBeVisible();
    await stateSelect.selectOption(holidayState);

    await submitSerialEvent(page);
    await page.waitForLoadState('networkidle');

    // After submit we land directly on the show page of the created series event
    await expect(page).toHaveURL(/.*\/app\/series-events\/\d+/);
    await expect(page.getByText(eventName).first()).toBeVisible();

    // Navigate back to the list explicitly and copy the series event as template
    await page.goto(`${config.siteUrl}/app/series-events`);
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/.*\/app\/series-events$/);
    await expect(page.getByText(eventName).first()).toBeVisible();

    await copySerialEventFromList(page, eventName);
    await page.waitForLoadState('networkidle');

    // Verify the create form is pre-filled from the template
    await expect(page.locator('#name')).toHaveValue(eventName);
    await expect(holidaysCheckbox).toBeChecked();
    await expect(schoolHolidaysCheckbox).toBeChecked();
    await expect(stateSelect).toHaveValue(holidayState);

    await page.locator('#name').fill(copyName);
    await page.locator('.ProseMirror').fill(`${eventData.description} - kopie`);

    await submitSerialEvent(page);
    await page.waitForLoadState('networkidle');

    // The copy now has its own show page
    await expect(page).toHaveURL(/.*\/app\/series-events\/\d+/);
    await expect(page.getByText(copyName).first()).toBeVisible();
});
