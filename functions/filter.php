<?php
    $allFilters = [];
    for ($i = 1; $i <= 14; $i++) {
        $genre = getGenre($i);
        if ($genre) $allFilters[] = ['type' => 'genre', 'id' => $i, 'name' => $genre, 'label' => "Genre: $genre"];
    }

    for ($i = 1; $i <= 14; $i++) {
        $language = getLanguage($i);
        if ($language) $allFilters[] = ['type' => 'language', 'id' => $i, 'name' => $language, 'label' => "Language: $language"];
    }

    $countryQuery = $conn->query("SELECT DISTINCT Country FROM mappernames WHERE Country IS NOT NULL AND Country != '' ORDER BY Country ASC");
    while ($cRow = $countryQuery->fetch_assoc()) {
        $code = $cRow['Country'];
        $fullName = getFullCountryName($code) ?? $code;
        $allFilters[] = [
            'type' => 'country',
            'id' => $code,
            'name' => $fullName,
            'label' => "Country: $fullName",
        ];
    }

    $stmt = $conn->prepare("SELECT descriptorID, name FROM descriptors WHERE Usable = 1");
    $stmt->execute();
    $descResult = $stmt->get_result();
    while ($row = $descResult->fetch_assoc()) {
        $allFilters[] = ['type' => 'descriptor', 'id' => $row['descriptorID'], 'name' => $row['name'], 'label' => $row['name']];
    }

    usort($allFilters, function($a, $b) {
        if ($a['type'] === 'country' && $b['type'] === 'country') {
            return strcmp($a['name'], $b['name']);
        }
        return 0;
    });

    $allFiltersJSON = json_encode($allFilters);

    $uri = $_SERVER['REQUEST_URI'];
    $isRatingsPage = strpos($uri, 'ratings') !== false;
    $isProfilePage = strpos($uri, 'profile') !== false && !$isRatingsPage;
    $showExtraFilters = $isRatingsPage || $isProfilePage;
?>

<style>
    .filter-section {
        margin-bottom: 1em;
    }
    .filter-search-box {
        position: relative;
        background-color: #203838;
        border: 1px solid white;
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
        background-color: DarkSlateGrey;
        border: 1px solid white;
        max-height: 25em;
        overflow-y: auto;
        z-index: 999;
    }
    .popover-category-header {
        background-color: #182828;
        color: #6fffea;
        padding: 0.25em 0.5em;
        font-weight: bold;
        font-size: 0.85em;
    }
    .popover-item {
        padding: 0.4em 1em;
        cursor: pointer;
    }
    .popover-item:hover {
        background-color: #203838;
    }
    .filter-chip {
        background-color: DarkSlateGrey;
        border: 1px solid white;
        padding: 0.1em 0.4em;
        display: inline-flex;
        align-items: center;
        gap: 0.4em;
        font-size: 0.9em;
    }
    .filter-chip .remove {
        cursor: pointer;
        color: #ff6666;
        font-weight: bold;
    }
    .filter-checkbox-group label {
        display: block;
        margin-bottom: 0.25em;
        cursor: pointer;
    }
</style>

