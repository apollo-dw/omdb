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

    $stmt = $conn->prepare("SELECT descriptorID, name FROM descriptors WHERE Usable = 1");
    $stmt->execute();
    $descResult = $stmt->get_result();
    while ($row = $descResult->fetch_assoc()) {
        $allFilters[] = ['type' => 'descriptor', 'id' => $row['descriptorID'], 'name' => $row['name'], 'label' => $row['name']];
    }

    $allFiltersJSON = json_encode($allFilters);
?>

<label for="filter-input">Search:</label>
<div id="filterTextboxContainer">
    <div id="chips-wrapper" class="flex-row-container" style="display: inline-flex; flex-wrap: wrap; gap: 4px;"></div>
    <input type="text" id="filter-input" placeholder="genres, languages, descriptors..." autocomplete="off">
    <div id="filter-suggestions-popover" class="popover" style="display: none;"></div>
</div>

<script>

    $(document).ready(function() {
        const filterDictionary = <?php echo $allFiltersJSON; ?>;
        let activeFilters = []; 
        
        const $input = $('#filter-input');
        const $popover = $('#filter-suggestions-popover');
        const $chipsWrapper = $('#chips-wrapper');
        const $container = $('#textboxContainer');

        const urlParams = new URLSearchParams(window.location.search);
        
        const urlG = urlParams.get('g');
        if (urlG) activeFilters.push(filterDictionary.find(f => f.type === 'genre' && f.id == urlG));
        
        const urlL = urlParams.get('l');
        if (urlL) activeFilters.push(filterDictionary.find(f => f.type === 'language' && f.id == urlL));
        
        const urlDesc = urlParams.get('descriptors');
        if (urlDesc) {
            const descNames = urlDesc.split(',');
            descNames.forEach(name => {
                const found = filterDictionary.find(f => f.type === 'descriptor' && f.name === name);
                if (found) activeFilters.push(found);
            });
        }
        
        activeFilters = activeFilters.filter(f => f !== undefined);
        renderChips();

        $container.on('click', () => $input.focus());

        $input.on('input', function() {
            const query = $(this).val().toLowerCase().trim();
            $popover.empty();

            if (query.length === 0) {
                $popover.hide();
                return;
            }

            const matches = filterDictionary.filter(f => 
                f.label.toLowerCase().includes(query) && 
                !activeFilters.some(active => active.id === f.id && active.type === f.type)
            ).slice(0, 15);

            if (matches.length > 0) {
                matches.forEach((match, index) => {
                    const $item = $('<div>', { class: 'descriptor-item', text: match.label });
                    $item.on('click', function(e) {
                        e.stopPropagation();
                        activeFilters.push(match);
                        $input.val('');
                        $popover.hide();
                        renderChips();
                        fireUpdateEvent();
                    });
                    $popover.append($item);
                });
                $popover.show();
            } else {
                $popover.hide();
            }
        });

        $(document).on('click', function(event) {
            if (!$(event.target).closest('#textboxContainer').length) {
                $popover.hide();
            }
        });

        $input.on('keydown', function(e) {
            if (e.key === 'Backspace' && $(this).val() === '') {
                if (activeFilters.length > 0) {
                    activeFilters.pop();
                    renderChips();
                    fireUpdateEvent();
                }
            }
        });

        function renderChips() {
            $chipsWrapper.empty();
            activeFilters.forEach((filter, index) => {
                const $chip = $('<span>', { class: `filter-chip type-${filter.type}`, text: filter.name });
                const $removeBtn = $('<span>', { class: 'remove-chip', html: '&times;' });
                
                $removeBtn.on('click', function(e) {
                    e.stopPropagation();
                    activeFilters.splice(index, 1);
                    renderChips();
                    fireUpdateEvent();
                });

                $chip.append($removeBtn);
                $chipsWrapper.append($chip);
            });
        }

        function fireUpdateEvent() {
            $(document).trigger('omdbFiltersUpdated', [activeFilters]);
        }
    });
</script>