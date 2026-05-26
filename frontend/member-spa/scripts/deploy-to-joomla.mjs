import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '..');

const browserDist = path.join(projectRoot, 'dist', 'member-spa', 'browser');
const flatDist = path.join(projectRoot, 'dist', 'member-spa');
const distPath = existsSync(browserDist) ? browserDist : flatDist;

if (!existsSync(distPath)) {
  throw new Error(`Build output not found at ${distPath}. Run "npm run build:prod" first.`);
}

const defaultTarget = path.resolve(projectRoot, '..', '..', 'components', 'com_balancirk', 'media', 'member-spa', 'browser');
const targetPath = process.env.JOOMLA_MEDIA_TARGET ? path.resolve(process.env.JOOMLA_MEDIA_TARGET) : defaultTarget;

rmSync(targetPath, { recursive: true, force: true });
mkdirSync(targetPath, { recursive: true });
cpSync(distPath, targetPath, { recursive: true });

console.log(`Angular member SPA deployed to: ${targetPath}`);
