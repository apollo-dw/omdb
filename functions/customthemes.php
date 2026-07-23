<?php
    $CUSTOM_THEME_FONTS = [
        'Arial'              => 'Arial, sans-serif',
        'Verdana'            => 'Verdana, sans-serif',
        'Helvetica'          => 'Helvetica, Arial, sans-serif',
        'Tahoma'             => 'Tahoma, Geneva, sans-serif',
        'Trebuchet MS'       => '"Trebuchet MS", Helvetica, sans-serif',
        'Segoe UI'           => '"Segoe UI", Tahoma, Geneva, sans-serif',
        'Times New Roman'    => '"Times New Roman", Times, serif',
        'Georgia'            => 'Georgia, serif',
        'Garamond'           => 'Garamond, serif',
        'Palatino Linotype'  => '"Palatino Linotype", "Book Antiqua", Palatino, serif',
        'Book Antiqua'       => '"Book Antiqua", Palatino, serif',
        'Courier New'        => '"Courier New", Courier, monospace',
        'Lucida Console'     => '"Lucida Console", Monaco, monospace',
        'Impact'             => 'Impact, Charcoal, sans-serif',
        'Comic Sans MS'      => '"Comic Sans MS", cursive, sans-serif',
    ];

    $DEFAULT_THEME = [
        'main-theme-color' => 'DarkSlateGray',
        'main-theme-color-darker' => '#203838',
        'main-theme-color-even-darker' => '#0c1515',
        'main-theme-text-color' => 'white',
        'main-theme-background-color' => 'black',
        'main-theme-subtext-color' => '#a8a8a8',
        'main-theme-link-color' => '#6fffea',
        'main-theme-star-color' => 'white',
        'main-theme-patron-pink' => '#ecb4f5',
        'main-theme-text-font-family' => 'Verdana, sans-serif',
    ];

    function GetOmdbLogoFilters(string $hex): array {
        // if hex doesnt contain #, early return default values
        if (!preg_match('/^#?[0-9A-Fa-f]{6}$/', $hex)) {
            return [
                'rotation' => 0,
                'saturation' => 1,
                'brightness' => 1,
            ];
        }

        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $d = $max - $min;

        // magic bs from you-know-where
        if ($d == 0) {
            $h = 0;
        } elseif ($max == $r) {
            $h = 60 * fmod((($g - $b) / $d + 6), 6);
        } elseif ($max == $g) {
            $h = 60 * ((($b - $r) / $d) + 2);
        } else {
            $h = 60 * ((($r - $g) / $d) + 4);
        }

        $l = ($max + $min) / 2;
        $s = $d == 0 ? 0 : $d / (1 - abs(2 * $l - 1));
        
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        $sourceLuminance =
            0.2126 * (47 / 255) +
            0.7152 * (79 / 255) +
            0.0722 * (79 / 255);
        $brightness = round($luminance / $sourceLuminance, 2);

        return [
            'rotation' => round(($h - 180)),
            'saturation' => round(max(1, $s / 0.25), 2),
            'brightness' => max(0.5, min(2, $brightness)),
        ];
    }

    function ParseCSSValueEscapes(string $value): string {
        return str_replace(
            ['</style', '<', '>', "\0"],
            ['<\/style', '\3C ', '\3E ', ''],
            $value
        );
    }

    function ParseCSSVariableNames(string $name): string {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
    }

    function RenderCustomThemeCss($profile) {
        global $DEFAULT_THEME;

        if (!isset($profile['IsPatron']) || $profile['IsPatron'] !== 1) {
            return;
        }

        $userTheme = [];
        if (!empty($profile['ProfileTheme'])) {
            $userTheme = json_decode($profile['ProfileTheme'], true) ?? [];
        }

        $activeTheme = array_merge($DEFAULT_THEME, $userTheme);
        $imageFilter = GetOmdbLogoFilters($activeTheme['main-theme-color']);

        echo "<style>\n";
        echo ":root {\n";
        foreach ($activeTheme as $variable => $value) {
            echo "--" .
                ParseCSSVariableNames($variable) .
                ": " .
                ParseCSSValueEscapes((string)$value) .
                ";\n";
        }
        echo "--top-bar-icon-hue: " . (float)$imageFilter['rotation'] . "deg;\n";
        echo "--top-bar-icon-saturation: " . (float)$imageFilter['saturation'] . ";\n";
        echo "--top-bar-icon-brightness: " . (float)$imageFilter['brightness'] . ";\n";

        echo "}\n";
        echo "</style>\n";
    }

