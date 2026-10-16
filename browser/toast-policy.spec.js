import { expect, test } from '@playwright/test';
import { component, mount } from './fixture.js';

const fixture = component('toast-stack');
const row = (page, message) => page.locator('.lyra-toast').filter({ hasText: message });

async function setup(page) {
  // Alpine.nextTick uses a timer; finish mounting before freezing time.
  const errors = await mount(page, fixture, '<button id="opener">Notify</button><button id="elsewhere">Elsewhere</button>');
  await page.clock.install();
  await page.clock.pauseAt(new Date());
  return errors;
}

async function queue(page, message, options = {}, method = 'toast') {
  await page.evaluate(({ message, options, method }) => window.Alpine.store('lyraToasts')[method](message, options), { message, options, method });
  await expect(row(page, message)).toHaveCount(1);
}

async function advance(page, milliseconds) {
  await page.clock.runFor(milliseconds);
}

test('toast policy delta: implicit info/success last 8000ms and danger/error persist', async ({ page }) => {
  const errors = await setup(page);
  await queue(page, 'Information');
  await queue(page, 'Success', {}, 'success');
  await queue(page, 'Danger', { tone: 'danger' });
  await queue(page, 'Error', {}, 'error');
  await advance(page, 7999);
  await expect(page.locator('.lyra-toast')).toHaveCount(4);
  await advance(page, 1);
  await expect(row(page, 'Information')).toHaveCount(0);
  await expect(row(page, 'Success')).toHaveCount(0);
  await advance(page, 60000);
  await expect(page.locator('[aria-live="assertive"] .lyra-toast')).toHaveCount(2);
  expect(errors).toEqual([]);
});

for (const duration of [4000, 8000, 0]) {
  test(`explicit global duration ${duration} applies to every tone`, async ({ page }) => {
    const errors = await setup(page);
    await page.evaluate(duration => { window.Alpine.store('lyraToasts').duration = duration; }, duration);
    await queue(page, 'Global info');
    await queue(page, 'Global success', {}, 'success');
    await queue(page, 'Global danger', { tone: 'danger' });
    await queue(page, 'Global error', {}, 'error');
    await advance(page, duration ? duration - 1 : 60000);
    await expect(page.locator('.lyra-toast')).toHaveCount(4);
    if (duration) {
      await advance(page, 1);
      await expect(page.locator('.lyra-toast')).toHaveCount(0);
    }
    expect(errors).toEqual([]);
  });
}

test('per-toast duration wins including zero; later global assignments affect only new notifications', async ({ page }) => {
  const errors = await setup(page);
  await queue(page, 'Original implicit');
  await page.evaluate(() => { window.Alpine.store('lyraToasts').duration = 4000; });
  await queue(page, 'Original global');
  await queue(page, 'Persistent override', { duration: 0 });
  await page.evaluate(() => { window.Alpine.store('lyraToasts').duration = 0; });
  await queue(page, 'New persistent', {}, 'error');
  await queue(page, 'Finite override', { duration: 1000, tone: 'danger' });
  await advance(page, 999);
  await expect(page.locator('.lyra-toast')).toHaveCount(5);
  await advance(page, 1);
  await expect(row(page, 'Finite override')).toHaveCount(0);
  await advance(page, 2999);
  await expect(row(page, 'Original global')).toHaveCount(1);
  await advance(page, 1);
  await expect(row(page, 'Original global')).toHaveCount(0);
  await advance(page, 3999);
  await expect(row(page, 'Original implicit')).toHaveCount(1);
  await advance(page, 1);
  await expect(row(page, 'Original implicit')).toHaveCount(0);
  await advance(page, 60000);
  await expect(page.locator('.lyra-toast')).toHaveCount(2);
  expect(errors).toEqual([]);
});

for (const first of ['pointer', 'focus']) {
  test(`overlapping pauses: ${first} enters first, both claims must leave before remaining time resumes`, async ({ page }) => {
    const errors = await setup(page);
    await queue(page, 'Paused', { duration: 4000 });
    await advance(page, 1000);
    const close = row(page, 'Paused').getByRole('button');
    if (first === 'pointer') {
      await close.hover();
      await close.focus();
    } else {
      await close.focus();
      await close.hover();
    }
    await advance(page, 10000);
    await expect(row(page, 'Paused')).toHaveCount(1);
    if (first === 'pointer') await page.mouse.move(0, 0);
    else await page.locator('#elsewhere').focus();
    await advance(page, 10000);
    await expect(row(page, 'Paused')).toHaveCount(1);
    if (first === 'pointer') await page.locator('#elsewhere').focus();
    else await page.mouse.move(0, 0);
    await advance(page, 2999);
    await expect(row(page, 'Paused')).toHaveCount(1);
    await advance(page, 1);
    await expect(row(page, 'Paused')).toHaveCount(0);
    expect(errors).toEqual([]);
  });
}

