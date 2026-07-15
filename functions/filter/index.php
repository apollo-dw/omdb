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
        'categories' => ['genre', 'language', 'country', 'descriptor', 'status', 'meta'],
        'customTokens' => []
    ];

    $filterConfig = array_merge($defaultFilterConfig, $filterConfig ?? []);

    $allFilters = [];

    if (in_array('genre', $filterConfig['categories'])) {
        for ($i = 1; $i <= 14; $i++) {
            $genre = getGenre($i);
            if ($genre) $allFilters[] = ['type' => 'genre', 'id' => $i, 'name' => $genre, 'label' => "Genre: $genre"];
        }
    }

    if (in_array('language', $filterConfig['categories'])) {
        for ($i = 1; $i <= 14; $i++) {
            $language = getLanguage($i);
            if ($language) $allFilters[] = ['type' => 'language', 'id' => $i, 'name' => $language, 'label' => "Language: $language"];
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

    if (in_array('meta', $filterConfig['categories']) && $loggedIn) {
        $allFilters[] = ['type' => 'meta', 'id' => 'friends', 'name' => 'Friend Ratings', 'label' => 'System: Friend Ratings'];
        $allFilters[] = ['type' => 'meta', 'id' => 'alreadyRated', 'name' => 'Already Rated Maps', 'label' => 'System: Already Rated Maps'];
    }

    if (in_array('status', $filterConfig['categories'])) {
        $allFilters[] = ['type' => 'status', 'id' => '4', 'name' => 'Loved Maps', 'label' => 'Status: Loved Maps'];
        $allFilters[] = ['type' => 'status', 'id' => '-2', 'name' => 'Graveyard Maps', 'label' => 'Status: Graveyard Maps'];
        $allFilters[] = ['type' => 'status', 'id' => '1,2', 'name' => 'Ranked Maps', 'label' => 'Status: Ranked Maps'];
    }

    if (!empty($filterConfig['customTokens'])) {
        $allFilters = array_merge($allFilters, $filterConfig['customTokens']);
    }

    usort($allFilters, function($a, $b) {
        if ($a['type'] === 'country' && $b['type'] === 'country') {
            return strcmp($a['name'], $b['name']);
        }
        return 0;
    });

    $allFiltersJSON = json_encode($allFilters);
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
            <label><input type="checkbox" id="ratings" value="ratings" checked> Ratings</label>
            <label><input type="checkbox" id="reviews" value="reviews" checked> Reviews</label>
            <label><input type="checkbox" id="review_likes" value="review_likes" checked> Review likes</label>
            <label><input type="checkbox" id="lists" value="lists" checked> Lists</label>
            <label><input type="checkbox" id="list_likes" value="list_likes" checked> List likes</label>
            <label><input type="checkbox" id="ranked_maps" value="ranked_maps" checked> Ranked maps</label>
            <label><input type="checkbox" id="comments" value="comments" checked> Comments</label>
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
            <input type="text" id="filter-input" placeholder="Search descriptors, friends, statuses... or type sr>4" autocomplete="off">
            <div class="filter-popover" id="filter-popover" style="display: none;"></div>
        </div>
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
                                echo "<option value='".urlencode($row["Tag"])."'>".htmlspecialchars($row["Tag"])." ({$row["TagCount"]})</option>";
                            }
                        }
                    ?>
                </select>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // JS versions of the encode/decode funcs in helpers.php
    function encodeTokens(tokens) {
        const parts = [];
        for (const t of tokens) {
            const ex = t.exclude ? '-' : '';
            switch (t.type) {
                case 'genre':
                    parts.push(`g${ex}${t.id}`);
                    break;
                case 'language':
                    parts.push(`l${ex}${t.id}`);
                    break;
                case 'descriptor':
                    parts.push(`d${ex}${t.id}`);
                    break;
                case 'status':
                    parts.push(`s${ex}${String(t.id).replace(/,/g, '~')}`);
                    break;
                case 'country':
                    parts.push(`c${ex}${t.id}`);
                    break;
                case 'meta':
                    parts.push(`m${ex}${t.id}`);
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
                    if (t.ops && t.ops.length > 0) {
                        const prefix = {
                            sr: 'r',
                            cs: 'p',
                            ar: 'a',
                            od: 'o',
                            hp: 'h',
                            length: 't',
                            bpm: 'b',
                            circles: 'x',
                            sliders: 'y',
                            spinners: 'z'
                        }[t.type];
                        
                        const opStr = t.ops.map(o => o.op + o.val).join('');
                        parts.push(`${prefix}${ex}${opStr}`);
                    }
                    break;
                }
            }
        }
        return parts.join(',');
    }

    function decodeTokens(encoded) {
        if (!encoded)
            return [];
        const tokens = [];
        for (const part of encoded.split(',')) {
            const trimmed = part.trim();
            if (!trimmed)
                continue;
            const prefix = trimmed[0];

            let rest = trimmed.slice(1);
            let exclude = false;
            if (rest.startsWith('-')) {
                exclude = true;
                rest = rest.slice(1);
            }

            switch (prefix) {
                case 'g':
                    tokens.push({
                        type: 'genre',
                        id: parseInt(rest, 10),
                        exclude
                    });
                    break;
                case 'l':
                    tokens.push({
                        type: 'language',
                        id: parseInt(rest, 10),
                        exclude
                    });
                    break;
                case 'd':
                    tokens.push({
                        type: 'descriptor',
                        id: parseInt(rest, 10),
                        exclude
                    });
                    break;
                case 's':
                    tokens.push({
                        type: 'status',
                        id: rest.replace(/~/g, ','),
                        exclude
                    });
                    break;
                case 'c':
                    tokens.push({
                        type: 'country',
                        id: rest,
                        exclude
                    });
                    break;
                case 'm':
                    tokens.push({
                        type: 'meta',
                        id: rest,
                        exclude
                    });
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
                    const typeKey = { r: 'sr', p: 'cs', a: 'ar', o: 'od', h: 'hp', t: 'length', b: 'bpm', x: 'circles', y: 'sliders', z: 'spinners' }[prefix];
                    const namePrefix = { r: 'SR: ', p: 'CS: ', a: 'AR: ', o: 'OD: ', h: 'HP: ', t: 'Length: ', b: 'BPM: ', x: 'Circle count: ', y: 'Slider count: ', z: 'Spinner count: ' }[prefix];

                    const ops = [];
                    let rem = rest;
                    const opRx = /^(>=|<=|>|<|=)(\d+(?:\.\d+)?)/;
                    while (rem.length > 0) {
                        const m = rem.match(opRx);
                        if (!m) break;

                        ops.push({
                            op: m[1],
                            val: parseFloat(m[2])
                        });

                        rem = rem.slice(m[0].length);
                    }

                    if (ops.length > 0) {
                        let lower = null;
                        let upper = null;
                        const flip = {
                            '>': '<',
                            '>=': '<=',
                            '<': '>',
                            '<=': '>=',
                            '=': '='
                        };

                        for (const op of ops) {
                            switch (op.op) {
                                case '>':
                                case '>=':
                                    lower = op;
                                    break;
                                case '<':
                                case '<=':
                                    upper = op;
                                    break;
                                case '=':
                                    lower = upper = op;
                                    break;
                            }
                        }

                        let idStr = '';

                        if (lower && upper) {
                            if (lower.op === '=' && upper.op === '=') {
                                idStr = `${typeKey}=${lower.val}`;
                            } else {
                                idStr = `${lower.val}${flip[lower.op]}${typeKey}${upper.op}${upper.val}`;
                            }
                        } else if (lower) {
                            idStr = `${typeKey}${lower.op}${lower.val}`;
                        } else if (upper) {
                            idStr = `${typeKey}${upper.op}${upper.val}`;
                        }

                        tokens.push({
                            type: typeKey,
                            id: idStr,
                            name: namePrefix + idStr,
                            ops,
                            exclude
                        });
                    }
                    break;
                }
            }
        }
        return tokens;
    }

    let activeTokens = [];

    window.getOmdbFilterPayload = function() {
        return {
            order: $('#filter-order').val(),
            year: $('#filter-year').val() || '<?php echo $filterConfig["defaultYear"]; ?>',
            rating: $('#filter-rating').val() || "",
            sr: $('#filter-sr').val() || "",
            cs: $('#filter-cs').val() || "",
            ar: $('#filter-ar').val() || "",
            od: $('#filter-od').val() || "",
            hp: $('#filter-hp').val() || "",
            length: $('#filter-length').val() || "",
            bpm: $('#filter-bpm').val() || "",
            circles: $('#filter-circles').val() || "",
            sliders: $('#filter-sliders').val() || "",
            spinners: $('#filter-spinners').val() || "",
            tag: $('#filter-tag').val() || "",
            tokens: activeTokens,

            // only when showActivityToggles is true otherwise these fall back to true
            ratings: $('#ratings').length ? $('#ratings').is(':checked') : true,
            reviews: $('#reviews').length ? $('#reviews').is(':checked') : true,
            review_likes: $('#review_likes').length ? $('#review_likes').is(':checked') : true,
            lists: $('#lists').length ? $('#lists').is(':checked') : true,
            list_likes: $('#list_likes').length ? $('#list_likes').is(':checked') : true,
            ranked_maps: $('#ranked_maps').length ? $('#ranked_maps').is(':checked') : true,
            comments: $('#comments').length ? $('#comments').is(':checked') : true,
        };
    };

    $(document).ready(function() {
        const lookupMatrix = <?php echo $allFiltersJSON; ?>;
        let debounceTimer = null;

        const $input = $('#filter-input');
        const $popover = $('#filter-popover');
        const $chipsContainer = $('#filter-chips-container');
        const $wrapper = $('#filter-search-wrapper');

        $wrapper.on('click', function(e) {
            if (e.target === this || e.target === $chipsContainer[0]) $input.focus();
        });

        const urlParams = new URLSearchParams(window.location.search);

        const tokensString = urlParams.get('tokens');
        if (tokensString) {
            const raw = decodeTokens(tokensString);
            activeTokens = raw.map(t => {
                if (t.type === 'sr')
                    return t; // SR has name already
                const match = lookupMatrix.find(f => f.type === t.type && f.id == t.id);
                return match ? {
                    ...match,
                    exclude: t.exclude
                } : t;
            }).filter(Boolean);
        }

        $('#filter-order').val(urlParams.get('o') || "1");
        $('#filter-year').val(urlParams.get('y') || '<?php echo $filterConfig["defaultYear"]; ?>');
        $('#filter-rating').val(urlParams.get('r') || "");
        $('#filter-sr').val(urlParams.get('sr') || "");
        $('#filter-cs').val(urlParams.get('p') || "");
        $('#filter-ar').val(urlParams.get('a') || "");
        $('#filter-od').val(urlParams.get('o') || "");
        $('#filter-hp').val(urlParams.get('h') || "");
        $('#filter-length').val(urlParams.get('t') || "");
        $('#filter-bpm').val(urlParams.get('b') || "");
        $('#filter-circles').val(urlParams.get('x') || "");
        $('#filter-sliders').val(urlParams.get('y') || "");
        $('#filter-spinners').val(urlParams.get('z') || "");
        $('#filter-tag').val(urlParams.get('t') || "");

        renderChips();

        if (typeof resetPaginationDisplay === 'function') {
            resetPaginationDisplay(window.getOmdbFilterPayload());
        }

        function pushToken(obj, exclude = false) {
            if (obj && !activeTokens.some(t => t.type === obj.type && t.id == obj.id)) {
                activeTokens.push({
                    ...obj,
                    exclude
                });
            }
        }

        function fireUpdate() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                $(document).trigger('omdbFiltersSubmitted', [window.getOmdbFilterPayload()]);
            }, 100);
        }

        function renderPopover() {
            const query = $input.val().toLowerCase().trim();
            $popover.empty().hide();

            let matches = lookupMatrix.filter(f => {
                // If actively typing, we ONLY want to search for 'usable' descriptors
                // otherwise the tree is drawn later instead
                if (f.type === 'descriptor' && !f.usable) return false;
                
                return (!query || f.label.toLowerCase().includes(query)) &&
                       !activeTokens.some(t => t.id == f.id && t.type === f.type);
            });

            if (matches.length > 0 || !query) {
                const groups = { status: [], meta: [], genre: [], language: [], descriptor: [], country: [] };
                
                matches.forEach(m => {
                    if (groups[m.type]) groups[m.type].push(m);
                });

                if (!query) {
                    groups.descriptor = [];
                } else {
                    Object.keys(groups).forEach(k => {
                        groups[k] = groups[k].slice(0, 15);
                    });
                }

                const catNames = {
                    status: 'Statuses',
                    meta: 'System Options',
                    descriptor: 'Descriptors',
                    genre: 'Genres',
                    language: 'Languages',
                    country: 'Countries'
                };

                const displayOrder = ['status', 'meta', 'descriptor', 'genre', 'language', 'country'];
                let addedSomething = false;

                displayOrder.forEach(cat => {
                    // Below is basically:
                    // If there's no search query and current cat is descriptor, render desc tree
                    // else render them normally
                    if (cat === 'descriptor' && !query) {
                        addedSomething = true;
                        $popover.append(`<div class="popover-category-header">Descriptors Tree</div>`);
                        
                        const allDescriptors = lookupMatrix.filter(f => f.type === 'descriptor');
                        
                        function buildTree(parentID, depth) {
                            let html = '';
                            const children = allDescriptors
                                .filter(d => d.parentID == parentID || (!d.parentID && !parentID))
                                .sort((a, b) => a.name.localeCompare(b.name));

                            children.forEach(child => {
                                const isSelected = activeTokens.some(t => t.type === 'descriptor' && t.id == child.id);
                                
                                let style = `padding: 0.4em 1em 0.4em ${1 + depth * 1.5}em;`;
                                let classes = 'desc-tree-node';
                                
                                if (!child.usable) {
                                    style += ' color: #888; font-style: italic; cursor: default;';
                                } else if (isSelected) {
                                    style += ' color: #555; background-color: #112222; text-decoration: line-through; cursor: default;';
                                } else {
                                    classes += ' popover-item';
                                }

                                html += `<div class="${classes}" style="${style}" data-id="${child.id}">${child.name}</div>`;
                                
                                html += buildTree(child.id, depth + 1);
                            });
                            return html;
                        }

                        const treeHTML = buildTree(null, 0);
                        const $treeContainer = $(`<div>${treeHTML}</div>`);
                        
                        $treeContainer.find('.popover-item').attr('title', 'Left-click to include, Right-click to exclude');
                        $treeContainer.find('.popover-item').on('click contextmenu', function(e) {
                            e.preventDefault(); 
                            e.stopPropagation();
                            
                            const isExclude = e.type === 'contextmenu';
                            const id = $(this).data('id');
                            const item = allDescriptors.find(d => d.id == id);
                            
                            if (item && item.usable) {
                                pushToken(item, isExclude);
                                $input.val('');
                                $popover.hide();
                                renderChips();
                                fireUpdate();
                                $input.focus();
                            }
                        });

                        $popover.append($treeContainer);
                    } else if (groups[cat] && groups[cat].length > 0) {
                        addedSomething = true;
                        $popover.append(`<div class="popover-category-header">${catNames[cat]}</div>`);
                        groups[cat].forEach(item => {
                            const $el = $(`<div class="popover-item" title="Left-click to include, Right-click to exclude">${item.name}</div>`);
                            $el.on('click contextmenu', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                
                                const isExclude = e.type === 'contextmenu';
                                
                                pushToken(item, isExclude);
                                $input.val('');
                                $popover.hide();
                                renderChips();
                                fireUpdate();
                                $input.focus(); 
                            });
                            $popover.append($el);
                        });
                    }
                });

                if (addedSomething) {
                    $popover.show();
                }
            }
        }

        $input.on('input focus click', renderPopover);

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#filter-search-wrapper').length) $popover.hide();
        });

        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = $(this).val().trim().toLowerCase();

                const tokenConfigs = [
                    { key: 'sr', label: 'SR: ' },
                    { key: 'cs', label: 'CS: ' },
                    { key: 'ar', label: 'AR: ' },
                    { key: 'od', label: 'OD: ' },
                    { key: 'hp', label: 'HP: ' },
                    { key: 'length', label: 'Length: ' },
                    { key: 'bpm', label: 'BPM: ' },
                    { key: 'circles', label: 'Circle count: ' },
                    { key: 'sliders', label: 'Slider count: ' },
                    { key: 'spinners', label: 'Spinner count: ' }
                ];

                for (const cfg of tokenConfigs) {
                    const rx = new RegExp(`^(?:(\\d+(?:\\.\\d+)?)\\s*(<=|<|>|>=|=)\\s*)?${cfg.key}(?:(?:\\s*(<=|<|>|>=|=)\\s*(\\d+(?:\\.\\d+)?)))?$`, 'i');
                    const match = val.match(rx);

                    if (match && (match[1] || match[4])) {
                        let parsedOps = [];
                        
                        if (match[1] && match[2]) {
                            const flip = {'<': '>', '<=': '>=', '>': '<', '>=': '<=', '=': '='};
                            parsedOps.push({ op: flip[match[2]], val: match[1] });
                        }
                        if (match[3] && match[4]) {
                            parsedOps.push({ op: match[3], val: match[4] });
                        }

                        pushToken({ 
                            type: cfg.key, 
                            id: val, 
                            name: cfg.label + val, 
                            label: val, 
                            ops: parsedOps 
                        }, false);
                        
                        $(this).val('');
                        $popover.hide();
                        renderChips();
                        fireUpdate();
                        return;
                    }
                }

                if ($popover.is(':visible')) {
                    const firstItem = $popover.find('.popover-item').first();
                    if (firstItem.length) firstItem.click();
                }
            }

            if (e.key === 'Backspace' && $(this).val() === '' && activeTokens.length > 0) {
                activeTokens.pop();
                renderChips();
                fireUpdate();
                renderPopover(); 
            }
        });

        function renderChips() {
            $chipsContainer.find('.filter-chip').remove();
            activeTokens.forEach((tok, idx) => {
                const bg = tok.exclude ? '#402020' : 'DarkSlateGrey';
                const border = tok.exclude ? '#ff6666' : 'white';
                
                let prefix = tok.exclude ? '<b>Exclude</b> ' : '<b>Only</b> ';
                let displayText = tok.name;

                if (tok.type === 'meta') {
                    prefix = ''; 
                    if (tok.id === 'friends') {
                        displayText = tok.exclude ? '<b>Exclude</b> Friends\' Ratings' : '<b>Only</b> Friends\' Ratings';
                    } else if (tok.id === 'alreadyRated') {
                        displayText = tok.exclude ? '<b>Hide</b> Already Rated Maps' : '<b>Only</b> Already Rated Maps';
                    }
                }

                const $chip = $(`<span class="filter-chip" style="background-color: ${bg}; border-color: ${border};">
                    <span class="chip-text" style="cursor:pointer;" title="Click to toggle include/exclude">${prefix}${displayText}</span>
                </span>`);
                
                $chip.find('.chip-text').on('click', function(e) {
                    e.stopPropagation();
                    tok.exclude = !tok.exclude;
                    renderChips();
                    fireUpdate();
                });

                const $rem  = $(`<span class="remove" style="color:${tok.exclude ? '#ff9999' : '#ff6666'};">&times;</span>`).on('click', function(e) {
                    e.stopPropagation();
                    activeTokens.splice(idx, 1);
                    renderChips();
                    fireUpdate();
                    renderPopover(); 
                });
                
                $chip.append($rem);
                $chipsContainer.append($chip);
            });
        }

        $(document).on('change', 'select', function() {
            fireUpdate();
        });

         $(document).on('change', '#ratings, #reviews, #review_likes, #lists, #list_likes, #ranked_maps, #comments', function() {
            fireUpdate();
        });
    });
</script>