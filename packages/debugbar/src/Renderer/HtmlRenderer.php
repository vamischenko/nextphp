<?php

declare(strict_types=1);

namespace Nextphp\Debugbar\Renderer;

use Nextphp\Debugbar\DebugBar;

/**
 * Renders the debug bar as an HTML snippet to be appended before </body>.
 */
final class HtmlRenderer
{
    public function render(DebugBar $bar): string
    {
        $data = $bar->collectAll();

        $tabs    = '';
        $panels  = '';
        $first   = true;

        /** @psalm-suppress MixedAssignment */
        foreach ($data as $name => $collected) {
            /** @var array<string, mixed> $collected */
            $active  = $first ? ' nphp-active' : '';
            $label   = htmlspecialchars(ucfirst($name), ENT_QUOTES);
            $badge   = $this->badge($name, $collected);
            $tabs   .= "<button class=\"nphp-tab{$active}\" data-panel=\"nphp-panel-{$name}\">{$label}{$badge}</button>";
            $panels .= "<div class=\"nphp-panel{$active}\" id=\"nphp-panel-{$name}\">" . $this->renderPanel($name, $collected) . '</div>';
            $first   = false;
        }

        return $this->wrap($tabs, $panels);
    }

    /**
     * @param array<string, mixed> $collected
       * @psalm-pure
     */
    private function badge(string $name, array $collected): string
    {
        $count = match ($name) {
            'queries'  => (int) ($collected['count'] ?? 0),
            'timeline' => count((array) ($collected['entries'] ?? [])),
            default    => 0,
        };

        return $count > 0 ? " <span class=\"nphp-badge\">{$count}</span>" : '';
    }

    /**
     * @param array<string, mixed> $collected
       * @psalm-mutation-free
     */
    private function renderPanel(string $name, array $collected): string
    {
        return match ($name) {
            'queries'  => $this->renderQueries($collected),
            'timeline' => $this->renderTimeline($collected),
            'memory'   => $this->renderMemory($collected),
            'request'  => $this->renderRequest($collected),
            default    => '<pre>' . htmlspecialchars(json_encode($collected, JSON_PRETTY_PRINT) === false ? '{}' : (string) json_encode($collected, JSON_PRETTY_PRINT), ENT_QUOTES) . '</pre>',
        };
    }

    /**
     * @param array<string, mixed> $data
       * @psalm-pure
     */
    private function renderQueries(array $data): string
    {
        $count = (int) ($data['count'] ?? 0);
        $total = round((float) ($data['total_ms'] ?? 0), 2);
        $html  = "<p>{$count} queries &mdash; {$total} ms total</p><ol class=\"nphp-list\">";

        /** @var list<array{sql: string, duration_ms: float, bindings: mixed[]}> $queries */
        $queries = (array) ($data['queries'] ?? []);
        foreach ($queries as $q) {
            $sql  = htmlspecialchars($q['sql'], ENT_QUOTES);
            $ms   = round($q['duration_ms'], 3);
            $html .= "<li><code>{$sql}</code> <span class=\"nphp-dim\">{$ms}&nbsp;ms</span></li>";
        }

        return $html . '</ol>';
    }

    /**
     * @param array<string, mixed> $data
       * @psalm-pure
     */
    private function renderTimeline(array $data): string
    {
        $total = round((float) ($data['total_ms'] ?? 0), 2);
        $html  = "<p>Total: <strong>{$total} ms</strong></p><ul class=\"nphp-list\">";

        /** @var list<array{label: string, start_ms: float, duration_ms: float}> $entries */
        $entries = (array) ($data['entries'] ?? []);
        foreach ($entries as $e) {
            $label    = htmlspecialchars($e['label'], ENT_QUOTES);
            $duration = round($e['duration_ms'], 2);
            $start    = round($e['start_ms'], 2);
            $width    = $total > 0 ? min(100, round($duration / $total * 100.0, 1)) : 0;
            $html    .= "<li>{$label} <span class=\"nphp-dim\">{$start} ms + {$duration} ms</span>"
                     . "<div class=\"nphp-bar-track\"><div class=\"nphp-bar-fill\" style=\"width:{$width}%\"></div></div></li>";
        }

        return $html . '</ul>';
    }

