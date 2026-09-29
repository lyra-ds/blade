import { expect, test } from '@playwright/test';
import { component, mount } from './fixture.js';

test('calendar-view: localized controls, positioned events, availability, slot and popover', async ({ page }) => {
  const errors = await mount(page, component('calendar-view'));
  await page.evaluate(() => {
    window.__calendarActions = [];
    for (const name of ['lyra:view-change', 'lyra:change', 'lyra:event-open', 'lyra:slot-create']) {
      document.querySelector('.lyra-calview').addEventListener(name, event => {
        window.__calendarActions.push({ name, detail: event.detail instanceof Date
          ? `${event.detail.getFullYear()}-${String(event.detail.getMonth() + 1).padStart(2, '0')}-${String(event.detail.getDate()).padStart(2, '0')}T${String(event.detail.getHours()).padStart(2, '0')}:${String(event.detail.getMinutes()).padStart(2, '0')}`
          : event.detail });
      });
    }
  });

  await expect(page.getByRole('group', { name: 'Visualização do calendário' })).toBeVisible();
  await expect(page.getByLabel('Horas do calendário')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Semana' })).toHaveAttribute('aria-pressed', 'true');
  const chip = page.locator('.lyra-calview__evt');
  await expect(chip).toHaveCount(1);
  await expect(chip).toContainText('Consultation');
  await expect(chip).toHaveCSS('top', '145px');
  await expect(page.locator('.lyra-calview__avail')).toHaveCount(1);
  await expect(page.locator('.lyra-calview__avail')).toHaveCSS('top', '48px');
  await chip.click();
  await expect(page.getByRole('dialog', { name: 'Consultation' })).toBeVisible();
  await expect(page.getByRole('dialog')).toContainText('Consultation');
  await page.keyboard.press('Escape');
  await expect(page.getByRole('dialog')).toBeHidden();

  await page.locator('.lyra-calview__col').nth(2).click({ position: { x: 20, y: 72 } });
  await page.getByRole('button', { name: 'Mês' }).click();
  await expect(page.locator('.lyra-calview__mcell')).toHaveCount(42);
  await page.getByRole('button', { name: 'Dia' }).click();
  await expect(page.locator('.lyra-calview__col')).toHaveCount(1);
  await page.getByRole('button', { name: 'Próximo período' }).click();
  await page.getByRole('button', { name: 'Hoje' }).click();

  const actions = await page.evaluate(() => window.__calendarActions);
  expect(actions.map(action => action.name)).toEqual([
    'lyra:event-open', 'lyra:slot-create', 'lyra:view-change', 'lyra:view-change', 'lyra:change', 'lyra:change',
  ]);
  expect(actions[0].detail.title).toBe('Consultation');
  expect(actions[1].detail).toBe('2026-08-12T08:30');
  expect(actions[2].detail).toBe('month');
  expect(actions[3].detail).toBe('day');
  expect(actions[4].detail).toBe('2026-08-13');
  expect(errors).toEqual([]);
});
