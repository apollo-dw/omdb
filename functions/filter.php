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
        'showYear' => true,
        'showRating' => false,
        'showSR' => false,
        'showTag' => false,
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

    if (in_array('meta', $filterConfig['categories'])) {
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
    .filter-section { margin-bottom: 1em; }
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
    .popover-item { padding: 0.4em 1em; cursor: pointer; }
    .popover-item:hover { background-color: #203838; }
    
    .filter-chip {
        padding: 0.1em 0.4em;
        display: inline-flex;
        align-items: center;
        gap: 0.4em;
        font-size: 0.9em;
        border: 1px solid;
    }
    .filter-chip .remove { cursor: pointer; font-weight: bold; }
    .filter-chip .remove:hover { color: #ff9999; }
</style>

<div>
    <b>Filters</b>
    <hr>
    
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

    <?php if ($filterConfig['showRating'] || $filterConfig['showSR'] || $filterConfig['showTag']): ?>
        <div class="filter-section flex-row-container">
            <?php if ($filterConfig['showRating']): ?>
                <select id="filter-rating">
                    <option value="">All Scores</option>
                    <?php for ($i = 0; $i <= 5; $i += 0.5): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            <?php endif; ?>

            <?php if ($filterConfig['showSR']): ?>
                <select id="filter-sr">
                    <option value="">All Star Ratings</option>
                    <?php for ($i = 0; $i < 12; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?>★ - <?php echo ($i + 1); ?>★</option>
                    <?php endfor; ?>
                    <option value="12">12★+</option>
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
    let activeTokens = [];

    window.getOmdbFilterPayload = function() {
        return {
            order: $('#filter-order').val(),
            year: $('#filter-year').val(),
            rating: $('#filter-rating').val() || "",
            sr: $('#filter-sr').val() || "",
            tag: $('#filter-tag').val() || "",
            tokens: activeTokens 
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
            try {
                let decoded = tokensString;
                if (decoded.includes('%')) decoded = decodeURIComponent(decoded); 
                activeTokens = JSON.parse(decoded);
            } catch(e) { console.error("Could not parse tokens from URL", e); }
        } else {
            if (urlParams.get('g')) urlParams.get('g').split(',').forEach(id => pushToken(lookupMatrix.find(f => f.type === 'genre' && f.id == id)));
            if (urlParams.get('eg')) urlParams.get('eg').split(',').forEach(id => pushToken(lookupMatrix.find(f => f.type === 'genre' && f.id == id), true));
            if (urlParams.get('l')) urlParams.get('l').split(',').forEach(id => pushToken(lookupMatrix.find(f => f.type === 'language' && f.id == id)));
            if (urlParams.get('el')) urlParams.get('el').split(',').forEach(id => pushToken(lookupMatrix.find(f => f.type === 'language' && f.id == id), true));
            if (urlParams.get('c')) urlParams.get('c').split(',').forEach(id => pushToken(lookupMatrix.find(f => f.type === 'country' && f.id === decodeURIComponent(id))));
            if (urlParams.get('ec')) urlParams.get('ec').split(',').forEach(id => pushToken(lookupMatrix.find(f => f.type === 'country' && f.id === decodeURIComponent(id)), true));

            const descParam = urlParams.get('descriptors');
            if (descParam) {
                descParam.split(',').forEach(name => {
                    const isExclude = name.startsWith('-');
                    const cleanName = isExclude ? name.substring(1) : name;
                    pushToken(lookupMatrix.find(f => f.type === 'descriptor' && f.name === cleanName), isExclude);
                });
            }
        }

        $('#filter-order').val(urlParams.get('o') || "1");
        $('#filter-year').val("<?php echo isset($year) ? $year : 2026; ?>");
        $('#filter-rating').val(urlParams.get('r') || "");
        $('#filter-sr').val(urlParams.get('sr') || "");
        $('#filter-tag').val(urlParams.get('t') || "");

        renderChips();

        if (typeof resetPaginationDisplay === 'function') {
            resetPaginationDisplay(window.getOmdbFilterPayload());
        }

        function pushToken(obj, exclude = false) {
            if (obj && !activeTokens.some(t => t.type === obj.type && t.id == obj.id)) {
                activeTokens.push({...obj, exclude: exclude});
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
                        
                        $treeContainer.find('.popover-item').on('click', function(e) {
                            e.stopPropagation();
                            const id = $(this).data('id');
                            const item = allDescriptors.find(d => d.id == id);
                            if (item && item.usable) {
                                pushToken(item, false);
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
                            const $el = $(`<div class="popover-item">${item.name}</div>`);
                            $el.on('click', function(e) {
                                e.stopPropagation();
                                pushToken(item, false);
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
                const srMatch = val.match(/^(?:(\d+(?:\.\d+)?)\s*(<=|<|>|>=|=)\s*)?sr(?:(?:\s*(<=|<|>|>=|=)\s*(\d+(?:\.\d+)?)))?$/i);
                if (srMatch && (srMatch[1] || srMatch[4])) {
                    let parsedOps = [];
                    
                    if (srMatch[1] && srMatch[2]) {
                        const flip = {'<': '>', '<=': '>=', '>': '<', '>=': '<=', '=': '='};
                        parsedOps.push({ op: flip[srMatch[2]], val: srMatch[1] });
                    }
                    if (srMatch[3] && srMatch[4]) {
                        parsedOps.push({ op: srMatch[3], val: srMatch[4] });
                    }

                    pushToken({ type: 'sr', id: val, name: 'SR: ' + val, label: val, ops: parsedOps }, false);
                    
                    $(this).val('');
                    $popover.hide();
                    renderChips();
                    fireUpdate();
                    return;
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
                
                let prefix = tok.exclude ? '<b>NOT</b> ' : '';
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

        $(document).on('change', 'select', function() { fireUpdate(); });
    });
</script>