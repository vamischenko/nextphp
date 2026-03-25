<?php

declare(strict_types=1);

use Nextphp\Routing\Router;

return static function (Router $router): void {
    $router->get('/', static function (): string {
        $devServer = 'http://localhost:5173';
        $useDev = false;

        $manifestPath = __DIR__ . '/../public/build/manifest.json';
        if (is_file($manifestPath)) {
            $manifestRaw = file_get_contents($manifestPath);
            $manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;
            $entry = is_array($manifest) ? ($manifest['resources/assets/app.js'] ?? null) : null;

            if (is_array($entry) && isset($entry['file']) && is_string($entry['file'])) {
                $js = '/build/' . ltrim($entry['file'], '/');
                $cssTags = '';

                $css = $entry['css'] ?? [];
                if (is_array($css)) {
                    foreach ($css as $cssFile) {
                        if (is_string($cssFile)) {
                            $cssTags .= '<link rel="stylesheet" href="/build/' . htmlspecialchars(ltrim($cssFile, '/')) . '">' . "\n";
                        }
                    }
                }

                return <<<HTML
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nextphp Skeleton</title>
  {$cssTags}
</head>
<body>
  <h1>Nextphp Skeleton</h1>
  <p>Vite assets loaded from <code>public/build</code>.</p>
  <script type="module" src="{$js}"></script>
</body>
</html>
HTML;
            }
        } else {
            // Dev mode (vite dev server)
            $useDev = true;
        }

        $devScript = $useDev
            ? '<script type="module" src="' . $devServer . '/@vite/client"></script>' . "\n"
              . '<script type="module" src="' . $devServer . '/resources/assets/app.js"></script>'
            : '';

        return <<<HTML
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nextphp Skeleton</title>
</head>
<body>
  <h1>Nextphp Skeleton</h1>
  <p>Запусти <code>npm install</code> и <code>npm run dev</code> (Vite dev server) или <code>npm run build</code>.</p>
  {$devScript}
</body>
</html>
HTML;
    });
};
