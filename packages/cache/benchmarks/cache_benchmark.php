<?php

declare(strict_types=1);

use Nextphp\Cache\ArrayCache;

require dirname(__DIR__) . '/vendor/autoload.php';

$cache = new ArrayCache();
$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
    $cache->set('k' . $i, $i);
}
$elapsed = (microtime(true) - $start) * 1000;

echo "ArrayCache set 10k: " . round($elapsed, 2) . " ms\n";
