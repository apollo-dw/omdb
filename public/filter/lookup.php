<?php
    require_once __DIR__ . '/../../app/base.php';

    header('Content-Type: application/json');

    $type = postOrGet('type', ''); // Currently 'user' or 'tag'
    $query = trim((string)postOrGet('q', ''));
    $ids = postOrGet('ids', '');

    $results = [];

    if ($type === 'user') {
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
            $like = "%" . addcslashes($query, '%_\\') . "%";

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
    } elseif ($type === 'tag') {
        if ($query !== '') {
            $like = "%" . addcslashes($query, '%_\\') . "%";

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
    }

    echo json_encode(['results' => $results]);
