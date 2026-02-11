import { test, expect } from "@playwright/test";
import { loadEnv } from "./helpers/testHelpers";
import { searchDefaults } from "../../frontend/src/lib/instanceConfig";

test("check landing page location search default values", async ({ page }) => {
    const config = loadEnv();
    await page.goto(config.siteUrl);
    
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    const locationInput = page.locator('input[name="address"]');
    const radiusInput = page.locator('select[name="radius"]');

    // Wait for inputs to be visible and enabled
    await locationInput.waitFor({ state: 'visible' });
    await radiusInput.waitFor({ state: 'visible' });
    
    // Wait for the value to be populated (Vue might take time to set initial values)
    await expect(radiusInput).toHaveValue(searchDefaults.searchRadius.toString(), { timeout: 5000 });
    await expect(locationInput).toHaveValue(searchDefaults.locationAddress, { timeout: 5000 });
});


test("change location search values and verify persistence", async ({ page }) => {
    const config = loadEnv();
    await page.goto(config.siteUrl);

    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    const locationInput = page.locator('input[name="address"]');
    const radiusInput = page.locator('select[name="radius"]');
    const searchButton = page.locator('button:has-text("Suchen")');
    const syncIcon = page.locator('.search-location-input-wrapper .icon-wrapper .kern-icon--sync');
    const checkIcon = page.locator('.search-location-input-wrapper .icon-wrapper .kern-icon--check');
    const suggestionsList = page.locator('.search-location-input-wrapper ul');

    const newAddress = "Flensburg";
    const newRadiusValue = "25";

    // Change location and radius
    await radiusInput.selectOption(newRadiusValue);
    await locationInput.fill(newAddress);

    // Triggered search state
    await expect(syncIcon).toBeVisible();
    await expect(searchButton).toBeDisabled();

    await syncIcon.waitFor({ state: 'hidden', timeout: 10000 });

    // After suggestions are loaded/fetched
    await expect(syncIcon).not.toBeVisible();
    await expect(searchButton).toBeEnabled();
    await expect(suggestionsList).toBeVisible();

    // Select first suggestion
    const clickableSuggestion = suggestionsList.locator('li').first();
    await clickableSuggestion.click();

    const actualAddress = await locationInput.inputValue();
    expect(actualAddress).toContain(newAddress);

    // Perfect match indicated by check icon
    await checkIcon.waitFor({ state: 'visible', timeout: 10000 });
    await expect(checkIcon).toBeVisible();
    await expect(searchButton).toBeEnabled();
    await expect(suggestionsList).not.toBeVisible();

    // Execute search
    await searchButton.click();
    
    await page.waitForURL('**/search');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    const searchPageLocationInput = page.locator('input[name="address"]');
    const searchPageRadiusInput = page.locator('select[name="radius"]');
    
    // Verify values on search page
    await expect(searchPageLocationInput).toHaveValue(actualAddress, { timeout: 5000 });
    await expect(searchPageRadiusInput).toHaveValue(newRadiusValue, { timeout: 5000 });
    
    await page.reload();

    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    // Verify persistence after reload
    await expect(searchPageLocationInput).toHaveValue(actualAddress, { timeout: 5000 });
    await expect(searchPageRadiusInput).toHaveValue(newRadiusValue, { timeout: 5000 });
});


test("Verify search page functionality with changed location and radius", async ({ page }) => {
    const config = loadEnv();
    await page.goto(`${config.siteUrl}/search`);

    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    const contentArea = page.locator('.content-area');
    await contentArea.waitFor({ state: 'visible', timeout: 10000 });

    const locationInput = page.locator('input[name="address"]');
    const radiusInput = page.locator('select[name="radius"]');
    const searchButton = page.locator('.primary-search-button');
    const checkIcon = page.locator('.search-location-input-wrapper .icon-wrapper .kern-icon--check');
    const suggestionsList = page.locator('.search-location-input-wrapper ul');

    // Divided by 2 because of dual rendering of event cards
    const previousEventsAmount = await page.locator('.event-card').count() / 2; 

    expect(previousEventsAmount).toBeGreaterThan(0);

    const newAddress = "Flensburg, Schleswig-Holstein";
    const newRadiusValue = "50";

    // Change location and radius
    await radiusInput.selectOption(newRadiusValue);
    await locationInput.fill(newAddress);

    await checkIcon.waitFor({ state: 'visible', timeout: 10000 });
    await expect(checkIcon).toBeVisible();
    await expect(searchButton).toBeEnabled();
    await expect(suggestionsList).not.toBeVisible();
        
    await expect(radiusInput).toHaveValue(newRadiusValue, { timeout: 5000 });
    await expect(locationInput).toHaveValue(newAddress, { timeout: 5000 });

    // Perform a search
    await searchButton.click();
    await contentArea.waitFor({ state: 'visible', timeout: 10000 });

    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    await contentArea.waitFor({ state: 'visible', timeout: 10000 });

    // Verify persistence after reload
    await expect(radiusInput).toHaveValue(newRadiusValue, { timeout: 5000 });
    await expect(locationInput).toHaveValue(newAddress, { timeout: 5000 });

});


test("Verify search page functionality with text term and no location", async ({ page }) => {
    const config = loadEnv();
    await page.goto(`${config.siteUrl}/search`);

    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    const contentArea = page.locator('.content-area');
    await contentArea.waitFor({ state: 'visible', timeout: 10000 });

    const searchTermInput = page.locator('input[name="searchTerm"]');
    const locationInput = page.locator('input[name="address"]');
    const searchButton = page.locator('.primary-search-button');

    const searchTerm = "Musik";

    await locationInput.clear();
    await searchTermInput.fill(searchTerm);

    await searchButton.click();
    await contentArea.waitFor({ state: 'visible', timeout: 10000 });

    await expect(searchTermInput).toHaveValue(searchTerm);
    await expect(locationInput).toHaveValue("");

    await page.reload();
    await contentArea.waitFor({ state: 'visible', timeout: 10000 });

    await expect(searchTermInput).toHaveValue(searchTerm);
    await expect(locationInput).toHaveValue("");

    const eventsAmount = await page.locator('.event-card').count() / 2;

    if (eventsAmount === 0) {
        const filterResetButton = page.locator('button:has-text("Filter zurücksetzen")');
        await expect(filterResetButton).toBeVisible();

        await filterResetButton.click();
        await contentArea.waitFor({ state: 'visible', timeout: 10000 });

        const newEventsAmount = await page.locator('.event-card').count() / 2;
        expect(newEventsAmount).toBeGreaterThan(eventsAmount);
        expect(locationInput).toHaveValue(searchDefaults.locationAddress);
    }
    
});


test('Verify search with category filter and persistence', async ({ page }) => {
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