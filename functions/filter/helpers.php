<?php
    function encodeTokens(array $tokens): string {
        $parts = [];

        foreach ($tokens as $t) {
            $type = $t['type'] ?? '';
            $id = $t['id'] ?? '';
            $exclude = !empty($t['exclude']);
            $ex = $exclude ? '-' : '';

            switch ($type) {
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
                    $encoded = str_replace(',', '~', $id);
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
        if ($encoded === '')
            return [];

        $tokens = [];
        foreach (explode(',', $encoded) as $part) {
            $part = trim($part);
            if ($part === '')
                continue;

            $prefix = $part[0];
            $rest = substr($part, 1);

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
                    // Restore commas from ~
                    $statusId = str_replace('~', ',', $rest);
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
                    ];

                    $cfg         = $typeMap[$prefix];
                    $typeKey     = $cfg['key'];
                    $labelPrefix = $cfg['label'];

                    $ops = [];
                    $remaining = $rest;
                    while ($remaining !== '') {
                        if (preg_match('/^(>=|<=|>|<|=)(\d+(?:\.\d+)?)(.*)$/s', $remaining, $m)) {
                            $ops[]     = ['op' => $m[1], 'val' => (float)$m[2]];
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
                            '>'  => '<',
                            '>=' => '<=',
                            '<'  => '>',
                            '<=' => '>=',
                            '='  => '=',
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
                            'type'    => $typeKey,
                            'id'      => $idStr,
                            'name'    => $labelPrefix . $idStr,
                            'ops'     => $ops,
                            'exclude' => $exclude,
                        ];
                    }
                    break;
                }
            }
        }

        return $tokens;
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
			'srFilters' => [],
            'csFilters' => [],
            'arFilters' => [],
            'odFilters' => [],
            'hpFilters' => [],
            'lengthFilters' => [],
            'bpmFilters' => [],
            'circlesFilters' => [],
            'slidersFilters' => [],
            'spinnersFilters' => [],
		];

		foreach ($tokensRaw as $t) {
			$type = $t['type'] ?? '';
			$id = $t['id'] ?? '';
			$exclude = !empty($t['exclude']);

			if ($type === 'meta') {
				if ($id === 'friends') $parsed['friendsStatus'] = $exclude ? 'exclude' : 'only';
				if ($id === 'alreadyRated') $parsed['ratedStatus'] = $exclude ? 'exclude' : 'only';
			} elseif ($type === 'status') {
				$parsed['statusFilters'][] = ['id' => $id, 'exclude' => $exclude];
				foreach (explode(',', $id) as $sv) {
					if ($exclude) $parsed['exStatuses'][] = (int)$sv; else $parsed['statuses'][] = (int)$sv;
				}
			} elseif ($type === 'descriptor') {
				$parsed['selectedDescriptors'][] = $t;
				if ($exclude) $parsed['exDescriptors'][] = (int)$id; else $parsed['descriptors'][] = (int)$id;
			} elseif ($type === 'genre') {
				if ($exclude) $parsed['exGenres'][] = (int)$id; else $parsed['genres'][] = (int)$id;
			} elseif ($type === 'language') {
				if ($exclude) $parsed['exLanguages'][] = (int)$id; else $parsed['languages'][] = (int)$id;
			} elseif ($type === 'country') {
				if ($exclude) $parsed['exCountries'][] = $id; else $parsed['countries'][] = $id;
			} elseif (in_array($type, ['sr', 'cs', 'ar', 'od', 'hp', 'length', 'bpm', 'circles', 'sliders', 'spinners']) && !empty($t['ops'])) {
                $rangeConfigs = [
                    'sr' => ['col' => 'b.SR',           'filterKey' => 'srFilters'],
                    'cs' => ['col' => 'b.CircleSize',   'filterKey' => 'csFilters'],
                    'ar' => ['col' => 'b.ApproachRate', 'filterKey' => 'arFilters'],
                    'od' => ['col' => 'b.OverallDifficulty', 'filterKey' => 'odFilters'],
                    'hp' => ['col' => 'b.Drain', 'filterKey' => 'hpFilters'],
                    'length' => ['col' => 'b.PlayTime', 'filterKey' => 'lengthFilters'],
                    'bpm' => ['col' => 'b.Bpm', 'filterKey' => 'bpmFilters'],
                    'circles' => ['col' => 'b.CircleCount', 'filterKey' => 'circlesFilters'],
                    'sliders' => ['col' => 'b.SliderCount', 'filterKey' => 'slidersFilters'],
                    'spinners' => ['col' => 'b.SpinnerCount', 'filterKey' => 'spinnersFilters'],
                ];

                $cfg = $rangeConfigs[$type];
                $dbCol = $cfg['col'];
                $filterKey = $cfg['filterKey'];

                $conds = [];
                foreach ($t['ops'] as $opData) {
                    $op = $opData['op'] ?? '';
                    $val = (float)($opData['val'] ?? 0);
                    
                    if (in_array($op, ['<', '<=', '>', '>=', '='])) {
                        $conds[] = "{$dbCol} {$op} {$val}";
                    }
                }

                if (!empty($conds)) {
                    $condStr = implode(" AND ", $conds);
                    $parsed[$filterKey][] = $exclude ? "NOT ({$condStr})" : "({$condStr})";
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
            $ph = implode(',', array_fill(0, count($parsed['countries']), '?'));
            $sql .= " AND EXISTS (SELECT 1 FROM beatmap_creators bc_f JOIN mappernames mn_f ON bc_f.CreatorID = mn_f.UserID WHERE bc_f.BeatmapID = b.BeatmapID AND mn_f.Country IN ($ph))";
            $types .= str_repeat('s', count($parsed['countries']));
            $values = array_merge($values, $parsed['countries']);
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

        foreach ($parsed['descriptors'] as $dId) {
            $descendantIds = getDescendantDescriptorIds($dId, $conn);
            if (empty($descendantIds))
                $descendantIds = [$dId];

            $ph = implode(',', array_fill(0, count($descendantIds), '?'));
            $sql .= " AND EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID IN ($ph))";
            $types .= str_repeat('i', count($descendantIds));
            $values = array_merge($values, $descendantIds);
        }
        foreach ($parsed['exDescriptors'] as $dId) {
            $descendantIds = getDescendantDescriptorIds($dId, $conn);
            if (empty($descendantIds))
                $descendantIds = [$dId];

            $ph = implode(',', array_fill(0, count($descendantIds), '?'));
            $sql .= " AND NOT EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID IN ($ph))";
            $types .= str_repeat('i', count($descendantIds));
            $values = array_merge($values, $descendantIds);
        }

        foreach ($parsed['srFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        foreach ($parsed['csFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        foreach ($parsed['arFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        foreach ($parsed['odFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        foreach ($parsed['hpFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        foreach ($parsed['lengthFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        foreach ($parsed['bpmFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        foreach ($parsed['circlesFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        foreach ($parsed['slidersFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        foreach ($parsed['spinnersFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        return ['sql' => $sql, 'types' => $types, 'values' => $values];
    }
?>