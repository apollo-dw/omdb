<?php
    require_once __DIR__ . '/../../app/base.php';

    header('Content-Type: application/json');

    // 'type' takes a category or comma sep list so one popover render is one request
    $type = postOrGet('type', '');
    $query = trim((string)postOrGet('q', ''));
    $ids = postOrGet('ids', '');

    $requested = array_values(array_intersect(
        array_map('trim', explode(',', (string)$type)),
        ['user', 'tag', 'tournament', 'series']
    ));

    $results = [];
    $like = ($query !== '') ? "%" . addcslashes($query, '%_\\') . "%" : '';

    foreach ($requested as $requestedType) {
        if ($requestedType === 'user') {
            if ($ids !== '') {
                $idList = array_slice(array_filter(array_map('intval', explode(',', $ids))), 0, 25);

                if (!empty($idList)) {
                    $ph = implode(',', array_fill(0, count($idList), '?'));
                    $stmt = $conn->prepare("SELECT UserID, Username FROM mappernames WHERE UserID IN ($ph)");
                    $stmt->bind_param(str_repeat('i', count($idList)), ...$idList);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        $results[] = [
                            'type' => 'user',
                            'id' => (int)$row['UserID'],
                            'name' => $row['Username'],
                            'label' => "Mapper: " . $row['Username'],
                        ];
                    }
                    $stmt->close();
                }
            } elseif ($query !== '') {
                $stmt = $conn->prepare("SELECT m.UserID, m.Username, COUNT(c.BeatmapID) AS MapCount
                    FROM mappernames m
                    LEFT JOIN beatmap_creators c ON c.CreatorID = m.UserID
                    WHERE m.Username LIKE ?
                    GROUP BY m.UserID, m.Username
                    ORDER BY (m.Username = ?) DESC, MapCount DESC, LENGTH(m.Username) ASC
                    LIMIT 8");
                $stmt->bind_param("ss", $like, $query);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $results[] = [
                        'type' => 'user',
                        'id' => (int)$row['UserID'],
                        'name' => $row['Username'],
                        'label' => "Mapper: " . $row['Username'],
                        'count' => (int)$row['MapCount'],
                    ];
                }
                $stmt->close();
            }
        } elseif ($requestedType === 'tag') {
            if ($query !== '') {
                $stmt = $conn->prepare("SELECT Tag, COUNT(*) AS TagCount
                    FROM rating_tags
                    WHERE Tag LIKE ?
                    GROUP BY Tag
                    ORDER BY (Tag = ?) DESC, TagCount DESC
                    LIMIT 8");
                $stmt->bind_param("ss", $like, $query);
            } else {
                $stmt = $conn->prepare("SELECT Tag, COUNT(*) AS TagCount
                    FROM rating_tags
                    GROUP BY Tag
                    ORDER BY TagCount DESC
                    LIMIT 8");
            }

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'type' => 'tag',
                    'id' => $row['Tag'],
                    'name' => $row['Tag'],
                    'label' => "Tag: " . $row['Tag'],
                    'count' => (int)$row['TagCount'],
                ];
            }
            $stmt->close();
        } elseif ($requestedType === 'tournament') {
            if ($query !== '') {
                $stmt = $conn->prepare("SELECT t.TournamentID, t.Name, t.Acronym, COUNT(DISTINCT tm.BeatmapID) AS MapCount
                    FROM tournaments t
                    JOIN tournament_maps tm ON tm.TournamentID = t.TournamentID
                    JOIN beatmaps bm ON bm.BeatmapID = tm.BeatmapID
                    WHERE t.Name LIKE ? OR t.Acronym LIKE ?
                    GROUP BY t.TournamentID
                    ORDER BY (t.Acronym = ?) DESC, (t.Name = ?) DESC, MapCount DESC
                    LIMIT 8");
                $stmt->bind_param("ssss", $like, $like, $query, $query);
            } else {
                $stmt = $conn->prepare("SELECT t.TournamentID, t.Name, t.Acronym, COUNT(DISTINCT tm.BeatmapID) AS MapCount
                    FROM tournaments t
                    JOIN tournament_maps tm ON tm.TournamentID = t.TournamentID
                    JOIN beatmaps bm ON bm.BeatmapID = tm.BeatmapID
                    GROUP BY t.TournamentID
                    ORDER BY t.StartDate DESC, t.Name ASC
                    LIMIT 8");
            }

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $acronym = (string)($row['Acronym'] ?? '');
                $results[] = [
                    'type' => 'tournament',
                    'id' => (int)$row['TournamentID'],
                    'name' => $row['Name'],
                    'label' => "Tournament: " . $row['Name'] . ($acronym !== '' ? " ($acronym)" : ""),
                    'count' => (int)$row['MapCount'],
                ];
            }
            $stmt->close();
        } elseif ($requestedType === 'series') {
            if ($query !== '') {
                $stmt = $conn->prepare("SELECT s.SeriesID, s.Name, s.Acronym, COUNT(DISTINCT tm.BeatmapID) AS MapCount
                    FROM tournament_series s
                    JOIN tournaments t ON t.SeriesID = s.SeriesID
                    JOIN tournament_maps tm ON tm.TournamentID = t.TournamentID
                    JOIN beatmaps bm ON bm.BeatmapID = tm.BeatmapID
                    WHERE s.Name LIKE ? OR s.Acronym LIKE ?
                    GROUP BY s.SeriesID
                    ORDER BY (s.Acronym = ?) DESC, (s.Name = ?) DESC, MapCount DESC
                    LIMIT 8");
                $stmt->bind_param("ssss", $like, $like, $query, $query);
            } else {
                $stmt = $conn->prepare("SELECT s.SeriesID, s.Name, s.Acronym, COUNT(DISTINCT tm.BeatmapID) AS MapCount
                    FROM tournament_series s
                    JOIN tournaments t ON t.SeriesID = s.SeriesID
                    JOIN tournament_maps tm ON tm.TournamentID = t.TournamentID
                    JOIN beatmaps bm ON bm.BeatmapID = tm.BeatmapID
                    GROUP BY s.SeriesID
                    ORDER BY MapCount DESC
                    LIMIT 8");
            }

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $acronym = (string)($row['Acronym'] ?? '');
                $results[] = [
                    'type' => 'series',
                    'id' => (int)$row['SeriesID'],
                    'name' => $row['Name'],
                    'label' => "Series: " . $row['Name'] . ($acronym !== '' ? " ($acronym)" : ""),
                    'count' => (int)$row['MapCount'],
                ];
            }
            $stmt->close();
        }
    }

    echo json_encode(['results' => $results]);
