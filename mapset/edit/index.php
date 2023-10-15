<?php
$mapset_id = $_GET['id'] ?? -1;
require '../../base.php';

if (!$loggedIn) {
    die("You need to be logged in to view this page.");
}

$stmt = $conn->prepare("SELECT b.*, ber.`BeatmapID` AS `HasEditRequest`
                           FROM `beatmaps` b
                           LEFT JOIN `beatmap_edit_requests` ber ON b.`BeatmapID` = ber.`BeatmapID` AND ber.`Status` = 'Pending'
                           WHERE b.`SetID` = ? 
                           ORDER BY b.`Mode`, b.`SR` DESC;");
$stmt->bind_param("s", $mapset_id);
$stmt->execute();
$result = $stmt->get_result();
$sampleRow = $result->fetch_assoc();
mysqli_data_seek($result, 0);

$PageTitle = htmlspecialchars($sampleRow['Title']) . " by " . GetUserNameFromId($sampleRow['SetCreatorID'], $conn);
require '../../header.php';

if($mapset_id == -1){
    die("Nop");
}

$difficulties = [];
while ($row = $result->fetch_assoc())
    $difficulties[$row['BeatmapID']] = $row;

$mapset_id = htmlspecialchars($mapset_id);

$stmt = $conn->prepare("SELECT * FROM `beatmap_edit_requests` WHERE SetID = ? AND `Status` = 'Pending';");
$stmt->bind_param('i', $mapset_id);
$stmt->execute();
$result = $stmt->get_result();

$setHasEditRequest = $result->num_rows > 0;
if ($setHasEditRequest) {
    $setRequest = $result->fetch_assoc();
}
?>

    <h1>Edit request for <?php echo htmlspecialchars($sampleRow['Title']) . " by " . GetUserNameFromId($sampleRow['SetCreatorID'], $conn) ?></h1>
    <a href="../<?php echo $mapset_id; ?>">Return to mapset</a><br><br><br><br>

    <style>
        .tab {
            width:100%;
            background-color: darkslategray;
            padding: 1.5em;
        }

        .pending-changes {
            background-color: #6A4256 !important;
        }

        .pending-changes:hover {
            background-color: #4F2F3F !important;
        }

        .mapperList .remove-button{
            min-width: 0;
            background-color: firebrick;
        }

        .mapperList {
            background-color: #182828;
            min-height: 4em;
            margin-left: 0;
            padding-left: 0.5em;
            min-width: 20em;
        }

        .mapperList li{
            display: block;
            padding-bottom: 0.25em;
        }
    </style>

    <div class="tabbed-container-nav">
        <button <?php if ($setHasEditRequest) echo "class='pending-changes'"; ?> onclick="openTab('set')">Mapset (General)</button><?php
        foreach($difficulties as $beatmapID => $difficulty){
            $class = "";
            if ($difficulty['HasEditRequest'])
                $class = "class='pending-changes'";

            echo "<button {$class} onclick=\"openTab('{$beatmapID}')\">{$difficulty['DifficultyName']}</button>";
        }

        ?>
    </div>

    <div id='set' class='tab' style='display:none;'>
        <?php if($setHasEditRequest) {
            $stmt = $conn->prepare("SELECT NominatorID FROM `beatmapset_nominators` WHERE SetID = ?;");
            $stmt->bind_param('i', $mapset_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $currentNominators = array();
            while ($row = $result->fetch_assoc())
                $currentNominators[] = $row['NominatorID'];

            $requesterUsername = GetUserNameFromId($setRequest['UserID'], $conn);
            $data = json_decode($setRequest['EditData'], true);
            $meta = $data["Meta"] != '' ? htmlspecialchars($data["Meta"]) : "<i>No comment for request</i>";
            $newMappers = $data["Mappers"];

            echo "<b>{$requesterUsername}</b> submitted this request on {$setRequest["Timestamp"]}<br><br>
        <a href='http://osu.ppy.sh/b/{$beatmapID}' target='_blank'>Link to set on osu! website</a>
        <hr>";

            $deletedMappers = array_diff($currentNominators, $newMappers);
            $newlyAddedMappers = array_diff($newMappers, $currentNominators);
            $unchangedMappers = array_intersect($currentNominators, $newMappers);

            echo "Changes to Nominators:<div style='background-color:#182828;font-family: monospace;border: 1px solid white;padding: 0.5em;width: 33%;min-height:10em;'>";
            foreach ($deletedMappers as $deletedID) {
                $name = GetUserNameFromId($deletedID, $conn);
                echo "<span style='color: red;'>- {$name} <span class='subText'>{$deletedID}</span></span><br>";
            }

            foreach ($unchangedMappers as $unchangedID) {
                $name = GetUserNameFromId($unchangedID, $conn);
                echo "* {$name} <span class='subText'>{$unchangedID}</span><br>";
            }

            foreach ($newlyAddedMappers as $newlyAddedID) {
                $name = GetUserNameFromId($newlyAddedID, $conn);
                echo "<span style='color: green;'>+ {$name} <span class='subText'>{$newlyAddedID}</span></span><br>";
            }
            echo "</div>";
            ?>
            <hr>
            <b>Meta comment:</b>
            <div style="background-color:#182828;border: 1px solid white;padding: 0.5em;width: 33%;min-height:10em;">
                <?php echo nl2br($meta); ?>
            </div>
            <hr>
            <?php
            if (($userName == "moonpoint" || $userId == 12704035 || $userId === 1721120) && $loggedIn) {
                ?>
                <button style="background-color:#477769;" type="button" onclick="window.location.href = `AcceptRequest.php?SetID=<?php echo $mapset_id; ?>`;">ACCEPT</button>
                <?php
            }

            if (($userName == "moonpoint" || $userId == 12704035 || $userId === 1721120 || $userId == $setRequest['UserID'] ) && $loggedIn) {
                ?>
                <button style="background-color:firebrick;" type="button" onclick="window.location.href = `DenyRequest.php?SetID=<?php echo $mapset_id; ?>`;">DENY</button>
                <?php
            }
        } else { ?>
            You are submitting an edit request for <b>the whole set.</b><br>
            Misuse of the edit request feature will result in you being banned. This is not recommended.
            <br><br>
            <hr>
            <u><b>Determining who the nominators of a moddingv1 set are is tricky.</b></u><br>
            You'll need to look at the mapset's forum post and guesstimate, since icons have been removed. <br>
            You can find the forum post through <a href="https://osu.ppy.sh/beatmapsets/<?php echo $mapset_id; ?>/discussion" target='_blank'>the modding discussions</a>. <br>
            Wayback machine may prove useful aswell: <b>https://web.archive.org/web/20171125185124/http://osu.ppy.sh/forum/t/[insert forum id here]/</b><br> <br>

            As some sort of "criteria":
            <ul>
                <li><b>Treat the set of bubbles before an unrank as the final nominations.</b> It was common for maps to get ranked again without any re-bubbles.</li>
                <li><b>Treat the most recent set of bubbles as the final nominations.</b> It was extremely common for maps to get bubbled by one set of nominators, and then re-bubbled by a different set of nominators.</li>
                <li>Approved and multi-mode maps will likely have 3 nominators associated with them. </li>
                <li>Leaving links to the relevant forum posts in the proposal meta is good practice :)</li>
            </ul>
            <hr>
            <form action="SubmitEditRequest.php" method="post" id="form-set" setID="<?php echo $mapset_id; ?>">
                <b>Nominators</b><br>
                <span class="subText">Add users that have nominated this beatmap.</span><br>
                <div class="flex-container">
                    <div style="margin-right: 1em;">
                        <label>
                            Add nominator ID:
                            <input id="add-mapper-input-set" type="text" pattern="[0-9]+" placeholder="Add ID here" onkeypress="return event.keyCode != 13;" > <br>
                            <button type="button" id="add-mapper-btn-set" onclick="addItem(this)" style="float:right;margin-right:1em;">Add</button>
                        </label>
                    </div>
                    <div>
                        <ul class="mapperList" difficultyID="set">
                            <?php
                            $stmt = $conn->prepare("SELECT NominatorID, u.Username FROM beatmapset_nominators bc LEFT JOIN mappernames u ON u.UserID = bc.NominatorID WHERE SetID = ?");
                            $stmt->bind_param('i', $mapset_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while($row = $result->fetch_assoc())
                                echo "<li>{$row["Username"]} <span class='subText mapperid'>{$row["NominatorID"]}</span> <button class='remove-button'>X</button></li>";
                            ?>
                        </ul>
                    </div>
                </div><br>
                <label for="meta">
                    Add any comments for this edit request:<br>
                    <span class="subText">This is a good place to leave sources & reasons for the changes, if they are not immediately obvious.</span><br><br>
                    <textarea id="meta-comment-set" name="meta" style="width:33%;"></textarea>
                </label>
                <br><br>
                <button type="submit" form="form-set">Submit edit request</button>
            </form>

            <?php
        }

        echo '<hr> <b>Edit history:</b> <br>';
        $stmt = $conn->prepare("SELECT * FROM beatmap_edit_requests WHERE SetID = ? ORDER BY Timestamp DESC");
        $stmt->bind_param("i", $mapset_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo '<span class="subText">no edits</span>';
        } else {
            while($row = $result->fetch_assoc()) {
                $editDataArray = json_decode($row['EditData'], true);

                $status = "Pending";
                if ($row["Status"] != "Pending"){
                    $editorName = GetUserNameFromId($row["EditorID"], $conn);
                    $status = "{$row["Status"]} by {$editorName}";
                }

                $submitterName = GetUserNameFromId($row["UserID"], $conn);

                echo "<details>
                <summary><span class='subText'>{$submitterName} on {$row["Timestamp"]} {$status}</span></summary>
                <span class='subText'>{$row['EditData']}</span>
            </details>";
            }
        }
        ?>
    </div>

<?php
foreach($difficulties as $beatmapID => $difficulty) {
    echo "<div id='{$beatmapID}' class='tab' style='display:none;'>";
    if ($difficulty['HasEditRequest']){
        $stmt = $conn->prepare("SELECT * FROM beatmap_edit_requests WHERE `BeatmapID` = ? AND Status = 'Pending';");
        $stmt->bind_param('i', $beatmapID);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();

        $stmt = $conn->prepare("SELECT CreatorID FROM `beatmap_creators` WHERE BeatmapID = ?;");
        $stmt->bind_param('i', $beatmapID);
        $stmt->execute();
        $result = $stmt->get_result();

        $currentMappers = array();
        while ($row = $result->fetch_assoc())
            $currentMappers[] = $row['CreatorID'];

        $requesterUsername = GetUserNameFromId($request['UserID'], $conn);
        $data = json_decode($request['EditData'], true);
        $meta = $data["Meta"] != '' ? htmlspecialchars($data["Meta"]) : "<i>No comment for request</i>";
        $newMappers = $data["Mappers"];

        echo "<b>{$requesterUsername}</b> submitted this request on {$request["Timestamp"]}<br><br>
                  <a href='http://osu.ppy.sh/b/{$beatmapID}' target='_blank'>Link to set on osu! website</a>
                  <hr>";

        $deletedMappers = array_diff($currentMappers, $newMappers);
        $newlyAddedMappers = array_diff($newMappers, $currentMappers);
        $unchangedMappers = array_intersect($currentMappers, $newMappers);

        echo "Changes to Mappers:<div style='background-color:#182828;font-family: monospace;border: 1px solid white;padding: 0.5em;width: 33%;min-height:10em;'>";
        foreach ($deletedMappers as $deletedID) {
            $name = GetUserNameFromId($deletedID, $conn);
            echo "<span style='color: red;'>- {$name} <span class='subText'>{$deletedID}</span></span><br>";
        }

        foreach ($unchangedMappers as $unchangedID) {
            $name = GetUserNameFromId($unchangedID, $conn);
            echo "* {$name} <span class='subText'>{$unchangedID}</span><br>";
        }

        foreach ($newlyAddedMappers as $newlyAddedID) {
            $name = GetUserNameFromId($newlyAddedID, $conn);
            echo "<span style='color: green;'>+ {$name} <span class='subText'>{$newlyAddedID}</span></span><br>";
        }
        echo "</div>";
        ?>
        <hr>
        <b>Meta comment:</b>
        <div style="background-color:#182828;border: 1px solid white;padding: 0.5em;width: 33%;min-height:10em;">
            <?php echo nl2br($meta); ?>
        </div>
        <hr>
        <?php
        if (($userName == "moonpoint" || $userId == 12704035 || $userId === 1721120) && $loggedIn) {
            ?>
            <button style="background-color:#477769;" type="button" onclick="window.location.href = `AcceptRequest.php?BeatmapID=<?php echo $beatmapID; ?>`;">ACCEPT</button>
            <?php
        }

        if (($userName == "moonpoint" || $userId == 12704035 || $userId === 1721120 || $userId == $request['UserID'] ) && $loggedIn) {
            ?>
            <button style="background-color:firebrick;" type="button" onclick="window.location.href = `DenyRequest.php?BeatmapID=<?php echo $beatmapID; ?>`;">DENY</button>
            <?php
        }
    } else {
        ?>
        You are submitting an edit request for <b><?php echo $difficulty['DifficultyName']; ?></b>.<br>
        Misuse of the edit request feature will result in you being banned. This is not recommended.
        <br><br>
        <hr>
        <form action="SubmitEditRequest.php" method="post" id="form-<?php echo $beatmapID; ?>" difficultyID="<?php echo $beatmapID; ?>">
            <b>Mappers</b><br>
            <span class="subText">Add mappers that have contributed to this difficulty.</span><br>
            <div class="flex-container">
                <div style="margin-right: 1em;">
                    <label>
                        Add mapper ID:
                        <input id="add-mapper-input-<?php echo $beatmapID; ?>" type="text" pattern="[0-9]+" placeholder="Add ID here" onkeypress="return event.keyCode != 13;" /> <br>
                        <button type="button" id="add-mapper-btn-<?php echo $beatmapID; ?>" onclick="addItem(this)" style="float:right;margin-right:1em;">Add</button>
                    </label>
                </div>
                <div>
                    <ul class="mapperList" difficultyID="1">
                        <?php
                        $stmt = $conn->prepare("SELECT CreatorID, u.Username FROM beatmap_creators bc LEFT JOIN mappernames u ON u.UserID = bc.CreatorID WHERE BeatmapID = ?");
                        $stmt->bind_param('i', $beatmapID);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        while($row = $result->fetch_assoc())
                            echo "<li>{$row["Username"]} <span class='subText mapperid'>{$row["CreatorID"]}</span> <button class='remove-button'>X</button></li>";
                        ?>
                    </ul>
                </div>
            </div><br>
            <label for="meta">
                Add any comments for this edit request:<br>
                <span class="subText">This is a good place to leave sources & reasons for the changes, if they are not immediately obvious.</span><br><br>
                <textarea id="meta-comment-<?php echo $beatmapID; ?>" name="meta" style="width:33%;"></textarea>
            </label>
            <br><br>
            <button type="submit" form="form-<?php echo $beatmapID; ?>">Submit edit request</button>
        </form>
        <?php
    }
    echo '<hr> <b>Edit history:</b> <br>';
    $stmt = $conn->prepare("SELECT * FROM beatmap_edit_requests WHERE BeatmapID = ? ORDER BY Timestamp DESC");
    $stmt->bind_param("i", $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo '<span class="subText">no edits</span>';
    } else {
        while($row = $result->fetch_assoc()) {
            $editDataArray = json_decode($row['EditData'], true);

            $status = "Pending";
            if ($row["Status"] != "Pending"){
                $editorName = GetUserNameFromId($row["EditorID"], $conn);
                $status = "{$row["Status"]} by {$editorName}";
            }

            $submitterName = GetUserNameFromId($row["UserID"], $conn);

            echo "<details>
                        <summary><span class='subText'>{$submitterName} on {$row["Timestamp"]} {$status}</span></summary>
                        <span class='subText'>{$row['EditData']}</span>
                      </details>";
        }
    }

    echo '</div>';
}
?>

    <script>
        $(document).on("submit", "form", function(event) {
            event.preventDefault();

            const form = $(this);
            const mapperListData = [];
            form.find(".mapperList li").each(function() {
                const mapperID = $(this).find(".mapperid").text();
                mapperListData.push(mapperID);
            });

            const mapperListInput = $("<input>")
                .attr("type", "hidden")
                .attr("name", "mapperListData")
                .val(JSON.stringify(mapperListData));
            form.append(mapperListInput);

            if (mapperListData.length === 0) {
                alert("Mapper list is empty. Please add at least one mapper.");
                return;
            }

            const beatmapID = $(this).attr('difficultyID');
            if (beatmapID){
                const beatmapIDInput = $("<input>")
                    .attr("type", "hidden")
                    .attr("name", "BeatmapID")
                    .val(beatmapID);

                form.append(beatmapIDInput);
            }

            const setID = $(this).attr('setID');
            if (setID){
                const setIDInput = $("<input>")
                    .attr("type", "hidden")
                    .attr("name", "SetID")
                    .val(setID);

                form.append(setIDInput);
            }

            form[0].submit();
        });

        function addItem(button) {
            const beatmapID = $(button).attr("id").split("-").pop();
            const input = $(`#add-mapper-input-${beatmapID}`);
            const value = input.val().trim();
            const list = $(button).closest(".flex-container").find(".mapperList");

            if (value !== '') {
                $.ajax({
                    type: "GET",
                    url: "GetUsernameFromID.php",
                    data: { id: value },
                    success: function(username) {
                        if (username !== '') {
                            const listItem = `<li data-creatorid='${value}'>${username} <span class='subText mapperid'>${value}</span> <button class='remove-button'>X</button></li>`;
                            list.append(listItem);
                            input.val('');
                        }
                    }
                });
            }
        }

        $(document).on("click", ".remove-button", function() {
            $(this).closest("li").remove();
        });
    </script>

<?php
require '../../footer.php';
?>