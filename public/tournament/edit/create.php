<?php
    require "../../base.php";
    $PageTitle = "Edit tournament";
    require "../../header.php";

    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    $tournamentId = $_GET["id"] ?? null;
    $seriesId = $_GET["seriesID"] ?? null;

    if ($tournamentId === null && $seriesId === null) {
        http_response_code(400);
        exit();
    }

    if ($seriesId !== null) {
        $stmt = $conn->prepare("SELECT * FROM tournament_series WHERE SeriesID = ?;");
        $stmt->bind_param("i", $seriesId);
        $stmt->execute();
        $series = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (is_null($series)) {
            http_response_code(400);
            exit();
        }
    }

    if ($tournamentId !== null) {
        $stmt = $conn->prepare("SELECT * FROM tournaments WHERE TournamentID = ?;");
        $stmt->bind_param("i", $tournamentId);
        $stmt->execute();
        $tournament = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (is_null($tournament)) {
            http_response_code(404);
            exit();
        }

        $stmt = $conn->prepare("SELECT EditID FROM tournament_edit_requests WHERE TournamentID = ? AND Status = 'Pending' LIMIT 1;");
        $stmt->bind_param("i", $tournamentId);
        $stmt->execute();
        $pendingEdit = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($pendingEdit) {
            echo "pending";
            http_response_code(400);
            exit();
        }

        $stmt = $conn->prepare("SELECT * FROM tournament_stages WHERE TournamentID = ? ORDER BY SortOrder ASC;");
        $stmt->bind_param("i", $tournamentId);
        $stmt->execute();
        $stageResults = $stmt->get_result();

        while ($stage = $stageResults->fetch_assoc()) {
            $stageId = $stage['StageID'];
            $stage['Maps'] = [];

            $mapStmt = $conn->prepare("
                SELECT
                    tm.BeatmapID,
                    tm.Slot,
                    tm.SortOrder,
                    tm.IsCustom,
                    b.SetID,
                    b.DifficultyName,
                    s.Artist,
                    s.Title
                FROM tournament_maps tm
                LEFT JOIN beatmaps b ON tm.BeatmapID = b.BeatmapID
                LEFT JOIN beatmapsets s ON b.SetID = s.SetID
                WHERE tm.StageID = ?
                ORDER BY tm.SortOrder ASC;
            ");
            $mapStmt->bind_param("i", $stageId);
            $mapStmt->execute();
            $mapResults = $mapStmt->get_result();

            while ($map = $mapResults->fetch_assoc()) {
                $setId = $map['SetID'] !== null ? (int)$map['SetID'] : null;

                $stage['Maps'][] = [
                    'BeatmapID' => (int)$map['BeatmapID'],
                    'Slot' => $map['Slot'],
                    'SortOrder' => (int)$map['SortOrder'],
                    'IsCustom' => (int)$map['IsCustom'],
                    'SetID' => $setId,
                    'Title' => $map['Title'] ?? $map['BeatmapID'] . ' (Map not in OMDB)',
                    'Artist' => $map['Artist'] ?? '',
                    'DifficultyName' => $map['DifficultyName'] ?? '',
                    'ImageUrl' => $setId ? "https://b.ppy.sh/thumb/{$setId}l.jpg" : '/assets/img/missing-map-thumbnail.png'
                ];
            }
            $mapStmt->close();

            $stages[] = $stage;
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM tournament_series WHERE SeriesID = ?;");
        $stmt->bind_param("i", $tournament["SeriesID"]);
        $stmt->execute();
        $series = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (is_null($series)) {
            http_response_code(404);
            exit();
        }
    }
?>

<style>
    .container {
        width: 100%;
        background-color: DarkSlateGrey;
        padding: 1.5em;
        box-sizing: border-box;
        overflow: hidden;
    }

    .container td {
        padding: 0.5em;
    }

    textarea {
        border: 1px solid white;
        background-color: #203838;
        color: white;
        width: 100%;
    }

    .container select{
        border: 1px solid white;
        background-color: #203838;
    }

    .draggable-container {
        display: flex;
        flex-direction: column;
        width: 100%;
        box-sizing: border-box;
    }

    .draggable {
        margin: 0.5em 0 0;
        border: 1px solid #999;
        cursor: grab;
        box-sizing: border-box;
        min-height: 12em;
        display:flex;
        align-items: center;
        padding-right: 1em;
        padding-top: 1em;
        padding-bottom: 1em;
    }

    .draggable>div {
        margin-left:1em;
    }

    .draggable .icon-reorder, .draggable .icon-remove {
        font-size: 2em;
    }

    .draggable .icon-remove {
        color: firebrick;
    }

    .dragging {
        opacity: 0.5;
    }

    #DeleteListButton {
        background-color: firebrick;
        float: right;
    }

    #DeleteListButton:hover {
        background-color: #722020;
    }
