import { test, expect } from '@playwright/test';
import { loadEnv } from './helpers/testHelpers';

test('basic search functionality', async ({ page }) => {
    const config = loadEnv();
    await page.goto(`${config.siteUrl}/search`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(200);

    await expect(page.getByRole('heading', { name: 'Veranstaltungen finden' })).toBeVisible();

    const searchInput = page.locator('input[name="searchTerm"]');
    await expect(searchInput).toBeVisible();
    await searchInput.fill('Test');

    await page.getByRole('button', { name: 'Suchen' }).click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(200);

    const noEventsText = page.getByText('Keine Veranstaltungen gefunden');
    const eventsFoundText = page.getByText(/\d+ Veranstaltungen gefunden/);

    const hasResults = await eventsFoundText.isVisible();
    const hasNoResults = await noEventsText.isVisible();

    expect(hasResults || hasNoResults).toBe(true);
});

test('search with category filter', async ({ page }) => {
    const config = loadEnv();
    await page.goto(`${config.siteUrl}/search`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(200);

    const filterButton = page.getByRole('button', { name: 'Filter' });
    if (await filterButton.isVisible()) {
        await filterButton.click();
        await page.waitForTimeout(300);
    }

    const categoryAccordion = page.locator('summary:has-text("Kategorien")');
    const artsCheckbox = page.locator('input[type="checkbox"][value="ARTS"]');

    if (!(await artsCheckbox.isVisible())) {
        await categoryAccordion.click();
        await page.waitForTimeout(500);
    }

    await artsCheckbox.click();
    await page.waitForTimeout(200);
    await expect(artsCheckbox).toBeChecked();

    const searchButtons = page.getByRole('button', { name: 'Suchen' });
    await searchButtons.first().click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(200);

    const savedCategory = await page.evaluate(() => {
        return localStorage.getItem('searchCategory');
    });

    expect(savedCategory).toBe('["ARTS"]');

    const noEventsText = page.getByText('Keine Veranstaltungen gefunden');
    const eventsFoundText = page.getByText(/\d+ Veranstaltungen gefunden/);

    const hasResults = await eventsFoundText.isVisible();
    const hasNoResults = await noEventsText.isVisible();

    expect(hasResults || hasNoResults).toBe(true);
});
