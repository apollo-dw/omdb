<?php
  function parseShortlinks(mysqli $conn, string $text, bool $useLink = true): string {
    $cache = [];

    return preg_replace_callback(
        '/\[([A-Za-z]+)(\d+)\]/',
        function ($matches) use ($conn, &$cache, $useLink) {
            $type = strtolower($matches[1]);
            $id = (int)$matches[2];

            $key = "{$type}:{$id}";

            if (isset($cache[$key])) {
                return $cache[$key];
            }

            switch ($type) {
                case 'descriptor':
                    $text = parseDescriptorShortlinks($conn, $id, $useLink);
                    return $cache[$key] = $text;

                case 'mapset':
                case 'beatmapset':
                case 'set':
                    $text = parseBeatmapsetShortLinks($conn, $id, $useLink);
                    return $cache[$key] = $text;

                case 'beatmap':
                case 'map':
                    $text = parseBeatmapShortLinks($conn, $id, $useLink);
                    return $cache[$key] = $text;

                case 'list':
                    $text = parseListShortLinks($conn, $id, $useLink);
                    return $cache[$key] = $text;

                case 'profile':
                case 'user':
                    $text = parseUserShortLinks($conn, $id, $useLink);
                    return $cache[$key] = $text;

                default:
                    return $matches[0];
            }
        },
        $text
    );
  }

    function parseDescriptorShortlinks($conn, $id, $useLink = true): string {
        $stmt = $conn->prepare("SELECT Name FROM descriptors WHERE DescriptorID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $descriptor = $stmt->get_result()->fetch_assoc();

        if (!$descriptor) {
        return '...';
        }

        if (!$useLink) {
        return htmlspecialchars($descriptor['Name']);
        }

        return sprintf(
        '<a style="font-weight: bold;" href="/descriptor/?id=%d">%s</a>',
        $id,
        htmlspecialchars($descriptor['Name'])
        );
    }

    function parseBeatmapsetShortLinks($conn, $id, $useLink = true): string {
        $stmt = $conn->prepare("SELECT s.Artist, s.Title, mn.Username FROM beatmapsets s LEFT JOIN mappernames mn ON s.CreatorID = mn.UserID WHERE s.SetID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $set = $stmt->get_result()->fetch_assoc();

        if (!$set) {
            return '...';
        }

        if (!$useLink) {
            return htmlspecialchars("{$set['Artist']} - {$set['Title']} ({$set['Username']})");
        }

        return sprintf(
            '<a style="font-weight: bold;" href="/mapset/%d">%s</a>',
            $id,
            htmlspecialchars("{$set['Artist']} - {$set['Title']} ({$set['Username']})")
        );
    }

    function parseBeatmapShortLinks($conn, $id, $useLink = true): string {
        $stmt = $conn->prepare("SELECT s.Title, s.Artist, b.DifficultyName, s.SetID FROM beatmaps b LEFT JOIN beatmapsets s ON b.SetID = s.SetID WHERE b.BeatmapID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $beatmap = $stmt->get_result()->fetch_assoc();

        if (!$beatmap) {
            return '...';
        }

        if (!$useLink) {
            return htmlspecialchars("{$beatmap['Artist']} - {$beatmap['Title']} ({$beatmap['DifficultyName']})");
        }

        return sprintf(
            '<a style="font-weight: bold;" href="/mapset/%d">%s</a>',
            $beatmap['SetID'],
            htmlspecialchars("{$beatmap['Artist']} - {$beatmap['Title']} ({$beatmap['DifficultyName']})")
        );
    }

    function parseListShortLinks($conn, $id, $useLink = true): string {
        $stmt = $conn->prepare("SELECT l.Title, u.Username FROM lists l LEFT JOIN users u ON l.UserID = u.UserID WHERE l.ListID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $list = $stmt->get_result()->fetch_assoc();

        if (!$list) {
            return '...';
        }

        if (!$useLink) {
            return htmlspecialchars("{$list['Title']} ({$list['Username']})");
        }

        return sprintf(
            '<a style="font-weight: bold;" href="/list/?id=%d">%s</a>',
            $id,
            htmlspecialchars("{$list['Title']} ({$list['Username']})")
        );
    }

    function parseUserShortLinks($conn, $id, $useLink = true): string {
        if (!$useLink) {
            return htmlspecialchars(getUsernameFromId($id, $conn));
        }

       return sprintf(
            '<a style="font-weight: bold;" href="/profile/%d">%s</a>',
            $id,
            htmlspecialchars(getUsernameFromId($id, $conn))
        );
    }