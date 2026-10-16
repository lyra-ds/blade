import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { mount } from './fixture.js';

// Render the real component: the docs example has no disabled items.
const html = execFileSync('php', ['-r', String.raw`
require getcwd().'/vendor/autoload.php';
Orchestra\Testbench\Foundation\Application::create(
    basePath: getcwd().'/vendor/orchestra/testbench-core/laravel',
    options: ['extra' => ['providers' => [LyraDs\Blade\BladeServiceProvider::class]]],
);
echo Illuminate\Support\Facades\Blade::render(
    '<form id="actions"><lyra:dropdown :items="$items"><x-slot:trigger>Actions</x-slot:trigger></lyra:dropdown></form>',
    ['items' => [
        ['label' => 'Enabled', 'id' => 'enabled'],
        ['label' => 'Disabled button', 'id' => 'disabled-button', 'disabled' => true],
        ['label' => 'Disabled link', 'id' => 'disabled-link', 'href' => '#forbidden', 'disabled' => true],
    ]],
);
`], { encoding: 'utf8' });

for (const name of ['Disabled button', 'Disabled link']) {
  for (const activation of ['click', 'Enter', 'Space']) {
    test(`${name}: keyboard discoverable but ${activation} cannot activate`, async ({ page }) => {
      const errors = await mount(page, { html });
      await page.evaluate(() => {
        window.__selections = [];
        window.__submissions = 0;
        document.addEventListener('lyra:select', event => window.__selections.push(event.detail));
        document.querySelector('form').addEventListener('submit', event => {
          event.preventDefault();
          window.__submissions++;
        });
      });
      const url = page.url();
      await page.getByRole('button', { name: 'Actions', exact: true }).press('ArrowDown');
      const enabled = page.getByRole('menuitem', { name: 'Enabled', exact: true });
      await expect(enabled).toBeFocused();
      await page.keyboard.press('ArrowDown');
      await expect(page.getByRole('menuitem', { name: 'Disabled button', exact: true })).toBeFocused();
      if (name === 'Disabled link') await page.keyboard.press('ArrowDown');
      const disabled = page.getByRole('menuitem', { name, exact: true });
      await expect(disabled).toBeFocused();
      await expect(disabled).toHaveAttribute('aria-disabled', 'true');
      expect(await disabled.getAttribute('disabled')).toBeNull();
      if (name === 'Disabled link') expect(await disabled.getAttribute('href')).toBeNull();
      // Playwright's click action intentionally refuses aria-disabled controls.
      if (activation === 'click') {
        const box = await disabled.boundingBox();
        await page.mouse.click(box.x + box.width / 2, box.y + box.height / 2);
      } else await page.keyboard.press(activation);
      await expect(page.getByRole('menu')).toBeVisible();
      // A native button's pointer activation can blur it in WebKit.
      if (activation !== 'click') await expect(disabled).toBeFocused();
      expect(page.url()).toBe(url);
      expect(await page.evaluate(() => ({ selections: window.__selections, submissions: window.__submissions })))
        .toEqual({ selections: [], submissions: 0 });
      if (activation === 'click') await disabled.focus();
      await page.keyboard.press('Home');
      await expect(enabled).toBeFocused();
      await page.keyboard.press('Enter');
      await expect(page.getByRole('menu')).toBeHidden();
      expect(await page.evaluate(() => window.__selections)).toEqual([{ id: 'enabled' }]);
      expect(errors).toEqual([]);
    });
  }
}
