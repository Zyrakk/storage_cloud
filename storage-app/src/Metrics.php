<?php
namespace App;

use Prometheus\RenderTextFormat;

class Metrics {
    public static function output(): void {
        global $registry, $activeSessions;

        // --- Contar sesiones activas desde Redis ---
        // Obtenemos el adapter Redis y su cliente
        $adapter = $registry->getAdapter();
        $redisClient = $adapter->getClient();

        // Contamos IDs de sesión en el set 'active_sessions'
        $count = $redisClient->sCard('active_sessions');
        // Fijamos el gauge con el valor actual
        $activeSessions->set($count);
        // ----------------------------------------------

        // Renderizado de métricas Prometheus
        $renderer = new RenderTextFormat();
        header('Content-Type: ' . RenderTextFormat::MIME_TYPE);
        echo $renderer->render($registry->getMetricFamilySamples());
    }
}
