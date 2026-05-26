import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const appRoot = path.resolve(__dirname, '../../public/app');
const browserDir = path.join(appRoot, 'browser');

if (!fs.existsSync(browserDir)) {
  console.error('No se encontró public/app/browser. Ejecuta ng build primero.');
  process.exit(1);
}

for (const name of fs.readdirSync(browserDir)) {
  const src = path.join(browserDir, name);
  const dest = path.join(appRoot, name);
  fs.rmSync(dest, { recursive: true, force: true });
  fs.cpSync(src, dest, { recursive: true });
}

const htaccess = `<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /app/
  RewriteRule ^index\\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /app/index.html [L]
</IfModule>
`;
fs.writeFileSync(path.join(appRoot, '.htaccess'), htaccess);
console.log('Build Angular copiado a public/app/');
