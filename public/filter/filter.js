const omdbFilterConfig = JSON.parse(document.currentScript.dataset.config);

// JS versions of the encode/decode funcs in helpers.php
const OMDB_JOIN_TYPE_CHARS = {
    descriptor: 'd',
    country: 'c',
    user: 'u',
    tag: 'k',
    slot: 'v',
    tournament: 'w',
    series: 'q',
    sr: 'r',
    cs: 'p',
    ar: 'a',
    od: 'o',
    hp: 'h',
    length: 't',
    bpm: 'b',
    circles: 'x',
    sliders: 'y',
    spinners: 'z',
    desc: 'n',
    credits: 'e'
};

// format: [when included, when excluded, phrase when included, phrase when excluded]
const OMDB_META_TEXT = {
    rankedmappers: ['<b>Only</b> Ranked Mapper Ratings', '<b>Exclude</b> Ranked Mapper Ratings', "Ranked mappers' ratings only", "Excluding ranked mappers' ratings"],
    comments: ['<b>Only</b> Maps With Comments', '<b>Hide</b> Maps With Comments', 'Maps with comments only', 'Hiding maps with comments'],
    friends: ["<b>Only</b> Friends' Ratings", "<b>Exclude</b> Friends' Ratings", "Friends' ratings only", "Excluding friends' ratings"],
    mutuals: ["<b>Only</b> Mutual Friends' Ratings", "<b>Exclude</b> Mutual Friends' Ratings", "Mutual friends' ratings only", "Excluding mutual friends' ratings"],
    ratedlikeme: ["<b>Only</b> Similar Users' Ratings", "<b>Exclude</b> Similar Users' Ratings", "Similar users' ratings only", "Excluding similar users' ratings"],
    alreadyRated: ['<b>Only</b> Already Rated Maps', '<b>Hide</b> Already Rated Maps', 'Already rated maps only', 'Hiding already rated maps'],
    disagree: ['<b>Only</b> Maps I Disagree On', '<b>Hide</b> Maps I Disagree On', 'Maps I disagree on only', 'Hiding maps I disagree on']
};

const OMDB_DEFAULT_JOIN_MODES = {
    descriptor: 'and',
    country: 'or',
    user: 'or',
    tag: 'or',
    slot: 'or',
    tournament: 'or',
    series: 'or',
    genre: 'or',
    language: 'or',
    status: 'or',
    sr: 'and',
    cs: 'and',
    ar: 'and',
    od: 'and',
    hp: 'and',
    length: 'and',
    bpm: 'and',
    circles: 'and',
    sliders: 'and',
    spinners: 'and',
    desc: 'and',
    credits: 'and'
};

function encodeFilterTagValue(tag) {
    return encodeURIComponent(tag).replace(/-/g, '%2D');
}

