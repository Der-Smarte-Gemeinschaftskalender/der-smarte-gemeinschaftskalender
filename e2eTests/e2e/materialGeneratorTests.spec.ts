import { test, expect } from "@playwright/test";
import {
  loadEnv,
  login,
  navigateToApp,
} from "./helpers/testHelpers";

async function goToMaterialGenerator(page: Parameters<typeof login>[0], config: Awaited<ReturnType<typeof loadEnv>>) {
  await login(page, config);
  await navigateToApp(page, config);
  await page.getByText("Werbemittel").first().click();
  await expect(page).toHaveURL(/.*\/material-generator\/.*/);
  await page.waitForLoadState("networkidle");
}


test('loads and renders without error', async ({ page }) => {
    const config = loadEnv();
    await goToMaterialGenerator(page, config);

    await expect(page.getByRole('heading', { name: /Werbemittelgenerator/i }).first()).toBeVisible();

    const canvas = page.locator('canvas#canvas1');
    await expect(canvas).toBeVisible();

    await expect(page.locator('.kern-alert--danger')).not.toBeVisible();

    await expect(page.locator('.mg-sidebar')).toBeVisible();

    await expect(page.getByRole('button', { name: 'Herunterladen' }).first()).toBeVisible();
});

test('download produces a PNG file', async ({ page }) => {
    const config = loadEnv();
    await goToMaterialGenerator(page, config);

    await page.waitForTimeout(3000);

    const downloadPromise = page.waitForEvent('download', { timeout: 15000 });
    await page.getByRole('button', { name: 'Herunterladen' }).first().click();
    const download = await downloadPromise;

    const downloadPath = await download.path();
    expect(downloadPath).toBeTruthy();

    expect(download.suggestedFilename()).toMatch(/\.(png|PNG)$/);
    expect(download.suggestedFilename()).toMatch(/veranstaltungsuebersicht/i);
});

test('switching dimension re-renders canvas', async ({ page }) => {
    const config = loadEnv();
    await goToMaterialGenerator(page, config);

    const dimensionSelect = page.locator('[name="dimensionOptions"]');
    await expect(dimensionSelect).toBeVisible();
    await dimensionSelect.selectOption('post');

    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    await expect(page.locator('canvas#canvas1')).toBeVisible();
    await expect(page.locator('.kern-alert--danger')).not.toBeVisible();

    await dimensionSelect.selectOption('story');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    await expect(page.locator('canvas#canvas1')).toBeVisible();
    await expect(page.locator('.kern-alert--danger')).not.toBeVisible();
});

test('event picker opens and shows events or hint', async ({ page }) => {
    const config = loadEnv();
    await goToMaterialGenerator(page, config);

    const fromDateInput = page.locator('[name="fromDate"]');
    const toDateInput = page.locator('[name="toDate"]');
    await fromDateInput.fill('2020-01-01');
    await fromDateInput.blur();
    await toDateInput.fill('2099-12-31');
    await toDateInput.blur();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const pickerButton = page.getByRole('button', { name: /Auswählen/ });
    const hasEvents = await pickerButton.isVisible();

    if (hasEvents) {
        await pickerButton.click();

        const pickerPanel = page.locator('.mg-sidebar .kern-card').last();
        await expect(pickerPanel).toBeVisible();

        const checkboxes = pickerPanel.locator('input[type="checkbox"]');
        await expect(checkboxes.first()).toBeVisible();

        await expect(page.getByRole('button', { name: /Alle (aus|ab)wählen/ })).toBeVisible();
    } else {
        await expect(page.locator('.kern-alert--danger')).toBeVisible();
    }
});


test('deselecting all events shows no crash', async ({ page }) => {
    const config = loadEnv();
    await goToMaterialGenerator(page, config);

    await page.locator('[name="fromDate"]').fill('2020-01-01');
    await page.locator('[name="fromDate"]').blur();
    await page.locator('[name="toDate"]').fill('2099-12-31');
    await page.locator('[name="toDate"]').blur();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const pickerButton = page.getByRole('button', { name: /Auswählen/ });
    if (!(await pickerButton.isVisible())) {
        test.skip();
        return;
    }

    await pickerButton.click();

    const deselectBtn = page.getByRole('button', { name: 'Alle abwählen' });
    if (await deselectBtn.isVisible()) {
        await deselectBtn.click();
    } else {
        await page.getByRole('button', { name: 'Alle auswählen' }).click();
        await page.getByRole('button', { name: 'Alle abwählen' }).click();
    }

    await page.waitForTimeout(1000);

    await expect(page.locator('canvas#canvas1')).toBeVisible();
});


test('changing headline text re-renders without error', async ({ page }) => {
    const config = loadEnv();
    await goToMaterialGenerator(page, config);

    const headlineInput = page.locator('[name="headlineText"]');
    await expect(headlineInput).toBeVisible();
    await headlineInput.fill('Neue Überschrift E2E');
    await headlineInput.blur();

    await page.waitForTimeout(1000);

    await expect(page.locator('canvas#canvas1')).toBeVisible();
    await expect(page.locator('.kern-alert--danger')).not.toBeVisible();
});

test('changing font re-renders without error', async ({ page }) => {
    const config = loadEnv();
    await goToMaterialGenerator(page, config);

    const fontSelect = page.locator('[name="selectedFont"]');
    await expect(fontSelect).toBeVisible();

    const currentFont = await fontSelect.inputValue();
    await fontSelect.selectOption(currentFont === 'Arial' ? 'Times New Roman' : 'Arial');

    await page.waitForTimeout(1000);

    await expect(page.locator('canvas#canvas1')).toBeVisible();
    await expect(page.locator('.kern-alert--danger')).not.toBeVisible();
});
