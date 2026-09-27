import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { mount } from './fixture.js';

const examples = JSON.parse(readFileSync(new URL('../docs/api.json', import.meta.url), 'utf8')).components;

for (const [slug, buttonClass, eventName] of [
  ['tag', '.lyra-tag__remove', 'lyra:remove'],
  ['toast', '.lyra-toast__close', 'lyra:close'],
  ['action-bar', '.lyra-actionbar__clear', 'lyra:clear'],
]) {
  test(`${slug}: click, Enter and Space emit one bubbling action event each`, async ({ page }) => {
    const fixture = examples.find(item => item.slug === slug);
    expect(fixture).toBeDefined();
    const errors = await mount(page, { ...fixture, html: `<div x-data="{}">${fixture.html}</div>` });
    const button = page.locator(`main ${buttonClass}`);
    await expect(button).toHaveCount(1);
    await page.evaluate(eventName => {
      window.__actions = [];
      document.addEventListener(eventName, event => window.__actions.push(event.detail));
    }, eventName);

    await button.click();
    await button.focus();
    await page.keyboard.press('Enter');
    await page.keyboard.press('Space');

    expect(await page.evaluate(() => window.__actions)).toEqual([{}, {}, {}]);
    expect(errors).toEqual([]);
  });
}