function encodeTokens(tokens) {
    const parts = [];
    for (const t of tokens) {
        const ex = t.exclude ? '-' : '';
        switch (t.type) {
            case 'joinMode': {
                const char = OMDB_JOIN_TYPE_CHARS[t.id];
                if (char)
                    parts.push(`j${char}${t.mode === 'or' ? '1' : '0'}`);
                break;
            }
            case 'user':
                parts.push(`u${ex}${t.id}`);
                break;
            case 'tag':
                parts.push(`k${ex}${encodeFilterTagValue(t.id)}`);
                break;
            case 'slot':
                parts.push(`v${ex}${encodeFilterTagValue(t.id)}`);
                break;
            case 'tournament':
                parts.push(`w${ex}${t.id}`);
                break;
            case 'series':
                parts.push(`q${ex}${t.id}`);
                break;
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
                parts.push(`s${ex}${String(t.id).replace(/,/g, '~').replace(/-/g, '_')}`);
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
            case 'desc':
            case 'credits':
            case 'cs': {
                if (t.ops && t.ops.length > 0) {
                    const prefix = OMDB_JOIN_TYPE_CHARS[t.type];

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
    const joinTypes = {};
    for (const key in OMDB_JOIN_TYPE_CHARS) {
        joinTypes[OMDB_JOIN_TYPE_CHARS[key]] = key;
    }

    for (const part of encoded.split(',')) {
        const trimmed = part.trim();
        if (!trimmed)
            continue;
        const prefix = trimmed[0];

        let rest = trimmed.slice(1);

        if (prefix === 'j') {
            const joinType = joinTypes[rest[0]];
            if (joinType) {
                tokens.push({
                    type: 'joinMode',
                    id: joinType,
                    mode: rest.slice(1) === '1' ? 'or' : 'and'
                });
            }
            continue;
        }

        let exclude = false;
        if (rest.startsWith('-')) {
            exclude = true;
            rest = rest.slice(1);
        }

        switch (prefix) {
            case 'w':
                tokens.push({
                    type: 'tournament',
                    id: parseInt(rest, 10),
                    exclude
                });
                break;
            case 'q':
                tokens.push({
                    type: 'series',
                    id: parseInt(rest, 10),
                    exclude
                });
                break;
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
                    id: rest.replace(/~/g, ',').replace(/_/g, '-'),
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

            case 'u':
                tokens.push({
                    type: 'user',
                    id: parseInt(rest, 10),
                    exclude
                });
                break;

            case 'k':
                tokens.push({
                    type: 'tag',
                    id: decodeURIComponent(rest),
                    exclude
                });
                break;

            case 'v':
                tokens.push({
                    type: 'slot',
                    id: decodeURIComponent(rest),
                    exclude
                });
                break;

            case 'n':
            case 'e':
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
                const typeKey = { r: 'sr', p: 'cs', a: 'ar', o: 'od', h: 'hp', t: 'length', b: 'bpm', x: 'circles', y: 'sliders', z: 'spinners', n: 'desc', e: 'credits' }[prefix];
                const namePrefix = { r: 'SR: ', p: 'CS: ', a: 'AR: ', o: 'OD: ', h: 'HP: ', t: 'Length: ', b: 'BPM: ', x: 'Circle count: ', y: 'Slider count: ', z: 'Spinner count: ', n: 'Descriptor count: ', e: 'Credit count: ' }[prefix];

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

// same as formatFilterSlotName() in helpers.php
function omdbSlotDisplayName(value) {
    const match = /^([A-Za-z]+)\*$/.exec(value);

    return match ? match[1] + ' (any)' : value;
}

function omdbEscapeHtml(value) {
    return $('<div>').text(value === null || value === undefined ? '' : value).html();
}

function omdbEscapeAttr(value) {
    return omdbEscapeHtml(value).replace(/"/g, '&quot;');
}

// Turns filter payload into readable phrases (e.g. "Descriptors: Tech or Alt")
window.describeOmdbFilters = function(payload, options) {
    const skip = (options && options.skip) || [];
    const tokens = (payload.tokens || []).filter(t => t.type !== 'joinMode' && skip.indexOf(t.type) === -1);

    const modes = {};
    (payload.tokens || []).filter(t => t.type === 'joinMode').forEach(t => {
        modes[t.id] = t.mode;
    });

    const labels = {
        meta: '',
        status: 'Status',
        descriptor: 'Descriptors',
        user: 'Mapped by',
        tag: 'Tagged',
        slot: 'Tournament slot',
        tournament: 'Tournament',
        series: 'Tournament series',
        genre: 'Genre',
        language: 'Language',
        country: 'Mapper country',
        sr: 'SR',
        cs: 'CS',
        ar: 'AR',
        od: 'OD',
        hp: 'HP',
        length: 'Length',
        bpm: 'BPM',
        circles: 'Circles',
        sliders: 'Sliders',
        spinners: 'Spinners',
        desc: 'Descriptor count',
        credits: 'Credit count'
    };

    const phrases = [];

    Object.keys(labels).forEach(type => {
        const ofType = tokens.filter(t => t.type === type);
        if (ofType.length === 0)
            return;

        if (type === 'meta') {
            ofType.forEach(t => {
                const text = OMDB_META_TEXT[t.id];
                if (text)
                    phrases.push(t.exclude ? text[3] : text[2]);
            });
            return;
        }

        const naming = t => omdbEscapeHtml(t.ops ? t.id : (t.name || t.id));
        const includes = ofType.filter(t => !t.exclude).map(naming);
        const excludes = ofType.filter(t => t.exclude).map(naming);
        const glue = ' ' + (modes[type] || OMDB_DEFAULT_JOIN_MODES[type] || 'and') + ' ';

        let phrase = labels[type] + ': ';
        if (includes.length > 0)
            phrase += includes.join(glue);
        if (excludes.length > 0)
            phrase += (includes.length > 0 ? ', ' : '') + 'not ' + excludes.join(', ');

        phrases.push(phrase);
    });

    return phrases;
};

let activeTokens = [];
let joinModes = {};

function joinModeFor(type) {
    return joinModes[type] || OMDB_DEFAULT_JOIN_MODES[type] || 'and';
}

function joinModeTokens() {
    return Object.keys(joinModes)
        .filter(type => joinModes[type] !== OMDB_DEFAULT_JOIN_MODES[type])
        .map(type => ({ type: 'joinMode', id: type, mode: joinModes[type] }));
}

window.getOmdbFilterPayload = function() {
    return {
        order: $('#filter-order').val(),
        year: $('#filter-year').val() || omdbFilterConfig.defaultYear,
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
        tokens: activeTokens.concat(joinModeTokens()),

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
    const lookupMatrix = omdbFilterConfig.lookupMatrix;
    const asyncCategories = omdbFilterConfig.asyncCategories;
    const asyncCache = {};
    const asyncPending = {};

    // Acronyms like "CO" are worth looking up
    const ASYNC_MIN_QUERY_LENGTH = 2;
    let debounceTimer = null;

    const $input = $('#filter-input');
    const $popover = $('#filter-popover');
    const $chipsContainer = $('#filter-chips-container');
    const $wrapper = $('#filter-search-wrapper');
    let activePopoverIndex = -1;
    let popoverDismissed = false;

    function clearPopoverSelection() {
        $popover.find('.popover-item.keyboard-active').removeClass('keyboard-active');
        activePopoverIndex = -1;
    }

    function hidePopover(dismiss = false) {
        clearPopoverSelection();
        $popover.hide();
        if (dismiss) popoverDismissed = true;
    }

    function selectPopoverItem(index) {
        const $items = $popover.find('.popover-item');
        if (!$items.length) return;

        clearPopoverSelection();
        activePopoverIndex = (index + $items.length) % $items.length;
        const $active = $items.eq(activePopoverIndex).addClass('keyboard-active');
        $active[0].scrollIntoView({ block: 'nearest' });
    }

    $wrapper.on('click', function(e) {
        if (e.target === this || e.target === $chipsContainer[0]) $input.focus();
    });

    const urlParams = new URLSearchParams(window.location.search);

    const tokensString = urlParams.get('tokens');
    if (tokensString) {
        const raw = decodeTokens(tokensString);

        raw.filter(t => t.type === 'joinMode').forEach(t => {
            joinModes[t.id] = t.mode;
        });

        activeTokens = raw.filter(t => t.type !== 'joinMode').map(t => {
            if (t.name)
                return t; // Range tokens have a name already
            const match = lookupMatrix.find(f => f.type === t.type && f.id == t.id);
            return match ? {
                ...match,
                exclude: t.exclude
            } : t;
        }).filter(Boolean);
    }

    $('#filter-order').val(urlParams.get('o') || "1");
    $('#filter-year').val(urlParams.get('y') || omdbFilterConfig.defaultYear);
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

    function appendPopoverItems(catName, items) {
        $popover.append(`<div class="popover-category-header">${omdbEscapeHtml(catName)}</div>`);

        items.forEach(item => {
            const count = item.count ? ` <span class="popover-item-count">(${item.count})</span>` : '';
            const $el = $(`<div class="popover-item" title="Left-click to include, Right-click (or shift-click) to exclude">${omdbEscapeHtml(item.name)}${count}</div>`);

            $el.on('click contextmenu', function(e) {
                e.preventDefault();
                e.stopPropagation();

                pushToken(item, e.type === 'contextmenu' || e.shiftKey);
                $input.val('');
                renderChips();
                fireUpdate();
                $input.focus();
                hidePopover(true);
            });

            $popover.append($el);
        });
    }

    function appendTreeItems(catName, items, options) {
        const numeric = !!(options && options.numeric);
        const compare = (a, b) => a.name.localeCompare(b.name, undefined, numeric ? { numeric: true } : undefined);

        $popover.append(`<div class="popover-category-header">${omdbEscapeHtml(catName)}</div>`);

        function buildTree(parentID, depth) {
            let html = '';
            const children = items
                .filter(d => d.parentID == parentID || (!d.parentID && !parentID))
                .sort(compare);

            children.forEach(child => {
                const isSelected = activeTokens.some(t => t.type === child.type && t.id == child.id);

                let style = `padding: 0.4em 1em 0.4em ${1 + depth * 1.5}em;`;
                let classes = 'desc-tree-node';

                if (child.usable === false) {
                    style += ' color: #888; font-style: italic; cursor: default;';
                } else if (isSelected) {
                    style += ' color: #555; background-color: #112222; text-decoration: line-through; cursor: default;';
                } else {
                    classes += ' popover-item';
                }

                const count = child.count ? ` <span class="popover-item-count">(${child.count})</span>` : '';

                html += `<div class="${classes}" style="${style}" data-id="${omdbEscapeAttr(child.id)}">${omdbEscapeHtml(child.name)}${count}</div>`;
                html += buildTree(child.id, depth + 1);
            });

            return html;
        }

        const $treeContainer = $(`<div>${buildTree(null, 0)}</div>`);

        $treeContainer.find('.popover-item').attr('title', 'Left-click to include, Right-click (or shift-click) to exclude');
        $treeContainer.find('.popover-item').on('click contextmenu', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const id = $(this).data('id');
            const item = items.find(d => d.id == id);

            if (item && item.usable !== false) {
                pushToken(item, e.type === 'contextmenu' || e.shiftKey);
                $input.val('');
                renderChips();
                fireUpdate();
                $input.focus();
                hidePopover(true);
            }
        });

        $popover.append($treeContainer);
    }

    // "user:foo" / "tag:foo" / "slot:foo" narrows the search down to one of the categories in lookup.php
    const SCOPE_ALIASES = omdbFilterConfig.scopeAliases;

    function parseScopedQuery(raw) {
        const match = raw.match(/^([a-z]+)\s*:\s*(.*)$/i);
        const scope = match ? SCOPE_ALIASES[match[1].toLowerCase()] : null;

        if (!scope)
            return { scope: null, query: raw };

        return { scope: scope, query: match[2].trim() };
    }

    // Every async cat comes back in 1 response
    function asyncResults(query) {
        if (asyncCache[query])
            return asyncCache[query];

        if (!asyncPending[query]) {
            asyncPending[query] = true;
            $.getJSON('/filter/lookup.php', { type: asyncCategories.join(','), q: query })
                .done(function(data) {
                    asyncCache[query] = (data && data.results) || [];
                    const current = parseScopedQuery($input.val().trim());
                    if (!popoverDismissed && current.query.toLowerCase() === query)
                        renderPopover();
                })
                .always(function() {
                    delete asyncPending[query];
                });
        }

        return null;
    }

    function renderPopover() {
        const scoped = parseScopedQuery($input.val().trim());
        const query = scoped.query.toLowerCase();
        hidePopover();
        $popover.empty();

        let matches = lookupMatrix.filter(f => {
            // If actively typing, we ONLY want to search for 'usable' descriptors
            // otherwise the tree is drawn later instead
            if (f.type === 'descriptor' && !f.usable) return false;
            if (asyncCategories.indexOf(f.type) !== -1) return false;
            if (scoped.scope && f.type !== scoped.scope) return false;

            return (!query || f.label.toLowerCase().includes(query)) &&
                   !activeTokens.some(t => t.id == f.id && t.type === f.type);
        });

        // Mappers, tags and slots live server-side, so a query that matches nothing locally from the other cats still has to reach lookup before we give up on it
        const mayLookUp = asyncCategories.length > 0 && (scoped.scope || query.length >= ASYNC_MIN_QUERY_LENGTH);

        if (matches.length > 0 || !query || scoped.scope || mayLookUp) {
            const groups = { status: [], meta: [], genre: [], language: [], descriptor: [], country: [], slot: [] };

            matches.forEach(m => {
                if (groups[m.type]) groups[m.type].push(m);
            });

            if (!query) {
                groups.descriptor = [];
                groups.slot = [];
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
                country: 'Countries',
                user: 'Mappers',
                tag: 'Tags',
                slot: 'Tournament Slots',
                tournament: 'Tournaments',
                series: 'Tournament Series'
            };

            const displayOrder = ['status', 'meta', 'descriptor', 'user', 'tag', 'slot', 'tournament', 'series', 'genre', 'language', 'country'];
            let addedSomething = false;

            displayOrder.forEach(cat => {
                // Below is basically:
                // If it's a lookup cat then render its part of the response
                // Else if there's no search query and current cat is descriptor, render desc tree
                // else render them normally
                if (asyncCategories.indexOf(cat) !== -1) {
                    if (scoped.scope && scoped.scope !== cat)
                        return;
                    if (!scoped.scope && query.length < ASYNC_MIN_QUERY_LENGTH)
                        return;
                    if (scoped.scope === cat && cat === 'user' && query.length === 0)
                        return;

                    const results = asyncResults(query);
                    if (!results)
                        return;

                    const items = results.filter(r => r.type === cat && !activeTokens.some(t => t.type === r.type && t.id == r.id));
                    if (items.length === 0)
                        return;

                    addedSomething = true;
                    appendPopoverItems(catNames[cat], items);
                } else if (cat === 'descriptor' && !query && !scoped.scope) {
                    const descriptorItems = lookupMatrix.filter(f => f.type === 'descriptor');
                    if (descriptorItems.length === 0)
                        return;

                    addedSomething = true;
                    appendTreeItems('Descriptors Tree', descriptorItems);
                } else if (cat === 'slot' && !query && (!scoped.scope || scoped.scope === 'slot')) {
                    const slotItems = lookupMatrix.filter(f => f.type === 'slot');
                    if (slotItems.length === 0)
                        return;

                    addedSomething = true;
                    appendTreeItems(catNames.slot, slotItems, { numeric: true });
                } else if (groups[cat] && groups[cat].length > 0) {
                    addedSomething = true;
                    appendPopoverItems(catNames[cat], groups[cat]);
                }
            });

            if (addedSomething) {
                $popover.show();
            }
        }
    }

    $input.on('input focus click', function() {
        popoverDismissed = false;
        renderPopover();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#filter-search-wrapper').length) hidePopover(true);
    });

    const statTokenConfigs = [
        { key: 'sr', label: 'SR: ' },
        { key: 'cs', label: 'CS: ' },
        { key: 'ar', label: 'AR: ' },
        { key: 'od', label: 'OD: ' },
        { key: 'hp', label: 'HP: ' },
        { key: 'length', label: 'Length: ' },
        { key: 'bpm', label: 'BPM: ' },
        { key: 'circles', label: 'Circle count: ' },
        { key: 'sliders', label: 'Slider count: ' },
        { key: 'spinners', label: 'Spinner count: ' },
        { key: 'desc', label: 'Descriptor count: ' },
        { key: 'credits', label: 'Credit count: ' }
    ];

    function applyStatQuery(rawValue, exclude) {
        const val = rawValue.trim().toLowerCase();

        for (const cfg of statTokenConfigs) {
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
                }, exclude);

                renderChips();
                fireUpdate();
                return true;
            }
        }

        return false;
    }

    $(document).on('click', '.filter-help code.filter-example', function(e) {
        const example = $(this).text().trim();

        if (example.slice(-1) === ':' || /^(user|mapper|tag|slot)\s*:/i.test(example)) {
            $input.val(example).trigger('focus').trigger('input');
            return;
        }

        if (applyStatQuery(example, e.shiftKey)) {
            $input.val('');
            hidePopover(true);
        }
    });

    $input.on('keydown', function(e) {
        if (e.key === 'Escape') {
            if ($popover.is(':visible')) e.preventDefault();
            hidePopover(true);
            return;
        }

        if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && $popover.is(':visible')) {
            const count = $popover.find('.popover-item').length;
            if (count) {
                e.preventDefault();
                selectPopoverItem(e.key === 'ArrowDown'
                    ? activePopoverIndex + 1
                    : (activePopoverIndex === -1 ? count - 1 : activePopoverIndex - 1));
            }
            return;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            const exclude = e.shiftKey;

            if (applyStatQuery($(this).val(), exclude)) {
                $(this).val('');
                hidePopover(true);
                return;
            }

            if ($popover.is(':visible')) {
                const $items = $popover.find('.popover-item');
                const $chosen = $items.eq(activePopoverIndex === -1 ? 0 : activePopoverIndex);
                if ($chosen.length) {
                    $chosen.trigger($.Event('click', { shiftKey: exclude }));
                    return;
                }
            }

            // A "tag:"/"slot:" search that matched nothing is still a valid filter since both are free text
            const scoped = parseScopedQuery($(this).val().trim());
            if ((scoped.scope === 'tag' || scoped.scope === 'slot') && scoped.query !== '') {
                const scopeLabel = scoped.scope === 'tag' ? 'Tag: ' : 'Slot: ';
                const scopeName = scoped.scope === 'slot' ? omdbSlotDisplayName(scoped.query) : scoped.query;
                pushToken({
                    type: scoped.scope,
                    id: scoped.query,
                    name: scopeName,
                    label: scopeLabel + scopeName
                }, exclude);

                $(this).val('');
                hidePopover(true);
                renderChips();
                fireUpdate();
            }
        }

        if (e.key === 'Backspace' && $(this).val() === '' && activeTokens.length > 0) {
            activeTokens.pop();
            renderChips();
            fireUpdate();
            renderPopover();
        }
    });

    function groupedTokens() {
        const order = [];
        const groups = {};

        activeTokens.forEach(tok => {
            if (!groups[tok.type]) {
                groups[tok.type] = [];
                order.push(tok.type);
            }
            groups[tok.type].push(tok);
        });

        const ordered = [];
        order.forEach(type => {
            groups[type].filter(t => !t.exclude).forEach(t => ordered.push(t));
            groups[type].filter(t => t.exclude).forEach(t => ordered.push(t));
        });

        return ordered;
    }

    function renderChips() {
        $chipsContainer.find('.filter-chip, .filter-join').remove();

        const ordered = groupedTokens();

        ordered.forEach((tok, idx) => {
            const previous = idx > 0 ? ordered[idx - 1] : null;

            if (previous) {
                const sameGroup = previous.type === tok.type && !previous.exclude && !tok.exclude;
                const toggleable = sameGroup && OMDB_JOIN_TYPE_CHARS[tok.type] !== undefined;

                if (toggleable) {
                    const mode = joinModeFor(tok.type);
                    const $join = $(`<span class="filter-join toggleable" title="Click to switch between AND and OR">${mode}</span>`);
                    $join.on('click', function(e) {
                        e.stopPropagation();
                        joinModes[tok.type] = mode === 'or' ? 'and' : 'or';
                        renderChips();
                        fireUpdate();
                    });
                    $chipsContainer.append($join);
                } else {
                    $chipsContainer.append(`<span class="filter-join">${sameGroup ? joinModeFor(tok.type) : 'and'}</span>`);
                }
            }

            const bg = tok.exclude ? '#402020' : 'DarkSlateGrey';
            const border = tok.exclude ? '#ff6666' : 'white';

            let prefix = tok.exclude ? '<b>Exclude</b> ' : '<b>Only</b> ';
            let displayText = omdbEscapeHtml(tok.name || tok.id);

            if (tok.type === 'meta') {
                prefix = '';
                const text = OMDB_META_TEXT[tok.id];
                if (text) {
                    displayText = tok.exclude ? text[1] : text[0];
                }
            } else if (tok.type === 'user') {
                prefix = tok.exclude ? '<b>Exclude</b> maps by ' : '<b>Only</b> maps by ';
            } else if (tok.type === 'tag') {
                prefix = tok.exclude ? '<b>Exclude</b> maps tagged ' : '<b>Only</b> maps tagged ';
            } else if (tok.type === 'slot') {
                prefix = tok.exclude ? '<b>Exclude</b> maps in slot ' : '<b>Only</b> maps in slot ';
            } else if (tok.type === 'tournament') {
                prefix = tok.exclude ? '<b>Exclude</b> maps from ' : '<b>Only</b> maps from ';
            } else if (tok.type === 'series') {
                prefix = tok.exclude ? '<b>Exclude</b> maps from series ' : '<b>Only</b> maps from series ';
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
                const position = activeTokens.indexOf(tok);
                if (position !== -1)
                    activeTokens.splice(position, 1);
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
