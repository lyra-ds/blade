import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { component, mount } from './fixture.js';

const slugs = ['tabs', 'toast-stack', 'accordion', 'sidebar-group', 'app-sidebar', 'bottom-sheet'];

for (const slug of slugs) {
  test(`${slug}: inherits wrapper state and keeps its own interaction`, async ({ page }) => {
    const fixture = component(slug);
    const wrapped = {
      ...fixture,
      html: `<div id="consumer" x-data="{ n: 0 }"><button id="increment" type="button" @click="n++">Increase</button>${fixture.html.replace('<div x-data="{ n: 0 }">', '<div>')}</div>`,
    };
    const errors = await mount(page, wrapped);
    const root = page.locator('main [x-data^="lyra"]').first();
    await expect(root).toHaveAttribute('x-data', /^lyra/);
    await root.evaluate(element => {
      const probe = document.createElement('span');
      probe.id = 'scope-probe';
      probe.setAttribute('x-text', 'n');
      element.appendChild(probe);
      window.Alpine.initTree(probe);
    });
    await expect(page.locator('#scope-probe')).toHaveText('0');
    await page.locator('#increment').click();
    await expect(page.locator('#scope-probe')).toHaveText('1');

    switch (slug) {
      case 'tabs':
        await page.getByRole('tab', { name: /Settings/ }).click();
        await expect(page.getByRole('tab', { name: /Settings/ })).toHaveAttribute('aria-selected', 'true');
        break;
      case 'toast-stack':
        await page.evaluate(() => window.Alpine.store('lyraToasts').toast('Saved', { tone: 'success' }));
        await expect(page.locator('[data-lyra-toast-region="polite"]')).toContainText('Saved');
        break;
      case 'accordion':
        await page.getByRole('button', { name: 'Security' }).click();
        await expect(page.getByRole('button', { name: 'Security' })).toHaveAttribute('aria-expanded', 'true');
        break;
      case 'sidebar-group':
        await page.locator('.lyra-sbgroup__label--btn').click();
        await expect(page.locator('.lyra-sbgroup__label--btn')).toHaveAttribute('aria-expanded', 'false');
        break;
      case 'app-sidebar':
        await page.locator('.lyra-appsidebar__toggle').click();
        await expect(page.locator('.lyra-appsidebar__toggle')).toHaveAttribute('aria-label', 'Expand sidebar');
        break;
      case 'bottom-sheet':
        await root.evaluate(element => { window.Alpine.$data(element).open = true; });
        await expect(page.getByRole('dialog')).toBeVisible();
        await page.getByRole('dialog').focus();
        await page.keyboard.press('Escape');
        await expect(page.getByRole('dialog')).toBeHidden();
        break;
    }
    expect(errors).toEqual([]);
  });
}

test('local: a consumer x-data error is visible', async ({ page }) => {
  const html = execFileSync('php', ['browser/dev-exception.php'], { encoding: 'utf8' });
  await page.setContent(html);
  await expect(page.getByRole('alert')).toContainText('<lyra:tabs> owns x-data');
  await expect(page.getByRole('alert')).toContainText('wrap it in a parent element');
});
