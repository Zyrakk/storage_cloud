<?php
namespace App;

use Prometheus\RenderTextFormat;

class Metrics {
    public static function output(): void {
        global $registry;
        $renderer = new RenderTextFormat();
        header('Content-Type: text/plain; version=0.0.4');
        echo $renderer->render($registry->getMetricFamilySamples());
    }
}
