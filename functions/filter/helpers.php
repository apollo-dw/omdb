<?php
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
			} elseif ($type === 'sr' && !empty($t['ops'])) {
				$srConds = [];
				foreach ($t['ops'] as $opData) {
					$op = $opData['op'] ?? '';
					$val = (float)($opData['val'] ?? 0);
					
					if (in_array($op, ['<', '<=', '>', '>=', '='])) {
						$srConds[] = "b.SR {$op} {$val}";
					}
				}
				if (!empty($srConds)) {
					$srCond = implode(" AND ", $srConds);
					$parsed['srFilters'][] = $exclude ? "NOT ({$srCond})" : "({$srCond})";
				}
			}
		}

		return $parsed;
	}

    function buildBeatmapFilterSQL(array $parsed): array {
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
            $sql .= " AND EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID = ?)";
            $types .= "i";
            $values[] = $dId;
        }
        foreach ($parsed['exDescriptors'] as $dId) {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID = ?)";
            $types .= "i";
            $values[] = $dId;
        }

        foreach ($parsed['srFilters'] as $cond) {
            $sql .= " AND $cond";
        }

        return ['sql' => $sql, 'types' => $types, 'values' => $values];
    }
?>