test('focused dismissal restores an eligible opener', async ({ page }) => {
  const errors = await setup(page);
  await page.locator('#opener').focus();
  await queue(page, 'Return focus', { duration: 0 });
  await row(page, 'Return focus').getByRole('button').focus();
  await page.keyboard.press('Enter');
  await expect(row(page, 'Return focus')).toHaveCount(0);
  await expect(page.locator('#opener')).toBeFocused();
  expect(errors).toEqual([]);
});

for (const reason of ['removed', 'disabled', 'hidden']) {
  test(`last-row focused dismissal falls back to the named stack when opener is ${reason}`, async ({ page }) => {
    const errors = await setup(page);
    await page.locator('#opener').focus();
    await queue(page, 'Fallback', { duration: 0 });
    await page.locator('#opener').evaluate((element, reason) => {
      if (reason === 'removed') element.remove();
      if (reason === 'disabled') element.disabled = true;
      if (reason === 'hidden') element.hidden = true;
    }, reason);
    await row(page, 'Fallback').getByRole('button').focus();
    await page.keyboard.press('Enter');
    await expect(page.locator('.lyra-toast')).toHaveCount(0);
    await expect(page.getByRole('region', { name: 'Notifications', exact: true })).toBeFocused();
    expect(errors).toEqual([]);
  });
}

test('unfocused dismissal retains current focus', async ({ page }) => {
  const errors = await setup(page);
  await page.locator('#opener').focus();
  await queue(page, 'Unfocused', { duration: 1000 });
  await page.locator('#elsewhere').focus();
  await advance(page, 1000);
  await expect(row(page, 'Unfocused')).toHaveCount(0);
  await expect(page.locator('#elsewhere')).toBeFocused();
  expect(errors).toEqual([]);
});

test('window event creates a plain-text danger toast with its explicit duration', async ({ page }) => {
  const errors = await setup(page);
  const message = '<strong>Event text</strong>';
  await page.evaluate(message => window.dispatchEvent(new CustomEvent('lyra:toast', {
    detail: { message, tone: 'danger', duration: 1200 },
  })), message);
  const toast = page.locator('[aria-live="assertive"] .lyra-toast');
  await expect(toast).toHaveText(new RegExp(message));
  await expect(toast.locator('strong')).toHaveCount(0);
  await expect(toast.locator('.lyra-toast__icon--danger')).toHaveCount(1);
  await advance(page, 1199);
  await expect(toast).toHaveCount(1);
  await advance(page, 1);
  await expect(toast).toHaveCount(0);
  expect(errors).toEqual([]);
});

test('teardown releases pause claims; remount preserves queue and remaining deadlines', async ({ page }) => {
  const errors = await setup(page);
  await queue(page, 'Paused across mount', { duration: 4000 });
  await queue(page, 'Running deadline', { duration: 6000 });
  await queue(page, 'Persistent across mount', { duration: 0 });
  await advance(page, 1000);
  const close = row(page, 'Paused across mount').getByRole('button');
  await close.hover();
  await close.focus();
  await advance(page, 1000);
  await page.evaluate(() => {
    document.querySelector('.lyra-toast-stack').remove();
  });
  await expect(page.locator('.lyra-toast-stack')).toHaveCount(0);
  await page.mouse.move(0, 0);
  await advance(page, 1000);
  await page.evaluate(html => {
    document.querySelector('main').innerHTML = html;
  }, fixture.html);
  await expect(page.locator('.lyra-toast')).toHaveCount(3);
  await advance(page, 1999);
  await expect(row(page, 'Paused across mount')).toHaveCount(1);
  await advance(page, 1);
  await expect(row(page, 'Paused across mount')).toHaveCount(0);
  await advance(page, 999);
  await expect(row(page, 'Running deadline')).toHaveCount(1);
  await advance(page, 1);
  await expect(row(page, 'Running deadline')).toHaveCount(0);
  await advance(page, 60000);
  await expect(row(page, 'Persistent across mount')).toHaveCount(1);
  expect(errors).toEqual([]);
});
