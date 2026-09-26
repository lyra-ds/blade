import { expect, test } from '@playwright/test';
import { components, mount } from './fixture.js';
import { expectHydrated } from './hydration.js';

// Negative proof: without Alpine.start() the hydration assertion must fail for every component.
for (const item of components) {
  test(`guard: ${item.slug} is reported as NOT hydrated when Alpine.start() never runs`, async ({ page }) => {
    await mount(page, item, '', { start: false });
    await expect(expectHydrated(page, item)).rejects.toThrow();
  });
}
