<?php
    // Tags are free text, so they get percent-encoded to survive the comma separated token string
    function encodeFilterTagValue(string $tag): string {
        return str_replace('-', '%2D', rawurlencode($tag));
    }

    function decodeFilterTagValue(string $value): string {
        return rawurldecode($value);
    }

    function filterJoinTypeChars(): array {
        return [
            'descriptor' => 'd',
            'country' => 'c',
            'user' => 'u',
            'tag' => 'k',
            'sr' => 'r',
            'cs' => 'p',
            'ar' => 'a',
            'od' => 'o',
            'hp' => 'h',
            'length' => 't',
            'bpm' => 'b',
            'circles' => 'x',
            'sliders' => 'y',
            'spinners' => 'z',
            'desc' => 'n',
        ];
    }

    function filterDefaultJoinModes(): array {
        return [
            'descriptor' => 'and',
            'country' => 'or',
            'user' => 'or',
            'tag' => 'or',
            'genre' => 'or',
            'language' => 'or',
            'status' => 'or',
            'sr' => 'and',
            'cs' => 'and',
            'ar' => 'and',
            'od' => 'and',
            'hp' => 'and',
            'length' => 'and',
            'bpm' => 'and',
            'circles' => 'and',
            'sliders' => 'and',
            'spinners' => 'and',
            'desc' => 'and',
        ];
    }

    function encodeTokens(array $tokens): string {
        $parts = [];
        $joinChars = filterJoinTypeChars();

        foreach ($tokens as $t) {
            $type = $t['type'] ?? '';
            $id = $t['id'] ?? '';
            $exclude = !empty($t['exclude']);
            $ex = $exclude ? '-' : '';

            switch ($type) {
                case 'joinMode': {
                    $char = $joinChars[$id] ?? null;
                    if ($char !== null) {
                        $parts[] = "j{$char}" . ((($t['mode'] ?? 'and') === 'or') ? '1' : '0');
                    }
                    break;
                }

                case 'user':
                    $parts[] = "u{$ex}" . (int)$id;
                    break;

                case 'tag':
                    $parts[] = "k{$ex}" . encodeFilterTagValue((string)$id);
                    break;

                case 'genre':
                    $parts[] = "g{$ex}{$id}";
                    break;

                case 'language':
                    $parts[] = "l{$ex}{$id}";
                    break;

                case 'descriptor':
                    $parts[] = "d{$ex}{$id}";
                    break;

                case 'status':
                    // Replace commas in multi-value status IDs with ~
                    // Replace negative status with _
                    $encoded = str_replace([',', '-'], ['~', '_'], $id);
                    $parts[] = "s{$ex}{$encoded}";
                    break;

                case 'country':
                    $parts[] = "c{$ex}{$id}";
                    break;

                case 'meta':
                    $parts[] = "m{$ex}{$id}";
                    break;

                case 'ar':
                case 'od':
                case 'hp':
                case 'length':
                case 'bpm':
                case 'circles':
                case 'sliders':
                case 'spinners':
                case 'sr':
                case 'desc':
                case 'cs': {
                    if (!empty($t['ops'])) {
                        $typeMap = [
                            'sr' => 'r',
                            'cs' => 'p',
                            'ar' => 'a',
                            'od' => 'o',
                            'hp' => 'h',
                            'length' => 't',
                            'bpm' => 'b',
                            'circles' => 'x',
                            'sliders' => 'y',
                            'spinners' => 'z',
                            'desc' => 'n',
                        ];

                        $prefix = $typeMap[$type];

                        $opStr = '';
                        foreach ($t['ops'] as $op) {
                            $opStr .= ($op['op'] ?? '') . ($op['val'] ?? '');
                        }
                        $parts[] = "{$prefix}{$ex}{$opStr}";
                    }
                    break;
                }
            }
        }

        return implode(',', $parts);
    }

    function decodeTokens(string $encoded): array {
        if ($encoded === '') {
            return [];
        }

        $tokens = [];
        $joinTypes = array_flip(filterJoinTypeChars());

        foreach (explode(',', $encoded) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $prefix = $part[0];
            $rest = substr($part, 1);

            if ($prefix === 'j') {
                $char = $rest !== '' ? $rest[0] : '';
                if (isset($joinTypes[$char])) {
                    $tokens[] = [
                        'type' => 'joinMode',
                        'id' => $joinTypes[$char],
                        'mode' => (substr($rest, 1) === '1') ? 'or' : 'and',
                    ];
                }
                continue;
            }

            $exclude = false;
            if ($rest !== '' && $rest[0] === '-') {
                $exclude = true;
                $rest = substr($rest, 1);
            }

            switch ($prefix) {
                case 'g':
                    $tokens[] = [
                        'type' => 'genre',
                        'id' => (int)$rest,
                        'exclude' => $exclude,
                    ];
                    break;

                case 'l':
                    $tokens[] = [
                        'type' => 'language',
                        'id' => (int)$rest,
                        'exclude' => $exclude,
                    ];
                    break;

                case 'd':
                    $tokens[] = [
                        'type' => 'descriptor',
                        'id' => (int)$rest,
                        'exclude' => $exclude,
                    ];
                    break;

                case 's':
                    // Restore commas from ~ and negative statuses from _
                    $statusId = str_replace(['~', '_'], [',', '-'], $rest);
                    $tokens[] = [
                        'type' => 'status',
                        'id' => $statusId,
                        'exclude' => $exclude,
                    ];
                    break;

                case 'c':
                    $tokens[] = [
                        'type' => 'country',
                        'id' => $rest,
                        'exclude' => $exclude,
                    ];
                    break;

                case 'm':
                    $tokens[] = [
                        'type' => 'meta',
                        'id' => $rest,
                        'exclude' => $exclude,
                    ];
                    break;

                case 'u':
                    $tokens[] = [
                        'type' => 'user',
                        'id' => (int)$rest,
                        'exclude' => $exclude,
                    ];
                    break;

                case 'k':
                    $tokens[] = [
                        'type' => 'tag',
                        'id' => decodeFilterTagValue($rest),
                        'exclude' => $exclude,
                    ];
                    break;

                case 'n':
                case 'a':
                case 'o':
                case 'h':
                case 't':
                case 'b':
                case 'x':
                case 'y':
                case 'z':
                case 'r':
                case 'p': {
                    $typeMap = [
                        'r' => ['key' => 'sr', 'label' => 'SR: '],
                        'p' => ['key' => 'cs', 'label' => 'CS: '],
                        'a' => ['key' => 'ar', 'label' => 'AR: '],
                        'o' => ['key' => 'od', 'label' => 'OD: '],
                        'h' => ['key' => 'hp', 'label' => 'HP: '],
                        't' => ['key' => 'length', 'label' => 'Length: '],
                        'b' => ['key' => 'bpm', 'label' => 'BPM: '],
                        'x' => ['key' => 'circles', 'label' => 'Circle count: '],
                        'y' => ['key' => 'sliders', 'label' => 'Slider count: '],
                        'z' => ['key' => 'spinners', 'label' => 'Spinner count: '],
                        'n' => ['key' => 'desc', 'label' => 'Descriptor Count: '],
                    ];

                    $cfg = $typeMap[$prefix];
                    $typeKey = $cfg['key'];
                    $labelPrefix = $cfg['label'];

                    $ops = [];
                    $remaining = $rest;
                    while ($remaining !== '') {
                        if (preg_match('/^(>=|<=|>|<|=)(\d+(?:\.\d+)?)(.*)$/s', $remaining, $m)) {
                            $ops[] = ['op' => $m[1], 'val' => (float)$m[2]];
                            $remaining = $m[3];
                        } else {
                            break; // fucked
                        }
                    }

                    if (!empty($ops)) {
                        $idStr = '';
                        $lower = null;
                        $upper = null;
                        $flip = [
                            '>' => '<',
                            '>=' => '<=',
                            '<' => '>',
                            '<=' => '>=',
                            '=' => '=',
                        ];

                        foreach ($ops as $op) {
                            switch ($op['op']) {
                                case '>':
                                case '>=':
                                    $lower = $op;
                                    break;

                                case '<':
                                case '<=':
                                    $upper = $op;
                                    break;

                                case '=':
                                    $lower = $upper = $op;
                                    break;
                            }
                        }

                        if ($lower && $upper) {
                            if ($lower['op'] === '=' && $upper['op'] === '=') {
                                $idStr = $typeKey . '=' . $lower['val'];
                            } else {
                                $idStr = $lower['val']
                                    . $flip[$lower['op']]
                                    . $typeKey
                                    . $upper['op']
                                    . $upper['val'];
                            }
                        } elseif ($lower) {
                            $idStr = $typeKey . $lower['op'] . $lower['val'];
                        } elseif ($upper) {
                            $idStr = $typeKey . $upper['op'] . $upper['val'];
                        }

                        $tokens[] = [
                            'type' => $typeKey,
                            'id' => $idStr,
                            'name' => $labelPrefix . $idStr,
                            'ops' => $ops,
                            'exclude' => $exclude,
                        ];
                    }
                    break;
                }
            }
        }

        return $tokens;
    }

    function filterRangeColumns(): array {
        return [
            'sr' => 'b.SR',
            'cs' => 'b.CircleSize',
            'ar' => 'b.ApproachRate',
            'od' => 'b.OverallDifficulty',
            'hp' => 'b.Drain',
            'length' => 'b.PlayTime',
            'bpm' => 'b.Bpm',
            'circles' => 'b.CircleCount',
            'sliders' => 'b.SliderCount',
            'spinners' => 'b.SpinnerCount',
            'desc' => '',
        ];
    }

    // beatmap_descriptors.BeatmapID is varchar
    // TODO: Make it NOT varchar
    function filterDescriptorCountCondition(string $op, float $val): string {
        $exists = "EXISTS (SELECT 1 FROM beatmap_descriptors bd_c WHERE bd_c.BeatmapID = b.BeatmapID)";
        $count = "(SELECT COUNT(*) FROM beatmap_descriptors bd_c WHERE bd_c.BeatmapID = CAST(b.BeatmapID AS CHAR))";

        if (($op === '=' && $val == 0) || ($op === '<=' && $val == 0) || ($op === '<' && $val > 0 && $val <= 1)) {
            return "NOT {$exists}";
        }

        if (($op === '>' && $val == 0) || ($op === '>=' && $val > 0 && $val <= 1)) {
            return $exists;
        }

        return "{$count} {$op} {$val}";
    }

    function parseFilterTokens($tokensRaw) {
        $parsed = [
            'friendsStatus' => 'any',
            'ratedStatus' => 'any',
            'statusFilters' => [],
            'statuses' => [],
            'exStatuses' => [],
            'selectedDescriptors' => [],
            'descriptors' => [],
            'exDescriptors' => [],
            'genres' => [],
            'exGenres' => [],
            'languages' => [],
            'exLanguages' => [],
            'countries' => [],
            'exCountries' => [],
            'users' => [],
            'exUsers' => [],
            'tags' => [],
            'exTags' => [],
            'rangeFilters' => [],
            'joinModes' => [],
        ];

        $rangeColumns = filterRangeColumns();

        foreach ($tokensRaw as $t) {
            $type = $t['type'] ?? '';
            $id = $t['id'] ?? '';
            $exclude = !empty($t['exclude']);

            if ($type === 'joinMode') {
                if (isset(filterDefaultJoinModes()[$id])) {
                    $parsed['joinModes'][$id] = (($t['mode'] ?? 'and') === 'or') ? 'or' : 'and';
                }
            } elseif ($type === 'meta') {
                if ($id === 'friends') {
                $parsed['friendsStatus'] = $exclude ? 'exclude' : 'only';
                }
                if ($id === 'alreadyRated') {
                $parsed['ratedStatus'] = $exclude ? 'exclude' : 'only';
                }
            } elseif ($type === 'status') {
                $parsed['statusFilters'][] = ['id' => $id, 'exclude' => $exclude];
                foreach (explode(',', $id) as $sv) {
                    if ($exclude) {
                    $parsed['exStatuses'][] = (int)$sv;
                    } else {
                    $parsed['statuses'][] = (int)$sv;
                    }
                }
            } elseif ($type === 'descriptor') {
                $parsed['selectedDescriptors'][] = $t;
                if ($exclude) {
                $parsed['exDescriptors'][] = (int)$id;
                } else {
                $parsed['descriptors'][] = (int)$id;
                }
            } elseif ($type === 'genre') {
                if ($exclude) {
                $parsed['exGenres'][] = (int)$id;
                } else {
                $parsed['genres'][] = (int)$id;
                }
            } elseif ($type === 'language') {
                if ($exclude) {
                $parsed['exLanguages'][] = (int)$id;
                } else {
                $parsed['languages'][] = (int)$id;
                }
            } elseif ($type === 'country') {
                if ($exclude) {
                $parsed['exCountries'][] = $id;
                } else {
                $parsed['countries'][] = $id;
                }
            } elseif ($type === 'user') {
                if ($exclude) {
                $parsed['exUsers'][] = (int)$id;
                } else {
                $parsed['users'][] = (int)$id;
                }
            } elseif ($type === 'tag') {
                if ($id === '') {
                continue;
                }
                if ($exclude) {
                $parsed['exTags'][] = (string)$id;
                } else {
                $parsed['tags'][] = (string)$id;
                }
            } elseif (isset($rangeColumns[$type]) && !empty($t['ops'])) {
                $dbCol = $rangeColumns[$type];

                $conds = [];
                foreach ($t['ops'] as $opData) {
                    $op = $opData['op'] ?? '';
                    $val = (float)($opData['val'] ?? 0);

                    if (in_array($op, ['<', '<=', '>', '>=', '='])) {
                        $conds[] = ($type === 'desc')
                            ? filterDescriptorCountCondition($op, $val)
                            : "{$dbCol} {$op} {$val}";
                    }
                }

                if (!empty($conds)) {
                    $condStr = implode(" AND ", $conds);

                    if (!isset($parsed['rangeFilters'][$type])) {
                        $parsed['rangeFilters'][$type] = ['include' => [], 'exclude' => []];
                    }

                    if ($exclude) {
                        $parsed['rangeFilters'][$type]['exclude'][] = "NOT ({$condStr})";
                    }
                    else {
                    $parsed['rangeFilters'][$type]['include'][] = "({$condStr})";
                    }
                }
            }
        }

        return $parsed;
    }

    function getDescendantDescriptorIds($descriptorId, $conn) {
        $stmt = $conn->prepare("WITH RECURSIVE DescendantDescriptors AS (
                SELECT DescriptorID
                FROM descriptors
                WHERE DescriptorID = ?
                UNION ALL
                SELECT d.DescriptorID
                FROM descriptors d
                JOIN DescendantDescriptors dd ON d.ParentID = dd.DescriptorID
            )
            SELECT DescriptorID FROM DescendantDescriptors;");
        $stmt->bind_param("i", $descriptorId);
        $stmt->execute();
        $result = $stmt->get_result();

        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['DescriptorID'];
        }
        $stmt->close();

        return $ids;
    }

    function buildBeatmapFilterSQL(array $parsed, $conn): array {
        $sql = "";
        $types = "";
        $values = [];

        $defaultModes = filterDefaultJoinModes();
        $joinMode = function (string $type) use ($parsed, $defaultModes): string {
            return (($parsed['joinModes'][$type] ?? $defaultModes[$type] ?? 'and') === 'or') ? 'or' : 'and';
        };

        $addGroup = function (array $fragments) use (&$sql, &$types, &$values) {
            if (empty($fragments)) {
                return;
            }

            $parts = [];
            foreach ($fragments as $fragment) {
                $parts[] = $fragment['sql'];
                $types .= $fragment['types'];
                $values = array_merge($values, $fragment['values']);
            }

            $sql .= " AND (" . implode(" AND ", $parts) . ")";
        };

        if (!empty($parsed['genres'])) {
            $ph = implode(',', array_fill(0, count($parsed['genres']), '?'));
            $sql .= " AND s.Genre IN ($ph)";
            $types .= str_repeat('i', count($parsed['genres']));
            $values = array_merge($values, $parsed['genres']);
        }
        if (!empty($parsed['exGenres'])) {
            $ph = implode(',', array_fill(0, count($parsed['exGenres']), '?'));
            $sql .= " AND s.Genre NOT IN ($ph)";
            $types .= str_repeat('i', count($parsed['exGenres']));
            $values = array_merge($values, $parsed['exGenres']);
        }

        if (!empty($parsed['languages'])) {
            $ph = implode(',', array_fill(0, count($parsed['languages']), '?'));
            $sql .= " AND s.Lang IN ($ph)";
            $types .= str_repeat('i', count($parsed['languages']));
            $values = array_merge($values, $parsed['languages']);
        }
        if (!empty($parsed['exLanguages'])) {
            $ph = implode(',', array_fill(0, count($parsed['exLanguages']), '?'));
            $sql .= " AND s.Lang NOT IN ($ph)";
            $types .= str_repeat('i', count($parsed['exLanguages']));
            $values = array_merge($values, $parsed['exLanguages']);
        }

        if (!empty($parsed['countries'])) {
            if ($joinMode('country') === 'or') {
                $ph = implode(',', array_fill(0, count($parsed['countries']), '?'));
                $sql .= " AND EXISTS (SELECT 1 FROM beatmap_creators bc_f JOIN mappernames mn_f ON bc_f.CreatorID = mn_f.UserID WHERE bc_f.BeatmapID = b.BeatmapID AND mn_f.Country IN ($ph))";
                $types .= str_repeat('s', count($parsed['countries']));
                $values = array_merge($values, $parsed['countries']);
            } else {
                $fragments = [];
                foreach ($parsed['countries'] as $country) {
                    $fragments[] = [
                        'sql' => "EXISTS (SELECT 1 FROM beatmap_creators bc_f JOIN mappernames mn_f ON bc_f.CreatorID = mn_f.UserID WHERE bc_f.BeatmapID = b.BeatmapID AND mn_f.Country = ?)",
                        'types' => 's',
                        'values' => [$country],
                    ];
                }
                $addGroup($fragments);
            }
        }
        if (!empty($parsed['exCountries'])) {
            $ph = implode(',', array_fill(0, count($parsed['exCountries']), '?'));
            $sql .= " AND NOT EXISTS (SELECT 1 FROM beatmap_creators bc_f JOIN mappernames mn_f ON bc_f.CreatorID = mn_f.UserID WHERE bc_f.BeatmapID = b.BeatmapID AND mn_f.Country IN ($ph))";
            $types .= str_repeat('s', count($parsed['exCountries']));
            $values = array_merge($values, $parsed['exCountries']);
        }

        if (!empty($parsed['statuses'])) {
            $ph = implode(',', array_fill(0, count($parsed['statuses']), '?'));
            $sql .= " AND b.Status IN ($ph)";
            $types .= str_repeat('i', count($parsed['statuses']));
            $values = array_merge($values, $parsed['statuses']);
        }
        if (!empty($parsed['exStatuses'])) {
            $ph = implode(',', array_fill(0, count($parsed['exStatuses']), '?'));
            $sql .= " AND b.Status NOT IN ($ph)";
            $types .= str_repeat('i', count($parsed['exStatuses']));
            $values = array_merge($values, $parsed['exStatuses']);
        }

        if (!empty($parsed['users'])) {
            if ($joinMode('user') === 'or') {
                $ph = implode(',', array_fill(0, count($parsed['users']), '?'));
                $sql .= " AND EXISTS (SELECT 1 FROM beatmap_creators bc_u WHERE bc_u.BeatmapID = b.BeatmapID AND bc_u.CreatorID IN ($ph))";
                $types .= str_repeat('i', count($parsed['users']));
                $values = array_merge($values, $parsed['users']);
            } else {
                $fragments = [];
                foreach ($parsed['users'] as $creatorId) {
                    $fragments[] = [
                        'sql' => "EXISTS (SELECT 1 FROM beatmap_creators bc_u WHERE bc_u.BeatmapID = b.BeatmapID AND bc_u.CreatorID = ?)",
                        'types' => 'i',
                        'values' => [$creatorId],
                    ];
                }
                $addGroup($fragments);
            }
        }
        foreach ($parsed['exUsers'] as $creatorId) {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM beatmap_creators bc_u WHERE bc_u.BeatmapID = b.BeatmapID AND bc_u.CreatorID = ?)";
            $types .= 'i';
            $values[] = $creatorId;
        }

        if (!empty($parsed['tags'])) {
            if ($joinMode('tag') === 'or') {
                $ph = implode(',', array_fill(0, count($parsed['tags']), '?'));
                $sql .= " AND EXISTS (SELECT 1 FROM rating_tags rt_f WHERE rt_f.BeatmapID = b.BeatmapID AND rt_f.Tag IN ($ph))";
                $types .= str_repeat('s', count($parsed['tags']));
                $values = array_merge($values, $parsed['tags']);
            } else {
                $fragments = [];
                foreach ($parsed['tags'] as $tag) {
                    $fragments[] = [
                        'sql' => "EXISTS (SELECT 1 FROM rating_tags rt_f WHERE rt_f.BeatmapID = b.BeatmapID AND rt_f.Tag = ?)",
                        'types' => 's',
                        'values' => [$tag],
                    ];
                }
                $addGroup($fragments);
            }
        }
        foreach ($parsed['exTags'] as $tag) {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM rating_tags rt_f WHERE rt_f.BeatmapID = b.BeatmapID AND rt_f.Tag = ?)";
            $types .= 's';
            $values[] = $tag;
        }

        if (!empty($parsed['descriptors'])) {
            $descriptorIdSets = [];
            foreach ($parsed['descriptors'] as $dId) {
                $descendantIds = getDescendantDescriptorIds($dId, $conn);
                $descriptorIdSets[] = empty($descendantIds) ? [$dId] : $descendantIds;
            }

            if ($joinMode('descriptor') === 'or') {
                $mergedIds = array_values(array_unique(array_merge(...$descriptorIdSets)));
                $ph = implode(',', array_fill(0, count($mergedIds), '?'));
                $sql .= " AND EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID IN ($ph))";
                $types .= str_repeat('i', count($mergedIds));
                $values = array_merge($values, $mergedIds);
            } else {
                $fragments = [];
                foreach ($descriptorIdSets as $descendantIds) {
                    $ph = implode(',', array_fill(0, count($descendantIds), '?'));
                    $fragments[] = [
                        'sql' => "EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID IN ($ph))",
                        'types' => str_repeat('i', count($descendantIds)),
                        'values' => $descendantIds,
                    ];
                }
                $addGroup($fragments);
            }
        }
        foreach ($parsed['exDescriptors'] as $dId) {
            $descendantIds = getDescendantDescriptorIds($dId, $conn);
            if (empty($descendantIds)) {
                $descendantIds = [$dId];
            }

            $ph = implode(',', array_fill(0, count($descendantIds), '?'));
            $sql .= " AND NOT EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID IN ($ph))";
            $types .= str_repeat('i', count($descendantIds));
            $values = array_merge($values, $descendantIds);
        }

        foreach ($parsed['rangeFilters'] as $rangeType => $conds) {
            if (!empty($conds['include'])) {
                $glue = ($joinMode($rangeType) === 'or') ? " OR " : " AND ";
                $sql .= " AND (" . implode($glue, $conds['include']) . ")";
            }

            foreach ($conds['exclude'] as $cond) {
                $sql .= " AND $cond";
            }
        }

        return ['sql' => $sql, 'types' => $types, 'values' => $values];
    }