</style>

<h1 style="margin:0;">Create new tournament for <?php echo safe_htmlspecialchars($series["Name"] ?? ''); ?></h1>
<hr>

<form id="tournamentForm" action="SubmitEdit.php" method="POST">
    <input type="hidden" name="EditData" id="EditData" value="" />
    <input type="hidden" name="TournamentID" value="<?php echo safe_htmlspecialchars($tournamentId ?? ''); ?>" />
    <input type="hidden" name="SeriesID" value="<?php echo safe_htmlspecialchars($series['SeriesID'] ?? ''); ?>" />

    <div class="container">
        <label>Tournament Name:</label><br>
        <input autocomplete="off" id="TournamentName" value="<?php echo safe_htmlspecialchars($tournament['Name'] ?? ''); ?>" required maxlength="50"/><br><br>
        <label>Acronym:</label><br>
        <input autocomplete="off" id="TournamentAcronym" value="<?php echo safe_htmlspecialchars($tournament['Acronym'] ?? ''); ?>" required maxlength="10"/><br><br>
        <label>Start Date:</label><br>
        <input autocomplete="off" id="TournamentStartDate" value="<?php echo safe_htmlspecialchars($tournament['StartDate'] ?? ''); ?>" required placeholder="YYYY-MM-DD" /><br><br>
        <label>End Date:</label><br>
        <input autocomplete="off" id="TournamentEndDate" value="<?php echo safe_htmlspecialchars($tournament['EndDate'] ?? ''); ?>" placeholder="YYYY-MM-DD" /><br><br>
        <label>Tournament Series:</label> <br>
        <input disabled value="<?php echo safe_htmlspecialchars($series["Name"] ?? ''); ?>" style="color: var(--main-theme-subtext-color);"/>
    </div>

    <br>

    <div class="tabbed-container-nav" id="stageTabsNav">
    </div>

    <div class="flex-container">
        <div id="container" class="draggable-container" style="width:80%;">
        </div>
        <div style="width:20%;margin: 0.5em 0 0.5em 0.5em;padding: 1em;min-height:24em;">
            <b>Current stage</b><br>
            <label>Name</label> <br>
            <input autocomplete="off" id="CurrentStageName" oninput="updateActiveStageInfo()" /> <br>
            <label>Acronym</label> <br>
            <input autocomplete="off" id="CurrentStageAcronym" oninput="updateActiveStageInfo()" /> <br>
            <br>
            <button id="MoveStageLeft" type="button" class="add-stage-btn" onclick="moveStageLeft()"> << </button>
            <button id="MoveStageRight" type="button" class="add-stage-btn" onclick="moveStageRight()"> >> </button> <br><br>
            <button type="button" class="add-stage-btn" onclick="addNewStage()">+ Add new stage</button> <br> <br>
            <button type="button" class="btn btn-danger" onclick="removeActiveStage()" id="removeStageBtn">
               - Remove current stage
            </button>
            <hr>
            <b>Add new map to current stage</b><br>
            <label>Beatmap ID:</label> <br>
            <input id="NewBeatmapID" name="NewBeatmapID"/> <br>
            <label>Slot:</label> <br>
            <input id="NewBeatmapSlot" name="NewBeatmapSlot"/> <br><br>
            <label>Is custom map:</label> <br>
            <input id="NewBeatmapIsCustom" name="NewBeatmapIsCustom" type="checkbox" /> <br><br>
            <input type="button" id="addNewButton" value="Add beatmap" onclick="addBeatmapToActiveStage()" />
            <hr>
            <b>Mass add maps to current stage</b><br>
            <label style="font-size: 0.85em; color: #ccc;">
                Format (1 per line):<br>
                <code>SLOT | BEATMAPID | ISCUSTOM (optional)</code> <br>
                <span class="subText">(you can also paste osu-wiki MD)</span>
            </label>
            <textarea id="MassAddInput" rows="5" placeholder="NM1 | 75&#10;NM2 | 102 | 1&#10;NM3 | 108" style="margin-top: 0.5em;"></textarea>
            <br><br>
            <button type="button" id="MassAddButton" onclick="massAddBeatmapsToActiveStage()">Mass Add Maps</button>
            <hr>
            <b><label>Meta comment</label></b>
            <textarea id="MetaComment" name="meta" style="width:100%;" rows=5 placeholder="Place any sources for this edit (e.g. osu!wiki, forum post, spreadsheet)" ></textarea>
        </div>
    </div>
    <div class="container" style="margin-top: 1em;">
        <input type="submit" id="submitButton" value="Submit tournament edit" />
    </div>
