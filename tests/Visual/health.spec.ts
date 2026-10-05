import { expect, test } from '@playwright/test';

/**
 * T009 — proves the harness itself works: the server boots, the browser reaches it, and the
 * locale x width matrix runs. Screens get their own specs as they are built (T101, T264, T486).
 */
test('the application responds', async ({ page }) => {
    const response = await page.goto('/up');
    expect(response?.status()).toBe(200);
});