    /**
     * @param array<string, mixed> $data
       * @psalm-pure
     */
    private function renderMemory(array $data): string
    {
        $current = (float) ($data['current_mb'] ?? 0);
        $peak    = (float) ($data['peak_mb'] ?? 0);

        return "<ul class=\"nphp-list\"><li>Current: <strong>{$current} MB</strong></li>"
             . "<li>Peak: <strong>{$peak} MB</strong></li></ul>";
    }

    /**
     * @param array<string, mixed> $data
       * @psalm-pure
     */
    private function renderRequest(array $data): string
    {
        $method  = htmlspecialchars((string) ($data['method'] ?? ''), ENT_QUOTES);
        $uri     = htmlspecialchars((string) ($data['uri'] ?? ''), ENT_QUOTES);
        $html    = "<p><strong>{$method}</strong> {$uri}</p><ul class=\"nphp-list\">";

        /** @var array<string, string> $headers */
        $headers = (array) ($data['headers'] ?? []);
        foreach ($headers as $name => $value) {
            $n     = htmlspecialchars($name, ENT_QUOTES);
            $v     = htmlspecialchars($value, ENT_QUOTES);
            $html .= "<li><code>{$n}</code>: {$v}</li>";
        }

        return $html . '</ul>';
    }

    /**
      * @psalm-pure
     */
    private function wrap(string $tabs, string $panels): string
    {
        return <<<HTML
        <style>
        #nphp-debugbar{position:fixed;bottom:0;left:0;right:0;z-index:99999;font:13px/1.4 'SF Mono',Consolas,monospace;background:#1a1a2e;color:#e0e0e0;box-shadow:0 -2px 8px rgba(0,0,0,.5);}
        #nphp-debugbar-tabs{display:flex;gap:0;border-bottom:1px solid #333;overflow-x:auto;}
        .nphp-tab{background:transparent;border:none;border-right:1px solid #333;color:#aaa;cursor:pointer;padding:6px 14px;white-space:nowrap;font:inherit;}
        .nphp-tab:hover{background:#252540;color:#fff;}
        .nphp-tab.nphp-active{background:#252540;color:#7ec8e3;border-bottom:2px solid #7ec8e3;}
        .nphp-badge{background:#e74c3c;border-radius:9px;color:#fff;font-size:10px;padding:1px 5px;margin-left:4px;}
        #nphp-debugbar-panels{max-height:220px;overflow-y:auto;padding:8px 12px;}
        .nphp-panel{display:none;}
        .nphp-panel.nphp-active{display:block;}
        .nphp-list{list-style:none;margin:4px 0;padding:0;}
        .nphp-list li{padding:3px 0;border-bottom:1px solid #282840;}
        .nphp-dim{color:#888;font-size:11px;}
        .nphp-bar-track{background:#333;border-radius:3px;height:4px;margin-top:3px;}
        .nphp-bar-fill{background:#7ec8e3;height:4px;border-radius:3px;}
        pre{margin:0;white-space:pre-wrap;word-break:break-all;}
        </style>
        <div id="nphp-debugbar">
          <div id="nphp-debugbar-tabs">{$tabs}</div>
          <div id="nphp-debugbar-panels">{$panels}</div>
        </div>
        <script>
        (function(){
          var tabs=document.querySelectorAll('.nphp-tab');
          tabs.forEach(function(t){
            t.addEventListener('click',function(){
              document.querySelectorAll('.nphp-tab,.nphp-panel').forEach(function(el){el.classList.remove('nphp-active');});
              t.classList.add('nphp-active');
              var p=document.getElementById(t.getAttribute('data-panel'));
              if(p)p.classList.add('nphp-active');
            });
          });
        })();
        </script>
        HTML;
    }
}
