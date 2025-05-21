<?php
namespace App;

use Prometheus\RenderTextFormat;

class Metrics {
    public static function output(): void {
        global $registry, $activeSessions, $redisAdapter;

        // --- Contar sesiones activas desde Redis ---
        // Obtenemos el cliente Redis desde el adapter definido en init.php
        $redisClient = $redisAdapter->getClient();

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
