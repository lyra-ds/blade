import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';

test('local: a standalone toast after a failed stack slot keeps role="status"', async ({ page }) => {
  const html = execFileSync('php', ['browser/toast-stack-scope.php'], { encoding: 'utf8' });
  await page.setContent(html);
  await expect(page.getByRole('status')).toContainText('Saved after a failed stack');
});
