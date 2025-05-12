<?php
namespace App;

use Prometheus\RenderTextFormat;

class Metrics {
    public static function output(): void {
        global $registry;
        $renderer = new RenderTextFormat();
        header('Content-Type: ' . RenderTextFormat::MIME_TYPE);
        echo $renderer->render($registry->getMetricFamilySamples());
    }
}