</form>

<script>
    let stages = <?php
        $formattedStages = [];
        if (!empty($stages)) {
            foreach ($stages as $sId => $s) {
                $formattedStages[] = [
                    'StageID' => $s['StageID'],
                    'Name' => $s['Name'],
                    'Acronym' => $s['Acronym'],
                    'Maps' => $s['Maps'] ?? []
                ];
            }
        }
        echo json_encode($formattedStages);
    ?>;

    let activeStageIndex = 0;

    document.addEventListener("DOMContentLoaded", () => {
        if (stages.length === 0) {
            addNewStage("Qualifiers", "Q");
        } else {
            renderTabs();
        }
    });

    document.getElementById("tournamentForm").onkeypress = function(e) {
        var key = e.charCode || e.keyCode || 0;
        if (key == 13) {
            e.preventDefault();
        }
    }

    function renderTabs() {
        const nav = document.getElementById("stageTabsNav");
        nav.innerHTML = "";

        stages.forEach((stage, index) => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = index === activeStageIndex ? "active" : "";
            btn.textContent = stage.Acronym || stage.Name || `Stage ${index + 1}`;
            btn.onclick = () => switchStage(index);
            nav.appendChild(btn);
        });

        if (stages[activeStageIndex]) {
            document.getElementById("CurrentStageName").value = stages[activeStageIndex].Name;
            document.getElementById("CurrentStageAcronym").value = stages[activeStageIndex].Acronym;
        } else {
            document.getElementById("CurrentStageName").value = "";
            document.getElementById("CurrentStageAcronym").value = "";
        }

        renderActiveStageMaps();
    }

    function switchStage(index) {
        activeStageIndex = index;
        renderTabs();
    }

    function updateActiveStageInfo() {
        if (!stages[activeStageIndex]) return;
        stages[activeStageIndex].Name = document.getElementById("CurrentStageName").value;
        stages[activeStageIndex].Acronym = document.getElementById("CurrentStageAcronym").value;

        const activeTabBtn = document.querySelectorAll("#stageTabsNav button")[activeStageIndex];
        if (activeTabBtn) {
            activeTabBtn.textContent = stages[activeStageIndex].Acronym || stages[activeStageIndex].Name || `Stage ${activeStageIndex + 1}`;
        }
    }

    function addNewStage(defaultName = "New Stage", defaultAcronym = "NEW") {
        stages.push({
            StageID: null,
            Name: defaultName,
            Acronym: defaultAcronym,
            Maps: []
        });
        activeStageIndex = stages.length - 1;
        renderTabs();
    }

    function moveStageLeft() {
        if (activeStageIndex <= 0) return;

        const temp = stages[activeStageIndex];
        stages[activeStageIndex] = stages[activeStageIndex - 1];
        stages[activeStageIndex - 1] = temp;

        activeStageIndex--;
        renderTabs();
    }

    function moveStageRight() {
        if (activeStageIndex >= stages.length - 1) return;

        const temp = stages[activeStageIndex];
        stages[activeStageIndex] = stages[activeStageIndex + 1];
        stages[activeStageIndex + 1] = temp;

        activeStageIndex++;
        renderTabs();
    }

    function renderActiveStageMaps() {
        const container = document.getElementById("container");
        container.innerHTML = "";

        const activeStage = stages[activeStageIndex];
        if (!activeStage || !activeStage.Maps) return;

        activeStage.Maps.forEach((map, index) => {
            const row = document.createElement("div");
            row.className = "draggable alternating-bg flex-container";
            row.style.alignItems = "center";
            row.style.padding = "0.5em";
            row.setAttribute("data-beatmap-id", map.BeatmapID);
            row.setAttribute("draggable", "true");

            const imageUrl = map.ImageUrl || (map.SetID ? `https://b.ppy.sh/thumb/${map.SetID}l.jpg` : '/assets/img/missing-map-thumbnail.png');
            const displayTitle = map.Artist ? `${map.Artist} - ${map.Title} [${map.DifficultyName}]` : (map.Title || `Beatmap #${map.BeatmapID}`);

            row.innerHTML = `
                <div>
                    <i class="icon-reorder" style="cursor: grab; margin-right: 0.5em;"></i>
                </div>
                <div style="width: 5em; text-align: center;">
                    <input type="text"
                        class="map-slot-input"
                        value="${escapeHtml(map.Slot)}"
                        style="width: 100%; text-align: center; box-sizing: border-box;"
                        placeholder="Slot" />
                </div>
                <div style="padding: 0 1em; box-sizing: content-box;">
                    <img src="${imageUrl}"
                        class="diffThumb"
                        style="aspect-ratio: 1 / 1; width: 80px; height: auto;"
                        onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';" />
                </div>
                <div style="flex-grow: 1;">
                    <b>${escapeHtml(displayTitle)}</b><br>
                    Beatmap ID: <code>${escapeHtml(map.BeatmapID)}</code>
                    <div style="margin-top: 0.25em;">
                        <label style="font-size: 0.85em; cursor: pointer; user-select: none;">
                            <input type="checkbox" class="map-custom-checkbox" ${map.IsCustom ? 'checked' : ''} /> Custom Map
                        </label>
                    </div>
                </div>
                <div>
                    <i class="icon-remove" style="cursor: pointer;" title="Remove map"></i>
                </div>
            `;

            const slotInput = row.querySelector(".map-slot-input");
            slotInput.addEventListener("input", (e) => {
                map.Slot = e.target.value;
            });

            const customCheckbox = row.querySelector(".map-custom-checkbox");
            customCheckbox.addEventListener("change", (e) => {
                map.IsCustom = e.target.checked ? 1 : 0;
            });

            slotInput.addEventListener("dragstart", (e) => e.stopPropagation());
            customCheckbox.addEventListener("dragstart", (e) => e.stopPropagation());

            row.querySelector(".icon-remove").addEventListener("click", () => {
                if (confirm("Are you sure you want to remove this map from the stage?")) {
                    removeMapFromActiveStage(index);
                }
            });

            container.appendChild(row);
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function addBeatmapToActiveStage() {
        const mapIdInput = document.getElementById("NewBeatmapID");
        const slotInput = document.getElementById("NewBeatmapSlot");
        const customInput = document.getElementById("NewBeatmapIsCustom");

        const beatmapID = parseInt(mapIdInput.value);
        const slot = slotInput.value.trim();
        const isCustom = customInput.checked ? 1 : 0;

        if (!beatmapID || !slot) {
            alert("Please enter a valid Beatmap ID and Slot.");
            return;
        }

        fetch(`GetBeatmapData.php?id=${beatmapID}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.error || !data) {
                    alert(data.error || "Failed to load beatmap data.");
                    return;
                }

                const newMap = {
                    BeatmapID: beatmapID,
                    Slot: slot,
                    IsCustom: isCustom,
                    SetID: data.SetID || data.setId || null,
                    Title: data.Title || data.itemTitle || "Unknown Title",
                    Artist: data.Artist || "",
                    DifficultyName: data.DifficultyName || data.version || "",
                    ImageUrl: data.ImageUrl || data.imageUrl || `https://b.ppy.sh/thumb/${data.SetID}l.jpg`
                };

                const currentMaps = stages[activeStageIndex].Maps;
                const existingIndex = currentMaps.findIndex(
                    m => m.Slot.trim().toLowerCase() === slot.toLowerCase()
                );

                if (existingIndex !== -1) {
                    currentMaps[existingIndex] = newMap;
                } else {
                    currentMaps.push(newMap);
                }

                mapIdInput.value = "";
                slotInput.value = "";
                customInput.checked = false;
                renderActiveStageMaps();
            })
            .catch((err) => {
                console.error("Error fetching beatmap data:", err);
                alert("An error occurred while fetching beatmap information.");
            });
    }

    function removeMapFromActiveStage(index) {
        stages[activeStageIndex].Maps.splice(index, 1);
        renderActiveStageMaps();
    }

    document.getElementById("tournamentForm").addEventListener("submit", function (e) {
        const seriesInput = document.querySelector("input[name='SeriesID']");

        const payload = {
            Tournament: {
                Name: document.getElementById("TournamentName").value,
                Acronym: document.getElementById("TournamentAcronym").value,
                SeriesID: seriesInput && seriesInput.value !== "" ? parseInt(seriesInput.value, 10) : null,
                StartDate: document.getElementById("TournamentStartDate").value,
                EndDate: document.getElementById("TournamentEndDate").value
            },
            Stages: stages.map((stage, stageIndex) => ({
                StageID: stage.StageID || null,
                Name: stage.Name,
                Acronym: stage.Acronym,
                SortOrder: stageIndex + 1,
                Maps: stage.Maps.map((map, mapIndex) => ({
                    BeatmapID: map.BeatmapID,
                    Slot: map.Slot,
                    SortOrder: mapIndex + 1,
                    IsCustom: map.IsCustom || 0
                }))
            })),
            Meta: document.getElementById("MetaComment").value
        };

        document.getElementById("EditData").value = JSON.stringify(payload);
    });

    let draggingElement = null;
    const container = document.getElementById("container");

    container.addEventListener("dragstart", (e) => {
        const draggable = e.target.closest(".draggable");
        if (draggable) {
            draggingElement = draggable;
            draggable.classList.add("dragging");
        }
    });

    container.addEventListener("dragover", (e) => {
        e.preventDefault();
        if (!draggingElement) return;

        const afterElement = getDragAfterElement(container, e.clientY);
        if (afterElement == null) {
            container.appendChild(draggingElement);
        } else {
            container.insertBefore(draggingElement, afterElement);
        }
    });

    container.addEventListener("dragend", (e) => {
        if (draggingElement) {
            draggingElement.classList.remove("dragging");
            draggingElement = null;
        }
        syncMapOrderFromDOM();
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll(".draggable:not(.dragging)")];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function syncMapOrderFromDOM() {
        const activeStage = stages[activeStageIndex];
        if (!activeStage || !activeStage.Maps) return;

        const domRows = [...container.querySelectorAll(".draggable")];
        const newMapsArray = [];

        domRows.forEach((row) => {
            const beatmapID = parseInt(row.getAttribute("data-beatmap-id"));

            const mapObj = activeStage.Maps.find(m => m.BeatmapID === beatmapID);
            if (mapObj) {
                newMapsArray.push(mapObj);
            }
        });

        activeStage.Maps = newMapsArray;
    }

    function removeActiveStage() {
        if (stages.length === 0) {
            alert("No stages available to remove.");
            return;
        }

        const currentStageName = stages[activeStageIndex]?.Name || `Stage #${activeStageIndex + 1}`;

        if (!confirm(`Are you sure you want to delete "${currentStageName}" and all its maps?`)) {
            return;
        }

        stages.splice(activeStageIndex, 1);

        if (activeStageIndex >= stages.length) {
            activeStageIndex = Math.max(0, stages.length - 1);
        }

        renderTabs();
    }

    function getAcronymForCategory(categoryStr) {
        const clean = categoryStr.toLowerCase().replace(/[^a-z0-9]/g, "");
        if (clean.includes("nomod")) return "NM";
        if (clean.includes("hidden")) return "HD";
        if (clean.includes("hardrock")) return "HR";
        if (clean.includes("doubletime")) return "DT";
        if (clean.includes("freemod")) return "FM";
        if (clean.includes("tiebreaker")) return "TB";

        return categoryStr.split(" ").map(w => w[0]).join("").toUpperCase() || "";
    }

    async function massAddBeatmapsToActiveStage() {
        const textarea = document.getElementById("MassAddInput");
        const rawText = textarea.value.trim();

        if (!rawText) {
            alert("Please enter at least one map or paste Markdown.");
            return;
        }

        const lines = rawText.split("\n");
        const parsedEntries = [];

        const isMarkdown = rawText.includes("http") || rawText.includes("beatmapsets");

        if (isMarkdown) {
            let currentAcronym = "NM";

            for (let i = 0; i < lines.length; i++) {
                const line = lines[i].trim();
                if (!line) continue;

                if (line.startsWith("-") && !line.includes("[")) {
                    const category = line.replace(/^[#\-\*\s]+/, "").trim();
                    currentAcronym = getAcronymForCategory(category);
                    continue;
                }

                const idMatch = line.match(/(?:#osu\/|\/b\/|\/beatmaps\/)(\d+)/);
                if (!idMatch) continue;

                const beatmapID = parseInt(idMatch[1], 10);
                const numMatch = line.match(/^(\d+)\./);
                const slotNumber = numMatch ? numMatch[1] : (parsedEntries.filter(e => e.slot.startsWith(currentAcronym)).length + 1);
                const slot = `${currentAcronym}${slotNumber}`;

                parsedEntries.push({
                    slot: slot,
                    beatmapID: beatmapID,
                    isCustom: 0
                });
            }
        } else {
            for (let i = 0; i < lines.length; i++) {
                const line = lines[i].trim();
                if (!line) continue;

                const parts = line.split("|").map(p => p.trim());
                if (parts.length < 2) {
                    alert(`Line ${i + 1} is invalid. Required format: SLOT | BEATMAPID`);
                    return;
                }

                const slot = parts[0];
                const beatmapID = parseInt(parts[1], 10);
                const isCustom = parts[2] ? (parseInt(parts[2], 10) === 1 ? 1 : 0) : 0;

                if (!slot || isNaN(beatmapID)) {
                    alert(`Line ${i + 1} is invalid`);
                    return;
                }

                parsedEntries.push({ slot, beatmapID, isCustom });
            }
        }

        if (parsedEntries.length === 0) {
            alert("no maps found");
            return;
        }

        const btn = document.getElementById("MassAddButton");
        btn.disabled = true;
        btn.innerText = `Adding ${parsedEntries.length} map(s)...`;

        let addedCount = 0;
        let failedCount = 0;

        for (const entry of parsedEntries) {
            try {
                const response = await fetch(`GetBeatmapData.php?id=${entry.beatmapID}`);
                const rawText = await response.text();
                let data;

                try {
                    data = JSON.parse(rawText);
                } catch (parseError) {
                    failedCount++;
                    continue;
                }

                if (data.error || !data) {
                    console.error(data.error);
                    failedCount++;
                    continue;
                }

                const newMap = {
                    BeatmapID: entry.beatmapID,
                    Slot: entry.slot,
                    IsCustom: entry.isCustom,
                    SetID: data.SetID || data.setId || null,
                    Title: data.Title || data.itemTitle || "Unknown Title",
                    Artist: data.Artist || "",
                    DifficultyName: data.DifficultyName || data.version || "",
                    ImageUrl: data.ImageUrl || data.imageUrl || `https://b.ppy.sh/thumb/${data.SetID}l.jpg`
                };

                const currentMaps = stages[activeStageIndex].Maps;
                const existingIndex = currentMaps.findIndex(
                    m => m.Slot.trim().toLowerCase() === entry.slot.trim().toLowerCase()
                );

                if (existingIndex !== -1) {
                    currentMaps[existingIndex] = newMap;
                } else {
                    currentMaps.push(newMap);
                }

                addedCount++;
            } catch (err) {
                console.error(err);
                failedCount++;
            }
        }

        renderActiveStageMaps();

        btn.disabled = false;
        btn.innerText = "Mass Add Maps";
        textarea.value = "";

        if (failedCount > 0) {
            alert(`Added ${addedCount} map(s). Failed to fetch ${failedCount} map(s). Check console for details.`);
        }
    }

    window.addEventListener('beforeunload', (e) => {
        e.preventDefault();
        e.returnValue = '';
    });
</script>

<?php
    require "../../footer.php";
?>
