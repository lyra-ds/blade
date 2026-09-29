import { expect, test } from '@playwright/test';
import lyra from '@lyra-ds/alpine';
import { components, component, mount } from './fixture.js';

test('every documented binding is registered by the published Alpine plugin', () => {
  const names = new Set();
  lyra({ store() {}, data(name) { names.add(name); } });
  for (const item of components) {
    expect(names.has(item.binding), `${item.slug}: ${item.binding}`).toBe(true);
  }
});

test('tabs: one visible panel and arrow keys change the active tab', async ({ page }) => {
  const errors = await mount(page, component('tabs'));
  await expect(page.getByRole('tablist')).toBeVisible();
  await expect(page.getByRole('tabpanel', { includeHidden: false })).toHaveCount(1);
  await page.getByRole('tab', { name: /issues/i }).focus();
  await page.keyboard.press('ArrowRight');
  await expect(page.getByRole('tab', { name: /settings/i })).toHaveAttribute('aria-selected', 'true');
  expect(errors).toEqual([]);
});

test('file-upload: selecting a file creates an item', async ({ page }) => {
  const errors = await mount(page, component('file-upload'));
  await page.locator('input[type=file]').setInputFiles({ name: 'proof.png', mimeType: 'image/png', buffer: Buffer.from('image') });
  await expect(page.locator('.lyra-upload__item')).toHaveCount(1);
  await expect(page.locator('.lyra-upload__item')).toContainText('proof.png');
  expect(errors).toEqual([]);
});

test('file-manager: open and navigate match React without disrupting view and menu selection', async ({ page }) => {
  const errors = await mount(page, component('file-manager'));
  await page.evaluate(() => {
    window.__fileManagerEvents = [];
    for (const name of ['lyra:open', 'lyra:navigate', 'lyra:view', 'lyra:select']) {
      document.querySelector('.lyra-fm').addEventListener(name, event => {
        window.__fileManagerEvents.push({ name, detail: event.detail });
      });
    }
  });

  await page.locator('.lyra-fm__name').first().click();
  await page.locator('.lyra-fm__crumb').first().focus();
  await page.keyboard.press('Enter');
  await page.getByRole('button', { name: 'Grid view' }).click();
  await page.locator('.lyra-fm__card-body').nth(1).click();
  await page.locator('.lyra-fm__card-actions .lyra-dropdown__trigger').first().click();
  await page.getByRole('menuitem', { name: 'Rename' }).click();

  const events = await page.evaluate(() => window.__fileManagerEvents);
  expect(events).toEqual([
    { name: 'lyra:open', detail: { id: 'brand-assets', name: 'Brand assets', type: 'folder', items: 12, updated: '2026-03-14' } },
    { name: 'lyra:navigate', detail: 0 },
    { name: 'lyra:view', detail: { view: 'grid' } },
    { name: 'lyra:open', detail: { id: 'homepage', name: 'homepage.fig', type: 'file', size: 4823000, updated: '2026-03-16', shared: true } },
    { name: 'lyra:select', detail: { id: 'rename' } },
  ]);
  await expect(page.locator('.lyra-fm__crumb').last()).toBeDisabled();
  expect(errors).toEqual([]);
});

test('dropdown: selection emits a nonempty detail.id', async ({ page }) => {
  const errors = await mount(page, component('dropdown'));
  await page.evaluate(() => {
    window.__selection = null;
    document.addEventListener('lyra:select', event => { window.__selection = event.detail; });
  });
  await page.locator('.lyra-dropdown__trigger').click();
  await page.getByRole('menuitem', { name: 'Rename project' }).click();
  expect(await page.evaluate(() => window.__selection?.id)).toBeTruthy();
  expect(errors).toEqual([]);
});

for (const slug of ['dialog', 'drawer', 'bottom-sheet']) {
  test(`${slug}: opens, closes with Escape, and restores focus`, async ({ page }) => {
    const fixture = component(slug);
    const errors = await mount(page, fixture, '<button id="opener" type="button">Open</button>');
    await page.locator('#opener').click();
    await page.locator('#opener').focus();
    await page.evaluate(() => {
      const root = document.querySelector('main [x-data]');
      window.Alpine.$data(root).open = true;
    });
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.getByRole('dialog').focus();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('dialog')).toBeHidden();
    await expect(page.locator('#opener')).toBeFocused();
    expect(errors).toEqual([]);
  });
}

test('toast-stack: rendered toasts expose a live region', async ({ page }) => {
  const errors = await mount(page, component('toast-stack'));
  await page.evaluate(() => window.Alpine.store('lyraToasts').toast('Saved', { tone: 'success' }));
  await expect(page.locator('[aria-live="polite"]')).toContainText('Saved');
  await expect(page.locator('[aria-live="assertive"]')).toBeAttached();
  expect(errors).toEqual([]);
});

test('otp-input: typing digits fills the cells and the hidden field', async ({ page }) => {
  const errors = await mount(page, component('otp-input'));
  await expect(page.locator('.lyra-otp__digit')).toHaveCount(6);
  await page.locator('.lyra-otp__digit').first().focus();
  await page.keyboard.type('123456');
  await expect(page.locator('input[type=hidden][name=verification_code]')).toHaveValue('123456');
  expect(errors).toEqual([]);
});
