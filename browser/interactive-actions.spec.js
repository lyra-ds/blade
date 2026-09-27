import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { mount } from './fixture.js';

const examples = JSON.parse(readFileSync(new URL('../docs/api.json', import.meta.url), 'utf8')).components;

for (const [slug, buttonClass, eventName, expectedId] of [
  ['tag', '.lyra-tag__remove', 'lyra:remove', 'design-system'],
  ['toast', '.lyra-toast__close', 'lyra:close', 'settings-saved'],
  ['action-bar', '.lyra-actionbar__clear', 'lyra:clear', 'file-selection'],
]) {
  test(`${slug}: click, Enter and Space emit one bubbling action event each, without a wrapper`, async ({ page }) => {
    const fixture = examples.find(item => item.slug === slug);
    expect(fixture).toBeDefined();
    const errors = await mount(page, fixture);
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

    expect(await page.evaluate(() => window.__actions)).toEqual([
      { id: expectedId },
      { id: expectedId },
      { id: expectedId },
    ]);
    expect(errors).toEqual([]);
  });
}

for (const [slug, rootClass, buttonClass, eventName] of [
  ['tag', '.lyra-tag', '.lyra-tag__remove', 'lyra:remove'],
  ['toast', '.lyra-toast', '.lyra-toast__close', 'lyra:close'],
  ['action-bar', '.lyra-actionbar', '.lyra-actionbar__clear', 'lyra:clear'],
]) {
  test(`${slug}: preserves a consumer x-data scope instead of adding an empty one`, async ({ page }) => {
    const fixture = examples.find(item => item.slug === slug);
    expect(fixture).toBeDefined();
    const html = fixture.html.replace(/\bx-data\b(?!=)/, "x-data=\"{ marker: 'consumer' }\"");
    expect(html).not.toBe(fixture.html);

    const errors = await mount(page, { ...fixture, html });
    const button = page.locator(`main ${buttonClass}`);
    await expect(button).toHaveCount(1);
    await page.evaluate(eventName => {
      window.__actions = [];
      document.addEventListener(eventName, event => window.__actions.push(event.detail));
    }, eventName);

    await button.click();

    expect(await page.evaluate(() => window.__actions)).toHaveLength(1);

    const marker = await page.evaluate(rootClass => {
      const el = document.querySelector(`main ${rootClass}`);
      return window.Alpine.$data(el).marker;
    }, rootClass);

    expect(marker).toBe('consumer');
    expect(errors).toEqual([]);
  });
}
