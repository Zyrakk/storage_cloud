<?php
namespace App;

use Prometheus\RenderTextFormat;

class Metrics {
    public static function output(): void {
        global $registry, $activeSessions, $redis;

        // --- Contar sesiones activas desde Redis ---
        // Usamos el cliente Redis inicializado en init.php
        $count = $redis->sCard('active_sessions');
        // Fijamos el gauge con el valor actual
        $activeSessions->set($count);
        // ----------------------------------------------

        // Renderizado de métricas Prometheus
        $renderer = new RenderTextFormat();
        header('Content-Type: ' . RenderTextFormat::MIME_TYPE);
        echo $renderer->render($registry->getMetricFamilySamples());
    }
}
