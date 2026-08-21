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
    selectEventPicture,
    submitSerialEventExpectingPictureError,
    addTagsWithEnter,
    PICTURE_FORMAT_ERROR_MESSAGE,
    PICTURE_TOO_LARGE_ERROR_MESSAGE,
} from './helpers/testHelpers';
import {
    createJpegTestImage,
    createLargeTestImage,
    createNonImageTestFile,
    createUnsupportedFormatTestFile,
    MAX_PICTURE_BYTES,
    OVERSIZED_IMAGE_BYTES,
} from './helpers/imageHelper';

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

test('serial termin with image on all events', async ({ page }) => {
    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial test termin with image');

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    const eventData = {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
        imagePath: './e2e/testFiles/test-image.png',
    };

    await fillEventForm(page, eventData);

    await page.locator('#end').pressSequentially(getFutureDate(4), { delay: 150 });

    await submitSerialEvent(page);
    await viewSerialEventFromList(page, eventName);

    const viewButtons = page.getByLabel('Ansehen');
    await expect(viewButtons.first()).toBeVisible({ timeout: 15000 });
    const eventCount = await viewButtons.count();
    expect(eventCount).toBeGreaterThanOrEqual(2);

    for (let i = 0; i < eventCount; i++) {
        await page.getByLabel('Ansehen').nth(i).click();
        await verifyEventDetails(page, eventData);
        await page.goBack();
        await page.waitForLoadState('networkidle');
    }
});

test('serial termin with large image on long series', async ({ page }) => {
    test.setTimeout(300000);

    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial test termin with large image');
    const imagePath = createLargeTestImage();
    const imageName = imagePath.split('/').pop()!.split('.')[0]!;

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    const eventData = {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
        imagePath,
    };

    await fillEventForm(page, eventData);

    await page.locator('#end').pressSequentially(getFutureDate(6), { delay: 150 });

    const createRequest = page.waitForResponse(
        (response) => response.request().method() === 'POST' && response.url().includes('/series-events'),
        { timeout: 240000 }
    );

    await submitSerialEvent(page);

    await expect(page.getByRole('heading', { name: 'Serientermin wird angelegt' })).toBeVisible();

    const createResponse = await createRequest;
    expect(createResponse.status()).toBe(200);

    const createdEvents = (await createResponse.json()).seriesEvent.created_events;
    expect(createdEvents.length).toBeGreaterThanOrEqual(20);

    await viewSerialEventFromList(page, eventName);
    await expect(page.getByLabel('Ansehen').first()).toBeVisible({ timeout: 15000 });

    const paginationInfo = await page.locator('.pagination-info').first().textContent();
    const totalPages = Number(paginationInfo?.match(/von\s+(\d+)/)?.[1] ?? 1);
    expect(totalPages).toBeGreaterThan(1);

    const getImageSourceOfEvent = async (pageNumber: number, position: 'first' | 'last') => {
        for (let currentPage = 1; currentPage < pageNumber; currentPage++) {
            await page.getByLabel('Nächste Seite').click();
        }
        await expect(page.locator('.pagination-info').first()).toHaveText(`Seite ${pageNumber} von ${totalPages}`);

        const viewButtons = page.getByLabel('Ansehen');
        await (position === 'first' ? viewButtons.first() : viewButtons.last()).click();
        await verifyEventDetails(page, eventData);
        const imageSource = await page.locator(`img[src*="${imageName}"]`).first().getAttribute('src');

        await page.goBack();
        await page.waitForLoadState('networkidle');
        await expect(page.getByLabel('Ansehen').first()).toBeVisible({ timeout: 15000 });

        return imageSource;
    };

    // Erster, mittlerer und letzter Termin der Serie
    const imageSources = [
        await getImageSourceOfEvent(1, 'first'),
        await getImageSourceOfEvent(Math.ceil(totalPages / 2), 'first'),
        await getImageSourceOfEvent(totalPages, 'last'),
    ];

    for (const imageSource of imageSources) {
        expect(imageSource).toBeTruthy();
        // Gleiche URL = dieselbe Mobilizon-Media, also nur ein Upload für die ganze Serie.
        expect(imageSource).toBe(imageSources[0]);
    }
});

