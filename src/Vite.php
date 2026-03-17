<?php

namespace Rapo;

class Vite {
    public static function render($entry = 'src/Assets/app.js'): string {
        $hotFile = getcwd() . '/public/hot';
        
        if (file_exists($hotFile)) {
            $url = trim(file_get_contents($hotFile));
            return <<<HTML
                <script type="module" src="{$url}/@vite/client"></script>
                <script type="module" src="{$url}/{$entry}"></script>
            HTML;
        }

        $manifestPath = getcwd() . '/public/build/manifest.json';
        if (!file_exists($manifestPath)) {
            return '';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $tags = '';
        
        if (isset($manifest[$entry])) {
            $file = $manifest[$entry]['file'];
            $tags .= '<script type="module" src="/build/' . $file . '"></script>';
            
            if (isset($manifest[$entry]['css'])) {
                foreach ($manifest[$entry]['css'] as $cssFile) {
                    $tags .= '<link rel="stylesheet" href="/build/' . $cssFile . '">';
                }
            }
        }

        return $tags;
    }
}
