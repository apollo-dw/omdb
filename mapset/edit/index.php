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
    ?>

<h1>Edit request for <?php echo htmlspecialchars($sampleRow['Title']) . " by " . GetUserNameFromId($sampleRow['SetCreatorID'], $conn) ?></h1>
<a href="../<?php echo $mapset_id; ?>">Return to mapset</a><br><br><br><br>

<style>
    .tab {
        width:100%;
        background-color: darkslategray;
        padding: 1.5em;
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
    <?php
        foreach($difficulties as $beatmapID => $difficulty){
            $tabName = $difficulty['DifficultyName'];
            if ($difficulty['HasEditRequest'])
                $tabName .= " (!)";

            echo "<button onclick=\"openTab('{$beatmapID}')\">{$tabName}</button>";
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

            echo "<b>{$requesterUsername}</b> submitted this request on {$request["Timestamp"]}<br><hr>";

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
                if ($userName == "moonpoint" && $loggedIn) {
            ?>
                <button style="background-color:#477769;" type="button" onclick="window.location.href = `AcceptRequest.php?BeatmapID=<?php echo $beatmapID; ?>`;">ACCEPT</button>
                <button style="background-color:firebrick;" type="button" onclick="window.location.href = `DenyRequest.php?BeatmapID=<?php echo $beatmapID; ?>`;">DENY</button>
            <?php
                }
            ?>
<?php
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
                            <input id="add-mapper-input-<?php echo $beatmapID; ?>" type="text" pattern="[0-9]+" placeholder="Add ID here"/> <br>
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
                </div>
                <hr>
                <label for="meta">
                    Add any comments for this edit request:<br>
                    <span class="subText">This is a good place to leave sources & reasons for the changes, if they are not immediately obvious.</span><br><br>
                    <textarea id="meta-comment-<?php echo $beatmapID; ?>" name="meta" style="width:33%;"></textarea>
                </label>
                <hr>
                <br>
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
            echo '<span class="subText">unedited difficulty</span>';
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
                      </details>";
            }
        }

        echo '</div>';
    }
?>

<script>
    window.onbeforeunload = function() {
        return true;
    };

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

        const beatmapID = $(this).attr('difficultyID');
        const beatmapIDInput = $("<input>")
            .attr("type", "hidden")
            .attr("name", "BeatmapID")
            .val(beatmapID);
        form.append(beatmapIDInput);

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