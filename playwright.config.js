import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './browser',
  testMatch: '*.spec.js',
  timeout: 15_000,
  expect: { timeout: 3_000 },
  workers: 4,
  reporter: 'list',
  projects: ['chromium', 'firefox', 'webkit'].map(name => ({
    name,
    use: {
      browserName: name,
      ...(process.env[`${name.toUpperCase()}_EXECUTABLE`] ? { launchOptions: { executablePath: process.env[`${name.toUpperCase()}_EXECUTABLE`] } } : {}),
    },
  })),
});
