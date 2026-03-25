## Nextphp Skeleton

### Vite (frontend assets)

- Dev server:

```bash
npm install
npm run dev
```

- Production build:

```bash
npm run build
```

The app will load assets from Vite dev server when `public/build/manifest.json` is missing,
and from `public/build/` after `npm run build`.

### FrankenPHP (optional)

If you want to run the app with FrankenPHP + Caddy:

- Use the provided `Caddyfile`
- Run FrankenPHP image/container mapping the project root and exposing port `8080`

Example (Docker):

```bash
docker run --rm -p 8080:8080 -v "$PWD":/app -w /app dunglas/frankenphp
```

Then open `http://localhost:8080`.

