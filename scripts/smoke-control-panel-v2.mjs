#!/usr/bin/env node

import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const BASE = process.env.SMOKE_BASE_URL ?? 'https://rrmanagementlatest.test';
const EMAIL = process.env.SMOKE_EMAIL ?? 'superadmin@rmms.local';
const PASSWORD = process.env.SMOKE_PASSWORD ?? 'password';
const SHOT_DIR = resolve('/tmp/control-panel-v2-smoke');

const errors = [];
const log = (msg) => console.log(`[smoke] ${msg}`);

async function run() {
    await mkdir(SHOT_DIR, { recursive: true });
    const browser = await chromium.launch({ headless: true });
    const ctx = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1600, height: 1000 },
    });
    const page = await ctx.newPage();
    page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
    const benignPatterns = [
        /WebSocket connection.*localhost:8080/i,
        /ERR_SSL_PROTOCOL_ERROR/i,
        /pusher.*disconnected/i,
    ];
    const isBenign = (text) => benignPatterns.some((re) => re.test(text));

    page.on('console', (msg) => {
        if (msg.type() !== 'error') return;
        const text = msg.text();
        if (isBenign(text)) return;
        errors.push(`console.error: ${text}`);
    });
    page.on('requestfailed', (req) => {
        const f = req.failure();
        if (!f) return;
        if (isBenign(`${req.url()} ${f.errorText}`)) return;
        errors.push(`requestfailed: ${req.url()} -> ${f.errorText}`);
    });

    log(`base = ${BASE}`);

    log('GET /login');
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle' }),
        page.click('button[type="submit"]'),
    ]);
    log(`logged in, url = ${page.url()}`);

    log('GET /control-panel-2');
    const overviewResp = await page.goto(`${BASE}/control-panel-2`, {
        waitUntil: 'networkidle',
    });
    log(`status = ${overviewResp?.status()}, url after = ${page.url()}`);
    await page.screenshot({
        path: `${SHOT_DIR}/01-overview.png`,
        fullPage: true,
    });
    const title = await page.title();
    const h1 = await page.locator('h1').first().textContent().catch(() => null);
    log(`title = "${title}", h1 = "${h1}"`);
    try {
        await page.waitForSelector('text=All Sidings Overview', {
            timeout: 5000,
        });
        log('overview ok');
    } catch {
        log('overview heading not found — see screenshot');
    }

    // The View Details button uses router.visit, not <a>. Pick a known siding.
    const sidingId = Number(process.env.SMOKE_SIDING_ID ?? 2);
    log(`smoke siding id = ${sidingId}`);

    if (sidingId) {
        log(`GET /control-panel-2/${sidingId}`);
        await page.goto(`${BASE}/control-panel-2/${sidingId}`, {
            waitUntil: 'networkidle',
        });
        await page.waitForSelector('text=Wagon Position', { timeout: 10000 });
        await page.screenshot({
            path: `${SHOT_DIR}/02-siding.png`,
            fullPage: true,
        });
        log('siding ok');

        // Try clicking Load Replay if present.
        const replayButton = await page
            .locator('button:has-text("Load Replay")')
            .first();
        if (await replayButton.isVisible()) {
            log('clicking Load Replay');
            await replayButton.click();
            await page
                .waitForSelector('button[aria-label="Pause"], button[aria-label="Play"]', {
                    timeout: 10000,
                })
                .catch(() => log('replay controls did not appear (no events?)'));
            await page.waitForTimeout(800);
            await page.screenshot({
                path: `${SHOT_DIR}/03-replay-loaded.png`,
                fullPage: true,
            });
            const play = page.locator('button[aria-label="Play"]').first();
            if (await play.isVisible().catch(() => false)) {
                await play.click();
                await page.waitForTimeout(2500);
                await page.screenshot({
                    path: `${SHOT_DIR}/04-replay-playing.png`,
                    fullPage: true,
                });
                log('replay played 2.5s');
            }
        }
    }

    await browser.close();

    await writeFile(
        `${SHOT_DIR}/report.json`,
        JSON.stringify({ errors, sidingId, base: BASE }, null, 2),
    );

    if (errors.length > 0) {
        log(`FAILURES (${errors.length}):`);
        errors.forEach((e) => console.error(`  - ${e}`));
        process.exit(1);
    }
    log('smoke ok');
}

run().catch((e) => {
    console.error('smoke FAILED:', e);
    process.exit(1);
});
