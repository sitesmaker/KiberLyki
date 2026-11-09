import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from "@tailwindcss/vite"

// https://vite.dev/config/
export default defineConfig(({ mode, command }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const backend = env.DEV_API_URL || env.VITE_API_URL || 'http://localhost:8000';
  return {
    base: command === 'build' ? '/site/' : '/',
    build: { outDir: '../server/public/site', emptyOutDir: true },
    server: { proxy: Object.fromEntries(['/api', '/storage', '/uploads', '/admin', '/site', '/club-theme.css', '/robots.txt', '/sitemap.xml'].map(path => [path, { target: backend, changeOrigin: false }])) },
    plugins: [react(), tailwindcss(), {
      name: 'club-seo',
      apply: 'serve',
      configureServer(server) {
        server.middlewares.use(async (request, response, next) => {
          const url = new URL(request.url || '/', 'http://localhost');
          if (!['GET', 'HEAD'].includes(request.method || '') || !request.headers.accept?.includes('text/html') || !/^\/(?:articles(?:\/[^/]+)?|tournaments(?:\/[^/]+)?)?\/?$/.test(url.pathname)) return next();
          try {
            const page = await fetch(`${backend}${url.pathname}${url.search}`, { headers: { Host: request.headers.host || new URL(backend).host, Accept: 'text/html' }, signal: AbortSignal.timeout(10_000) });
            let html = await page.text();
            const publicOrigin = `http://${request.headers.host || 'localhost:5173'}`;
            const backendOrigin = new URL(backend).origin;
            html = html.split(backendOrigin).join(publicOrigin).split(backendOrigin.replaceAll('/', '\\/')).join(publicOrigin.replaceAll('/', '\\/'));
            html = html.replace(/<script\b[^>]*src="\/site\/[^" ]+"[^>]*><\/script>/g, '').replace(/<link\b[^>]*href="\/site\/assets\/[^" ]+"[^>]*>/g, '');
            html = html.replace('</head>', '<link rel="stylesheet" href="/src/server-page-styles.css?direct"><script type="module" src="/src/main.tsx"></script></head>');
            response.statusCode = page.status;
            response.setHeader('Content-Type', 'text/html; charset=utf-8');
            response.end(await server.transformIndexHtml(request.url || '/', html));
          } catch {
            response.statusCode = 503;
            response.end('Laravel недоступен. Запустите сервер на localhost:8000.');
          }
        });
      },
      async transformIndexHtml(html, context) {
        if (html.includes('data-server-page')) return html;
        const path = new URL(context.originalUrl || context.path, 'http://localhost').pathname;
        const frontend = context.server?.resolvedUrls?.local[0];
        try {
          const response = await fetch(`${backend}/api/seo?${new URLSearchParams({ path })}`, { signal: AbortSignal.timeout(5000) });
          if (!response.ok) throw new Error('SEO unavailable');
          const payload = await response.json() as { head: string };
          // During development Vite is the public origin and proxies Laravel's relative URLs.
          const head = frontend ? payload.head.split(new URL(backend).origin).join(new URL(frontend).origin) : payload.head;
          return html.replace(/<title>.*?<\/title>|<meta\s+name="description"[^>]*>/gis, '').replace('</head>', `${head}</head>`);
        } catch {
          return html.replace('</head>', '<meta name="robots" content="noindex, follow"></head>');
        }
      },
    }],
  };
})