<div>
    <b>Filters</b>
    <hr>
    <div class="filter-section flex-row-container" style="align-items: center;">
        <select id="filter-order" autocomplete="off">
            <?php if ($showExtraFilters): ?>
                <option value="0">Latest</option>
                <option value="1">Oldest</option>
                <option value="2">Highest rated</option>
                <option value="3">Lowest rated</option>
            <?php else: ?>
                <option value="1">Highest Rated</option>
                <option value="2">Lowest Rated</option>
                <option value="3">Most Rated</option>
                <option value="4">Most Controversial</option>
                <option value="5">Most Underrated</option>
            <?php endif; ?>
        </select>
        <span> maps of </span>
        <select id="filter-year" autocomplete="off">
            <option value="all-time">All Time</option>
            <?php for ($i = 2007; $i <= date('Y'); $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="filter-section">
        <div class="filter-search-box" id="filter-search-wrapper">
            <div id="filter-chips-container" style="display: contents;"></div>
            <input type="text" id="filter-input" placeholder="Search genres, descriptors, countries..." autocomplete="off">
            <div class="filter-popover" id="filter-popover" style="display: none;"></div>
        </div>
    </div>

    <?php if ($showExtraFilters): ?>
        <div class="filter-section flex-row-container">
            <select id="filter-rating">
                <option value="">All Scores</option>
                <?php for ($i = 0; $i <= 5; $i += 0.5): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>

            <select id="filter-sr">
                <option value="">All Star Ratings</option>
                <?php for ($i = 0; $i < 12; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?>★ - <?php echo ($i + 1); ?>★</option>
                <?php endfor; ?>
                <option value="12">12★+</option>
            </select>

            <select id="filter-tag" style="flex-grow: 1;">
                <option value="">Any Tag</option>
                <?php
                    if (isset($profileId)) {
                        $stmt = $conn->prepare("SELECT Tag, COUNT(*) AS TagCount FROM rating_tags WHERE UserID = ? GROUP BY Tag ORDER BY TagCount DESC;");
                        $stmt->bind_param('i', $profileId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='".urlencode($row["Tag"])."'>".htmlspecialchars($row["Tag"])." ({$row["TagCount"]})</option>";
                        }
                    }
                ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="filter-section filter-checkbox-group">
        <?php if ($loggedIn): ?>
            <label><input type="checkbox" id="filter-friends"> Only include friend ratings</label>
        <?php endif; ?>

        <?php if (!$showExtraFilters && $loggedIn): ?>
            <label><input type="checkbox" id="filter-hide-rated"> Hide already rated maps</label>
        <?php endif; ?>

        <br><span>Exclude:</span><br>
        <label><input type="checkbox" id="filter-ex-loved"> Loved maps</label>
        <label><input type="checkbox" id="filter-ex-graveyard"> Graveyard maps</label>
        <label><input type="checkbox" id="filter-ex-ranked"> Ranked maps</label>
    </div>
</div>

<script>
    $(document).ready(function() {
        const lookupMatrix = <?php echo $allFiltersJSON; ?>;
        let activeTokens = [];
        let debounceTimer = null;

        const $input = $('#filter-input');
        const $popover = $('#filter-popover');
        const $chipsContainer = $('#filter-chips-container');
        const $wrapper = $('#filter-search-wrapper');

        $wrapper.on('click', function(e) {
            if (e.target === this || e.target === $chipsContainer[0]) $input.focus();
        });

        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.get('g'))
            pushToken(lookupMatrix.find(f => f.type === 'genre'    && f.id == urlParams.get('g')));
        if (urlParams.get('l'))
            pushToken(lookupMatrix.find(f => f.type === 'language' && f.id == urlParams.get('l')));
        if (urlParams.get('c'))
            pushToken(lookupMatrix.find(f => f.type === 'country'  && f.id === decodeURIComponent(urlParams.get('c'))));

        const descParam = urlParams.get('descriptors');
        if (descParam) {
            descParam.split(',').forEach(name => {
                pushToken(lookupMatrix.find(f => f.type === 'descriptor' && f.name === name));
            });
        }

        if (urlParams.get('o'))
            $('#filter-order').val(urlParams.get('o'));
        if (urlParams.get('y'))
            $('#filter-year').val(urlParams.get('y'));
        if (urlParams.get('r'))
            $('#filter-rating').val(urlParams.get('r'));
        if (urlParams.get('sr'))
            $('#filter-sr').val(urlParams.get('sr'));
        if (urlParams.get('t'))
            $('#filter-tag').val(urlParams.get('t'));
        if (urlParams.get('f') === 'true')
            $('#filter-friends').prop('checked', true);
        if (urlParams.get('alreadyRated') === 'true')
            $('#filter-hide-rated').prop('checked', true);
        if (urlParams.get('excludeLoved') === 'true')
            $('#filter-ex-loved').prop('checked', true);
        if (urlParams.get('excludeGraveyard') === 'true')
            $('#filter-ex-graveyard').prop('checked', true);
        if (urlParams.get('excludeRanked') === 'true')
            $('#filter-ex-ranked').prop('checked', true);

        renderChips();

        function pushToken(obj) {
            if (obj && !activeTokens.some(t => t.type === obj.type && t.id === obj.id))
                activeTokens.push(obj);
        }

        function fireUpdate() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                $(document).trigger('omdbFiltersSubmitted', [window.getOmdbFilterPayload()]);
            }, 100);
        }

        $input.on('input', function() {
            const query = $(this).val().toLowerCase().trim();
            $popover.empty().hide();
            if (!query) return;

            const dynamicMatches = lookupMatrix.filter(f =>
                f.label.toLowerCase().includes(query) &&
                !activeTokens.some(t => t.id === f.id && t.type === f.type)
            ).slice(0, 20);

            if (dynamicMatches.length > 0) {
                const groups = { genre: [], descriptor: [], language: [], country: [] };
                dynamicMatches.forEach(m => groups[m.type]?.push(m));

                Object.keys(groups).forEach(cat => {
                    if (groups[cat].length > 0) {
                        $popover.append(`<div class="popover-category-header">${cat.charAt(0).toUpperCase() + cat.slice(1)}s</div>`);
                        groups[cat].forEach(item => {
                            const $el = $(`<div class="popover-item">${item.name}</div>`);
                            $el.on('click', function(e) {
                                e.stopPropagation();
                                activeTokens.push(item);
                                $input.val('');
                                $popover.hide();
                                renderChips();
                                fireUpdate();
                            });
                            $popover.append($el);
                        });
                    }
                });
                $popover.show();
            }
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#filter-search-wrapper').length) $popover.hide();
        });

        $input.on('keydown', function(e) {
            if (e.key === 'Backspace' && $(this).val() === '' && activeTokens.length > 0) {
                activeTokens.pop();
                renderChips();
                fireUpdate();
            }
        });

        function renderChips() {
            $chipsContainer.find('.filter-chip').remove();
            activeTokens.forEach((tok, idx) => {
                const $chip = $(`<span class="filter-chip"><span>${tok.name}</span></span>`);
                const $rem  = $(`<span class="remove">&times;</span>`).on('click', function(e) {
                    e.stopPropagation();
                    activeTokens.splice(idx, 1);
                    renderChips();
                    fireUpdate();
                });
                $chip.append($rem);
                $chipsContainer.append($chip);
            });
        }

        $(document).on('change',
            '#filter-order, #filter-year, #filter-rating, #filter-sr, #filter-tag, ' +
            '#filter-friends, #filter-hide-rated, ' +
            '#filter-ex-loved, #filter-ex-graveyard, #filter-ex-ranked',
            function() {
                fireUpdate();
            }
        );

        window.getOmdbFilterPayload = function() {
            return {
                order: $('#filter-order').val(),
                year: $('#filter-year').val(),
                rating: $('#filter-rating').val() | "",
                sr: $('#filter-sr').val() || "",
                tag: $('#filter-tag').val() || "",
                friends: $('#filter-friends').is(':checked'),
                hideRated: $('#filter-hide-rated').is(':checked'),
                exLoved: $('#filter-ex-loved').is(':checked'),
                exGraveyard: $('#filter-ex-graveyard').is(':checked'),
                exRanked: $('#filter-ex-ranked').is(':checked'),
                tokens: activeTokens
            };
        };
    });
</script>