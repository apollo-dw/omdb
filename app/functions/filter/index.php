<?php
    // Default Config used by /charts
    $defaultFilterConfig = [
        'sortOptions' => [
            '1' => 'Highest Rated',
            '2' => 'Lowest Rated',
            '3' => 'Most Rated',
            '4' => 'Most Controversial',
            '5' => 'Most Underrated'
        ],
        'defaultYear' => 'all-time',
        'showYear' => true,
        'showRating' => false,
        'showTag' => false,
        'showRelevanceToggle' => false,
        'showActivityToggles' => false,
        'showFilterHelp' => true,
        'showTournamentFilters' => true,
        'categories' => ['genre', 'language', 'country', 'descriptor', 'status', 'meta', 'user', 'tag'],
        'customTokens' => []
    ];

    $filterConfig = array_merge($defaultFilterConfig, $filterConfig ?? []);

    $showTournamentFilters = !empty($filterConfig['showTournamentFilters']);

    // which "prefix:"es are free + their categories
    $scopeAliases = [];
    if (in_array('user', $filterConfig['categories'])) {
        $scopeAliases['user'] = 'user';
        $scopeAliases['mapper'] = 'user';
    }
    if (in_array('tag', $filterConfig['categories'])) {
        $scopeAliases['tag'] = 'tag';
    }
    if ($showTournamentFilters) {
        $scopeAliases['slot'] = 'slot';
        $scopeAliases['tournament'] = 'tournament';
        $scopeAliases['series'] = 'series';
    }

    $allFilters = [];

    if (in_array('genre', $filterConfig['categories'])) {
        for ($i = 1; $i <= 14; $i++) {
            $genre = getGenre($i);
            if ($genre) {
                $allFilters[] = ['type' => 'genre', 'id' => $i, 'name' => $genre, 'label' => "Genre: $genre"];
            }
        }
    }

    if (in_array('language', $filterConfig['categories'])) {
        for ($i = 1; $i <= 14; $i++) {
            $language = getLanguage($i);
            if ($language) {
                $allFilters[] = ['type' => 'language', 'id' => $i, 'name' => $language, 'label' => "Language: $language"];
            }
        }
    }

    if (in_array('country', $filterConfig['categories'])) {
        $countryQuery = $conn->query("SELECT DISTINCT Country FROM mappernames WHERE Country IS NOT NULL AND Country != '' ORDER BY Country ASC");
        while ($cRow = $countryQuery->fetch_assoc()) {
            $code = $cRow['Country'];
            $fullName = getFullCountryName($code) ?? $code;
            $allFilters[] = ['type' => 'country', 'id' => $code, 'name' => $fullName, 'label' => "Country: $fullName"];
        }
    }

    if ($showTournamentFilters) {
        $acronymQuery = $conn->query("SELECT REGEXP_REPLACE(Slot, '[0-9]+$', '') AS Acronym,
                COUNT(DISTINCT tournament_maps.BeatmapID) AS MapCount,
                COUNT(DISTINCT Slot) AS Variants
            FROM tournament_maps
            JOIN beatmaps ON beatmaps.BeatmapID = tournament_maps.BeatmapID
            WHERE Slot REGEXP '^[A-Za-z]+[0-9]*$'
            GROUP BY Acronym
            HAVING Variants > 1
            ORDER BY MapCount DESC");
        $slotAcryonyms = [];
        while ($mRow = $acronymQuery->fetch_assoc()) {
            $value = $mRow['Acronym'] . '*';
            $slotAcryonyms[$mRow['Acronym']] = $value;
            $allFilters[] = [
                'type' => 'slot',
                'id' => $value,
                'name' => formatFilterSlotName($value),
                'label' => "Slot: " . $value . " (any)",
                'count' => (int)$mRow['MapCount'],
                'parentID' => null,
            ];
        }

        $slotQuery = $conn->query("SELECT Slot, COUNT(DISTINCT tournament_maps.BeatmapID) AS MapCount
            FROM tournament_maps
            JOIN beatmaps ON beatmaps.BeatmapID = tournament_maps.BeatmapID
            WHERE Slot IS NOT NULL AND Slot != ''
            GROUP BY Slot
            ORDER BY MapCount DESC");
        while ($sRow = $slotQuery->fetch_assoc()) {
            $acronym = preg_replace('/[0-9]+$/', '', $sRow['Slot']);
            $allFilters[] = [
                'type' => 'slot',
                'id' => $sRow['Slot'],
                'name' => $sRow['Slot'],
                'label' => "Slot: " . $sRow['Slot'],
                'count' => (int)$sRow['MapCount'],
                'parentID' => $slotAcronym[$acronym] ?? null,
            ];
        }
    }

    if (in_array('descriptor', $filterConfig['categories'])) {
        $stmt = $conn->prepare("SELECT DescriptorID AS descriptorID, Name AS name, ParentID AS parentID, Usable AS usable FROM descriptors");
        $stmt->execute();
        $descResult = $stmt->get_result();
        while ($row = $descResult->fetch_assoc()) {
            $allFilters[] = [
                'type' => 'descriptor',
                'id' => $row['descriptorID'],
                'name' => $row['name'],
                'label' => $row['name'],
                'parentID' => $row['parentID'],
                'usable' => $row['usable'] == 1
            ];
        }
    }

    if (in_array('meta', $filterConfig['categories'])) {
        $metaFilters = [
            'rankedmappers' => 'Ranked Mapper Ratings',
            // 'comments' => 'Maps With Comments',
        ];
        if ($loggedIn) {
            $metaFilters += [
                'friends' => 'Friend Ratings',
                // 'mutuals' => 'Mutual Friend Ratings',
                // 'ratedlikeme' => 'Similar User Ratings',
                'alreadyRated' => 'Already Rated Maps',
                // 'disagree' => 'Maps I Disagree On',
            ];
        }
        foreach ($metaFilters as $metaId => $metaName) {
            $allFilters[] = ['type' => 'meta', 'id' => $metaId, 'name' => $metaName, 'label' => "System: {$metaName}"];
        }
    }

    if (in_array('status', $filterConfig['categories'])) {
        $allFilters[] = ['type' => 'status', 'id' => '4', 'name' => 'Loved Maps', 'label' => 'Status: Loved Maps'];
        $allFilters[] = ['type' => 'status', 'id' => '-2', 'name' => 'Graveyard Maps', 'label' => 'Status: Graveyard Maps'];
        $allFilters[] = ['type' => 'status', 'id' => '1,2', 'name' => 'Ranked Maps', 'label' => 'Status: Ranked Maps'];
    }

    if (!empty($filterConfig['customTokens'])) {
        $allFilters = array_merge($allFilters, $filterConfig['customTokens']);
    }

    $preloadedTokens = decodeTokens(postOrGet('tokens', ''));
    if (is_array($preloadedTokens)) {
        $preloadedUserIds = [];
        $preloadedTournamentIds = [];
        $preloadedSeriesIds = [];

        foreach ($preloadedTokens as $preloadedToken) {
            if ($preloadedToken['type'] === 'user') {
                $preloadedUserIds[] = (int)$preloadedToken['id'];
            }
            elseif ($preloadedToken['type'] === 'tag') {
                $allFilters[] = ['type' => 'tag', 'id' => $preloadedToken['id'], 'name' => $preloadedToken['id'], 'label' => "Tag: " . $preloadedToken['id']];
            }
            elseif ($preloadedToken['type'] === 'slot') {
                $slotValue = (string)$preloadedToken['id'];
                $known = false;
                foreach ($allFilters as $existing) {
                    if ($existing['type'] === 'slot' && (string)$existing['id'] === $slotValue) {
                        $known = true;
                        break;
                    }
                }

                if (!$known) {
                    $slotName = formatFilterSlotName($slotValue);
                    $allFilters[] = ['type' => 'slot', 'id' => $slotValue, 'name' => $slotName, 'label' => "Slot: " . $slotName];
                }
            }
            elseif ($preloadedToken['type'] === 'tournament') {
                $preloadedTournamentIds[] = (int)$preloadedToken['id'];
            }
            elseif ($preloadedToken['type'] === 'series') {
                $preloadedSeriesIds[] = (int)$preloadedToken['id'];
            }
        }

        if (!empty($preloadedUserIds)) {
            $ph = implode(',', array_fill(0, count($preloadedUserIds), '?'));
            $stmt = $conn->prepare("SELECT UserID, Username FROM mappernames WHERE UserID IN ($ph)");
            $stmt->bind_param(str_repeat('i', count($preloadedUserIds)), ...$preloadedUserIds);
            $stmt->execute();
            $preloadedUsers = $stmt->get_result();

            while ($preloadedUser = $preloadedUsers->fetch_assoc()) {
                $allFilters[] = [
                    'type' => 'user',
                    'id' => (int)$preloadedUser['UserID'],
                    'name' => $preloadedUser['Username'],
                    'label' => "Mapper: " . $preloadedUser['Username'],
                ];
            }
            $stmt->close();
        }

        if (!empty($preloadedTournamentIds)) {
            $ph = implode(',', array_fill(0, count($preloadedTournamentIds), '?'));
            $stmt = $conn->prepare("SELECT TournamentID, Name, Acronym FROM tournaments WHERE TournamentID IN ($ph)");
            $stmt->bind_param(str_repeat('i', count($preloadedTournamentIds)), ...$preloadedTournamentIds);
            $stmt->execute();
            $preloadedTournaments = $stmt->get_result();

            while ($preloadedTournament = $preloadedTournaments->fetch_assoc()) {
                $acronym = (string)($preloadedTournament['Acronym'] ?? '');
                $allFilters[] = [
                    'type' => 'tournament',
                    'id' => (int)$preloadedTournament['TournamentID'],
                    'name' => $preloadedTournament['Name'],
                    'label' => "Tournament: " . $preloadedTournament['Name'] . ($acronym !== '' ? " ($acronym)" : ""),
                ];
            }
            $stmt->close();
        }

        if (!empty($preloadedSeriesIds)) {
            $ph = implode(',', array_fill(0, count($preloadedSeriesIds), '?'));
            $stmt = $conn->prepare("SELECT SeriesID, Name, Acronym FROM tournament_series WHERE SeriesID IN ($ph)");
            $stmt->bind_param(str_repeat('i', count($preloadedSeriesIds)), ...$preloadedSeriesIds);
            $stmt->execute();
            $preloadedSeries = $stmt->get_result();

            while ($preloadedSeriesRow = $preloadedSeries->fetch_assoc()) {
                $acronym = (string)($preloadedSeriesRow['Acronym'] ?? '');
                $allFilters[] = [
                    'type' => 'series',
                    'id' => (int)$preloadedSeriesRow['SeriesID'],
                    'name' => $preloadedSeriesRow['Name'],
                    'label' => "Series: " . $preloadedSeriesRow['Name'] . ($acronym !== '' ? " ($acronym)" : ""),
                ];
            }
            $stmt->close();
        }
    }

    usort($allFilters, function ($a, $b) {
        if ($a['type'] === 'country' && $b['type'] === 'country') {
            return strcmp($a['name'], $b['name']);
        }
        return 0;
    });

    $asyncCategories = array_values(array_intersect(['user', 'tag'], $filterConfig['categories']));
    if ($showTournamentFilters) {
        $asyncCategories[] = 'tournament';
        $asyncCategories[] = 'series';
    }

    $filterClientConfig = [
        'lookupMatrix' => $allFilters,
        'asyncCategories' => $asyncCategories,
        'scopeAliases' => (object)$scopeAliases,
        'defaultYear' => (string)$filterConfig['defaultYear'],
    ];

    function isActivityChecked($key) {
        $cookieName = 'pref_activity_' . $key;
        if (isset($_COOKIE[$cookieName])) {
            return filter_var($_COOKIE[$cookieName], FILTER_VALIDATE_BOOLEAN);
        }
        return true;
    }
?>

<style>
    .filter-section {
        margin-bottom: 1em;
    }
    .filter-search-box {
        position: relative;
        background-color: var(--main-theme-color-darker);
        border: 1px solid var(--main-theme-text-color);
        padding: 0.25em;
        display: flex;
        flex-wrap: wrap;
        gap: 0.25em;
        align-items: center;
        width: 100%;
        box-sizing: border-box;
    }
    .filter-search-box input {
        background: transparent !important;
        border: none !important;
        color: white;
        outline: none;
        flex: 1;
        min-width: 150px;
        margin: 0;
    }
    .filter-popover {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background-color: var(--main-theme-color);
        border: 1px solid var(--main-theme-text-color);
        max-height: 25em;
        overflow-y: auto;
        z-index: 999;
    }
    .popover-category-header {
        background-color: var(--main-theme-color-even-darker);
        color: var(--main-theme-link-color);
        padding: 0.25em 0.5em;
        font-weight: bold;
        font-size: 0.85em;
    }
    .popover-item {
        padding: 0.4em 1em;
        cursor: pointer;
    }
    .popover-item:hover,
    .popover-item.keyboard-active {
        background-color: var(--main-theme-color-darker);
    }
    .filter-chip {
        padding: 0.1em 0.4em;
        display: inline-flex;
        align-items: center;
        gap: 0.4em;
        font-size: 0.9em;
        border: 1px solid;
    }
    .filter-chip .remove {
        cursor: pointer;
        font-weight: bold;
    }
    .filter-chip .remove:hover {
        color: #ff9999;
    }
    .filter-join {
        font-size: 0.75em;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--main-theme-subtext-color);
        padding: 0 0.1em;
        align-self: center;
    }
    .filter-join.toggleable {
        cursor: pointer;
        color: var(--main-theme-link-color);
        border-bottom: 1px dotted var(--main-theme-link-color);
    }
    .filter-join.toggleable:hover {
        color: white;
        border-bottom-color: white;
    }
    .filter-help {
        font-size: 0.8em;
        color: var(--main-theme-subtext-color);
        margin-top: 0.35em;
    }
    .filter-help summary {
        cursor: pointer;
    }
    .filter-help code {
        color: var(--main-theme-link-color);
    }
    .filter-help code.filter-example {
        cursor: pointer;
        border-bottom: 1px dotted var(--main-theme-link-color);
    }
    .filter-help code.filter-example:hover {
        color: white;
        border-bottom-color: white;
    }
    .popover-item .popover-item-count {
        color: var(--main-theme-subtext-color);
        font-size: 0.85em;
    }
</style>

<div>
    <b>Filters</b>
    <hr>

    <?php if ($filterConfig['showRelevanceToggle']): ?>
        <div class="filter-section">
            <label>
                <input type="checkbox" id="hideLessRelevantCheckbox" checked>
                <span>Hide less-relevant maps (Most rated and/or highest charted, min. 10 shown)</span>
            </label>
        </div>
    <?php endif; ?>

    <?php if ($filterConfig['showActivityToggles']): ?>
        <div class="filter-section flex-row-container" style="flex-wrap:wrap; margin-bottom:1em; flex-direction:column;">
            <b>Activity:</b>
            <label><input type="checkbox" id="ratings" value="ratings" <?php echo isActivityChecked('ratings') ? 'checked' : ''; ?>> Ratings</label>
            <label><input type="checkbox" id="reviews" value="reviews" <?php echo isActivityChecked('reviews') ? 'checked' : ''; ?>> Reviews</label>
            <label><input type="checkbox" id="review_likes" value="review_likes" <?php echo isActivityChecked('review_likes') ? 'checked' : ''; ?>> Review likes</label>
            <label><input type="checkbox" id="lists" value="lists" <?php echo isActivityChecked('lists') ? 'checked' : ''; ?>> Lists</label>
            <label><input type="checkbox" id="list_likes" value="list_likes" <?php echo isActivityChecked('list_likes') ? 'checked' : ''; ?>> List likes</label>
            <label><input type="checkbox" id="ranked_maps" value="ranked_maps" <?php echo isActivityChecked('ranked_maps') ? 'checked' : ''; ?>> Ranked maps</label>
            <label><input type="checkbox" id="comments" value="comments" <?php echo isActivityChecked('comments') ? 'checked' : ''; ?>> Comments</label>
            <label><input type="checkbox" id="nominations" value="nominations" <?php echo isActivityChecked('nominations') ? 'checked' : ''; ?>> Nominations</label>
        </div>
    <?php endif; ?>

    <div class="filter-section flex-row-container" style="align-items: center;">
        <?php if (!empty($filterConfig['sortOptions'])): ?>
            <select id="filter-order" autocomplete="off">
                <?php foreach ($filterConfig['sortOptions'] as $val => $label): ?>
                    <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <?php if ($filterConfig['showYear']): ?>
            <span> maps of </span>
            <select id="filter-year" autocomplete="off">
                <option value="all-time">All Time</option>
                <?php for ($i = 2007; $i <= date('Y'); $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        <?php endif; ?>
    </div>

    <div class="filter-section">
        <div class="filter-search-box" id="filter-search-wrapper">
            <div id="filter-chips-container" style="display: contents;"></div>
            <input type="text" id="filter-input" placeholder="Search descriptors, mappers, tags... or type sr&gt;4" autocomplete="off">
            <div class="filter-popover" id="filter-popover" style="display: none;"></div>
        </div>
        <?php if ($filterConfig['showFilterHelp']): ?>
            <details class="filter-help">
                <summary>Filter tips</summary>
                <b>Enter</b> includes<br>
                <b>Shift+Enter</b> excludes<br>
                <b>Up/Down</b> navigate suggestions<br>
                <b>Escape</b> closes suggestions<br>
                (left/right click in the list include/exclude)<br>
                Click the <b>and</b>/<b>or</b> between two chips of the same kind to change how they combine<br>
                Type a comparison to filter by stats (click one to apply it):
                <?php
                    $statExamples = [
                        'sr>5', '4<ar<=9', 'cs=4', 'od>8', 'hp<6', 'bpm>=180',
                        'length>300', 'circles>500', 'sliders<100', 'spinners=0', 'desc=0', 'credits>0'
                    ];

                    $renderedExamples = [];
                    foreach ($statExamples as $statExample) {
                        $escaped = safe_htmlspecialchars($statExample, ENT_QUOTES);
                        $renderedExamples[] = "<code class='filter-example'>" . $escaped . "</code>";
                    }

                    echo implode(', ', $renderedExamples);
                ?>
                <br>
                <?php
                    $scopeHints = [];
                    foreach (array_values(array_unique(array_values($scopeAliases))) as $scopeCategory) {
                        $scopeHints[] = "<code class='filter-example'>{$scopeCategory}:</code>";
                    }

                    if (!empty($scopeHints)) {
                        $lastHint = array_pop($scopeHints);
                        echo 'Prefix a search with '
                            . (empty($scopeHints) ? $lastHint : implode(', ', $scopeHints) . ' or ' . $lastHint)
                            . ' to search only that category';

                        if ($showTournamentFilters) {
                            echo " (<code class='filter-example'>slot:nm*</code> covers every NM slot)";
                        }
                    }
                ?>
            </details>
        <?php endif; ?>
    </div>

    <?php if ($filterConfig['showRating'] || $filterConfig['showTag']): ?>
        <div class="filter-section flex-row-container">
            <?php if ($filterConfig['showRating']): ?>
                <select id="filter-rating">
                    <option value="">All Ratings</option>
                    <?php for ($i = 0; $i <= 5; $i += 0.5): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            <?php endif; ?>

            <?php if ($filterConfig['showTag']): ?>
                <select id="filter-tag" style="flex-grow: 1;">
                    <option value="">Any Tag</option>
                    <?php
                        if (isset($profileId)) {
                            $stmt = $conn->prepare("SELECT Tag, COUNT(*) AS TagCount FROM rating_tags WHERE UserID = ? GROUP BY Tag ORDER BY TagCount DESC;");
                            $stmt->bind_param('i', $profileId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . urlencode($row["Tag"]) . "'>" . htmlspecialchars($row["Tag"]) . " ({$row["TagCount"]})</option>";
                            }
                        }
                    ?>
                </select>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="/filter/filter.js?v=<?php echo $github_version; ?>" data-config="<?php echo safe_htmlspecialchars(json_encode($filterClientConfig), ENT_QUOTES, 'UTF-8'); ?>"></script>
