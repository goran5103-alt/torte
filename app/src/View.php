<?php
declare(strict_types=1);

namespace App;

final class View
{
    /** Renderuje šablon iz app/templates, opciono unutar layout-a. */
    /** Promenljive koje šablon postavi za layout (naslov stranice, dodatni JS). */
    private const LAYOUT_VARS = ['title', 'scripts'];

    public static function render(string $template, array $vars = [], ?string $layout = null): void
    {
        $exported = [];
        $content = self::capture($template, $vars, $exported);
        if ($layout !== null) {
            $content = self::capture($layout, $exported + $vars + ['content' => $content]);
        }
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
    }

    public static function capture(string $template, array $vars = [], ?array &$exported = null): string
    {
        $file = APP_DIR . '/templates/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Šablon ne postoji: $template");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $exported = array_intersect_key(get_defined_vars(), array_flip(self::LAYOUT_VARS));
        return (string)ob_get_clean();
    }
}