test('serial termin keeps jpeg image on first save', async ({ page }) => {
    test.setTimeout(180000);

    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial test termin with jpeg image');
    const imagePath = createJpegTestImage();

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    const eventData = {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
        imagePath,
    };

    await fillEventForm(page, eventData);

    await page.locator('#end').pressSequentially(getFutureDate(1), { delay: 150 });

    const createRequest = page.waitForResponse(
        (response) => response.request().method() === 'POST' && response.url().includes('/series-events'),
        { timeout: 120000 }
    );

    await submitSerialEvent(page);

    const createResponse = await createRequest;
    expect(createResponse.status()).toBe(200);

    const seriesEvent = (await createResponse.json()).seriesEvent;

    // Das Bild muss bereits beim Anlegen der Serie übernommen werden. Wird es still verworfen,
    // müsste jeder Termin der Serie einzeln nachbearbeitet werden.
    expect(seriesEvent.mobilizon_fields?.picture?.url).toBeTruthy();
    expect(seriesEvent.created_events.length).toBeGreaterThanOrEqual(2);

    await viewSerialEventFromList(page, eventName);

    const viewButtons = page.getByLabel('Ansehen');
    await expect(viewButtons.first()).toBeVisible({ timeout: 15000 });
    const eventCount = await viewButtons.count();

    for (let i = 0; i < eventCount; i++) {
        await page.getByLabel('Ansehen').nth(i).click();
        await verifyEventDetails(page, eventData);
        await page.goBack();
        await page.waitForLoadState('networkidle');
    }
});

test('serial termin keeps image while filling the form', async ({ page }) => {
    test.setTimeout(180000);

    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial test termin with image and tags');
    const imagePath = createJpegTestImage();

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    const eventData = {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
        imagePath,
        tags: ['SerienbildTagEins', 'SerienbildTagZwei'],
    };

    // Erst das Bild wählen, danach den Rest ausfüllen - so arbeitet man das Formular von oben nach unten ab.
    await fillEventForm(page, { name: eventData.name, description: eventData.description, imagePath });
    await expect(page.getByAltText('Ereignisvorschau')).toBeVisible();

    const submitRequests: string[] = [];
    page.on('request', (request) => {
        if (request.method() === 'POST' && request.url().includes('/series-events')) {
            submitRequests.push(request.url());
        }
    });

    // Enter in einem einzeiligen Feld löst im Browser den ersten Submit-Button des Formulars aus.
    // Das darf weder das ausgewählte Bild entfernen noch den Serientermin vorzeitig anlegen.
    const enterFields = [
        { selector: '#name', label: 'Titel', value: eventName },
        { selector: '#onlineAddress', label: 'Webseite', value: 'https://example.org' },
        { selector: '#end', label: 'Enddatum' },
        { selector: "input[placeholder='hh']", label: 'Uhrzeit' },
    ];

    for (const field of enterFields) {
        const input = page.locator(field.selector).first();
        if (field.value) await input.fill(field.value);
        await input.press('Enter');

        await expect(page.getByAltText('Ereignisvorschau'), `Bild nach Enter im Feld "${field.label}"`).toBeVisible();
        await expect(page).toHaveURL(/.*\/app\/series-events\/create/);
    }

    await addTagsWithEnter(page, eventData.tags);
    await expect(page.getByAltText('Ereignisvorschau'), 'Bild nach Enter im Schlagwortfeld').toBeVisible();
    await expect(page).toHaveURL(/.*\/app\/series-events\/create/);

    expect(submitRequests, 'Serientermin darf beim Ausfüllen nicht angelegt werden').toHaveLength(0);

    await page.locator('#end').fill('');
    await page.locator('#end').pressSequentially(getFutureDate(1), { delay: 150 });

    const createRequest = page.waitForResponse(
        (response) => response.request().method() === 'POST' && response.url().includes('/series-events'),
        { timeout: 120000 }
    );

    await submitSerialEvent(page);

    const createResponse = await createRequest;
    expect(createResponse.status()).toBe(200);

    const seriesEvent = (await createResponse.json()).seriesEvent;
    expect(seriesEvent.mobilizon_fields?.picture?.url).toBeTruthy();

    await viewSerialEventFromList(page, eventName);
    await page.getByLabel('Ansehen').first().click();
    await verifyEventDetails(page, eventData);
});

