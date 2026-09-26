import { createRequire } from 'node:module';
import { readFileSync } from 'node:fs';

const require = createRequire(import.meta.url);
export const components = require('../docs/api.json').components.filter(item => item.binding);
const runtime = new URL('./.generated/runtime.js', import.meta.url).pathname;
const styles = readFileSync(require.resolve('@lyra-ds/styles/styles.css'), 'utf8');

export function component(slug) {
  const fixture = components.find(item => item.slug === slug);
  if (!fixture) throw new Error(`Missing docs/api.json fixture: ${slug}`);
  return fixture;
}

export async function mount(page, fixture, prefix = '', { start = true } = {}) {
  const errors = [];
  page.on('console', message => {
    if (message.type() === 'error') errors.push(message.text());
  });
  page.on('pageerror', error => errors.push(error.message));
  await page.setContent(`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}</style></head><body>${prefix}<main>${fixture.html}</main></body></html>`);
  if (!start) await page.evaluate(() => { window.__lyraSkipStart = true; });
  await page.addScriptTag({ path: runtime });
  await page.waitForFunction(() => window.__lyraReady === true);
  await page.evaluate(() => window.Alpine.nextTick());
  return errors;
}
