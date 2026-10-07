<?php
declare(strict_types=1);

namespace App\Core;

/** Renderiza plantillas PHP de app/Views dentro de un layout. */
final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layout/public'): void
    {
        $content = self::capture($template, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::capture($layout, $data + ['content' => $content]);
    }

    public static function capture(string $template, array $data = []): string
    {
        $file = ROOT . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Plantilla no encontrada: $template");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    public static function notFound(): never
    {
        http_response_code(404);
        self::render('public/404', ['title' => '404']);
        exit;
    }
}