test('serial termin keeps image at the upper size limit', async ({ page }) => {
    test.setTimeout(180000);

    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial test termin with image at size limit');
    // Knapp unter dem Formular-Limit: Was das Formular annimmt, muss auch gespeichert werden -
    // sonst landet die Serie ohne Bild und ohne Fehlermeldung (z.B. bei kleinerem PHP-Uploadlimit).
    const imagePath = createJpegTestImage(MAX_PICTURE_BYTES - 10_000, 'size-limit-test-flyer.jpg');

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    const eventData = {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
        imagePath,
    };

    await fillEventForm(page, eventData);

    await page.locator('#end').pressSequentially(getFutureDate(1), { delay: 150 });

    const createRequest = page.waitForResponse(
        (response) => response.request().method() === 'POST' && response.url().includes('/series-events'),
        { timeout: 120000 }
    );

    await submitSerialEvent(page);

    const createResponse = await createRequest;
    expect(createResponse.status()).toBe(200);

    const seriesEvent = (await createResponse.json()).seriesEvent;
    expect(seriesEvent.mobilizon_fields?.picture?.url).toBeTruthy();

    await viewSerialEventFromList(page, eventName);
    await page.getByLabel('Ansehen').first().click();
    await verifyEventDetails(page, eventData);
});

test('serial termin rejects image above the size limit', async ({ page }) => {
    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial test termin with oversized image');
    const imagePath = createLargeTestImage(OVERSIZED_IMAGE_BYTES, 'oversized-test-image.png');

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    await fillEventForm(page, {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
    });

    await selectEventPicture(page, imagePath);

    // Die Meldung muss die Größe benennen, damit klar ist, was zu tun ist
    await submitSerialEventExpectingPictureError(page, PICTURE_TOO_LARGE_ERROR_MESSAGE);
});

// Formate, die Nutzer*innen typischerweise erwischen: PDF-Flyer, iPhone-Foto, Scan, Vektorgrafik
const unsupportedPictureFiles = [
    { label: 'PDF', createFile: () => createNonImageTestFile() },
    { label: 'HEIC', createFile: () => createUnsupportedFormatTestFile('iphone-foto.heic') },
    { label: 'TIFF', createFile: () => createUnsupportedFormatTestFile('scan.tiff') },
    { label: 'SVG', createFile: () => createUnsupportedFormatTestFile('grafik.svg') },
];

for (const pictureFile of unsupportedPictureFiles) {
    test(`serial termin rejects ${pictureFile.label} with a message about the format`, async ({ page }) => {
        const config = loadEnv();
        const eventName = generateRandomTestName(`E2E serial test termin with ${pictureFile.label} image`);
        const filePath = pictureFile.createFile();

        await login(page, config);
        await navigateToApp(page, config);
        await createSerialEvent(page);

        await fillEventForm(page, {
            name: eventName,
            description: `Das ist eine Beschreibung${eventName}`,
        });

        await selectEventPicture(page, filePath);

        // Die Meldung muss das Format benennen - "zu groß" wäre hier irreführend
        await submitSerialEventExpectingPictureError(page, PICTURE_FORMAT_ERROR_MESSAGE);
    });
}

test('serial termin is not created when the server cannot store the picture', async ({ page }) => {
    test.setTimeout(180000);

    const config = loadEnv();
    const eventName = generateRandomTestName('E2E serial test termin with broken image');
    // Erlaubte Dateiendung, unbrauchbarer Inhalt: kommt durch die Formularprüfung, Mobilizon
    // kann das Bild aber nicht verarbeiten. Die Serie darf dann nicht ohne Bild angelegt werden.
    const filePath = createNonImageTestFile('kaputtes-bild.png');

    await login(page, config);
    await navigateToApp(page, config);
    await createSerialEvent(page);

    await fillEventForm(page, {
        name: eventName,
        description: `Das ist eine Beschreibung${eventName}`,
    });

    await selectEventPicture(page, filePath);

    const createRequest = page.waitForResponse(
        (response) => response.request().method() === 'POST' && response.url().includes('/series-events'),
        { timeout: 120000 }
    );

    await page.getByRole('button').getByText('Serientermin anlegen').click();

    const createResponse = await createRequest;
    expect(createResponse.status(), 'Server darf die Serie nicht stillschweigend ohne Bild anlegen').toBe(422);

    await expect(page.getByText('Das Bild konnte nicht gespeichert werden.')).toBeVisible();
    await expect(page).toHaveURL(/.*\/app\/series-events\/create/);
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

    // Extend the end date so there is room for at least one non-holiday, non-vacation day.
    const holidaysEndDate = new Date();
    holidaysEndDate.setDate(holidaysEndDate.getDate() + 70);
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
