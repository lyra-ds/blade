import { expect, test } from '@playwright/test';
import { components, mount } from './fixture.js';

// Shared by the per-component tests and by the negative guard in hydration-guard.spec.js.
export async function expectHydrated(page, item) {
  const root = page.locator(`main [x-data^="${item.binding}("]`).first();
  await expect(root).toHaveAttribute('x-data', new RegExp(`^${item.binding}\\(`));
  // Alpine strips x-cloak during init; a bare attribute (even x-cloak="") means it never ran.
  await expect(root).not.toHaveAttribute('x-cloak');
  // _x_dataStack is only set once Alpine initialised the element; Alpine.$data() is truthy either way.
  expect(await root.evaluate(element => Array.isArray(element._x_dataStack) && element._x_dataStack.length > 0)).toBe(true);
  // Enhancement signal: the component factory produced real state/methods on the element's own data scope.
  expect(await root.evaluate(element => Reflect.ownKeys(element._x_dataStack[0]).length > 0)).toBe(true);
}

export function registerHydrationTests(shard) {
  for (const [index, item] of components.entries()) {
    if (index % 3 !== shard) continue;
    test(`${item.slug}: emitted HTML hydrates without errors or fallback`, async ({ page }) => {
      test.fixme(item.slug === 'calendar-view' && !process.env.CALENDAR_VIEW_LOCAL_BUILD, 'requires @lyra-ds/alpine 1.2.0');
      const errors = await mount(page, item);
      await expectHydrated(page, item);
      if (item.slug === 'tabs') await expect(page.getByRole('tablist')).toBeVisible();
      expect(errors).toEqual([]);
    });
  }
}
