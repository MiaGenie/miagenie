<?php

namespace App;

use Illuminate\Support\HtmlString;

class MiaGenie
{
    /**
     * @return HtmlString
     */
    public static function assets(): HtmlString
    {
        $hot = __DIR__ . '/../public/hot';

        $devServerIsRunning = file_exists($hot);

        if ($devServerIsRunning) {
            $viteServer = file_get_contents($hot);

            return new HtmlString(<<<HTML
                <script type="module" src="$viteServer/@vite/client"></script>
                <script type="module" src="$viteServer/resources/js/app.js"></script>
            HTML);
        }

        $manifestPath = public_path('genie/manifest.json');

        if (!file_exists($manifestPath)) {
            return new HtmlString(<<<HTML
                <div>The manifest.json file could not be found.</div>
            HTML);
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        return new HtmlString(<<<HTML
                <script type="module" src="/genie/{$manifest['resources/js/app.js']['file']}"></script>
                <link rel="stylesheet" href="/genie/{$manifest['resources/js/app.js']['css'][0]}">
            HTML);
    }
}
