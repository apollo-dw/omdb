<?php
$mapset_id = $_GET['id'] ?? -1;
require '../../base.php';

if (!$loggedIn) {
    die("You need to be logged in to view this page.");
}

$stmt = $conn->prepare("SELECT b.*, ber.`BeatmapID` AS `HasEditRequest`, s.`Title`, s.`CreatorID`
                           FROM `beatmaps` b
                           JOIN beatmapsets s on b.SetID = s.SetID
                           LEFT JOIN `beatmap_edit_requests` ber ON b.`BeatmapID` = ber.`BeatmapID` AND ber.`Status` = 'Pending'
                           WHERE b.`SetID` = ? 
                           ORDER BY b.`Mode`, b.`SR` DESC;");
$stmt->bind_param("s", $mapset_id);
$stmt->execute();
$result = $stmt->get_result();
$sampleRow = $result->fetch_assoc();
mysqli_data_seek($result, 0);

$PageTitle = htmlspecialchars($sampleRow['Title']) . " by " . GetUserNameFromId($sampleRow['CreatorID'], $conn);
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

$beatmapRoles = $conn->query("SELECT Name FROM beatmap_roles");
while ($row = $beatmapRoles->fetch_assoc()) {
    $roles[] = $row['Name'];
}
$rolesJson = json_encode($roles);

?>

    <h1>Edit request for <?php echo htmlspecialchars($sampleRow['Title']) . " by " . GetUserNameFromId($sampleRow['CreatorID'], $conn) ?></h1>
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

        .mapperList {
            background-color: #182828;
            min-height: 4em;
            margin-left: 0;
            padding: 0.25rem;
            min-width: 20em;
			margin-top: 0;
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
            echo "</div><br>";
			
			$stmt = $conn->prepare("SELECT UserID, br.RoleID, Name FROM `beatmapset_credits` bc LEFT JOIN `beatmap_roles` br ON bc.RoleID = br.RoleID WHERE SetID = ?;");
			$stmt->bind_param('i', $mapset_id);
			$stmt->execute();
			$result = $stmt->get_result();

			$currentCredits = [];
			while ($row = $result->fetch_assoc()) {
				$currentCredits[] = array("role" => $row['Name'], "userID" => $row['UserID']);
			}

			$data = json_decode($setRequest['EditData'], true);
			$newCredits = $data['Credits'];

			$deletedCredits = array_udiff($currentCredits, $newCredits, function ($a, $b) {
				return strcmp(json_encode($a), json_encode($b));
			});

			$newlyAddedCredits = array_udiff($newCredits, $currentCredits, function ($a, $b) {
				return strcmp(json_encode($a), json_encode($b));
			});

			$unchangedCredits = array_uintersect($currentCredits, $newCredits, function ($a, $b) {
				return strcmp(json_encode($a), json_encode($b));
			});

			echo "Changes to Credits:<div style='background-color:#182828;font-family: monospace;border: 1px solid white;padding: 0.5em;width: 33%;min-height:10em;'>";

			foreach ($deletedCredits as $deletedCredit) {
				$name = GetUserNameFromId($deletedCredit['userID'], $conn);
				echo "<span style='color: red;'>- {$name} <span class='subText'>{$deletedCredit['userID']}</span></span> ({$deletedCredit['role']})<br>";
			}

			foreach ($unchangedCredits as $unchangedCredit) {
				$name = GetUserNameFromId($unchangedCredit['userID'], $conn);
				echo "* {$name} <span class='subText'>{$unchangedCredit['userID']}</span> ({$unchangedCredit['role']})<br>";
			}

			foreach ($newlyAddedCredits as $newlyAddedCredit) {
				$name = GetUserNameFromId($newlyAddedCredit['userID'], $conn);
				echo "<span style='color: green;'>+ {$name} <span class='subText'>{$newlyAddedCredit['userID']}</span></span> ({$newlyAddedCredit['role']})<br>";
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
            if ($loggedIn && isIdEditRequestAdmin($userId)) {
                ?>
                <button style="background-color:#477769;" type="button" onclick="window.location.href = `AcceptRequest.php?SetID=<?php echo $mapset_id; ?>`;">ACCEPT</button>
                <?php
            }

            if ($loggedIn && (isIdEditRequestAdmin($userId) || $userId == $setRequest['UserID'])) {
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
                <span class="subText">Add users that have nominated this beatmap.</span><br><br>
                <div class="flex-container">
                    <div style="margin-right: 1em;">
                        <label>
                            Add nominator ID:
                            <input id="add-mapper-input-set" type="text" pattern="[0-9]+" placeholder="Add ID here" onkeypress="return event.keyCode != 13;" > <br>
                            <button type="button" id="add-mapper-btn-set" onclick="addMapperItem(this)" style="float:right;">Add</button>
                        </label>
                    </div>
                    <div style="flex-grow: 1;">
                        <ul class="mapperList nominatorList" difficultyID="set">
                            <?php
                            $stmt = $conn->prepare("SELECT NominatorID, u.Username FROM beatmapset_nominators bc LEFT JOIN mappernames u ON u.UserID = bc.NominatorID WHERE SetID = ?");
                            $stmt->bind_param('i', $mapset_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while($row = $result->fetch_assoc())
                                echo "<li>
							<i class='icon-remove remove-button'></i> 
							{$row["Username"]} 
							<span class='subText mapperid'>{$row["NominatorID"]}</span>
							</li>";
                            ?>
                        </ul>
                    </div>
                </div><br>
				<b>Credits</b><br>
				<span class="subText">Add credits for users who participated in the beatmapset.</span><br><br>
				<div class="flex-container">
					<div style="margin-right: 1em;">
						<label>
							Add user ID:
							<input id="add-credit-input-set" type="text" pattern="[0-9]+" placeholder="Add ID here" onkeypress="return event.keyCode != 13;"> <br>
							<button type="button" id="add-credit-btn-set" onclick="addCreditItem(this)" style="float:right;">Add</button>
						</label>
					</div>
					<div style="flex-grow: 1;">
						<ul class="mapperList creditList" difficultyID="set">
							<?php
							$stmt = $conn->prepare("SELECT bc.UserID, u.Username, bc.RoleID, br.Name FROM beatmapset_credits bc LEFT JOIN mappernames u ON u.UserID = bc.UserID LEFT JOIN beatmap_roles br ON br.RoleID = bc.RoleID WHERE bc.SetID = ?");
							$stmt->bind_param('i', $mapset_id);
							$stmt->execute();
							$result = $stmt->get_result();
							
							while ($row = $result->fetch_assoc()) {
								echo "<li data-creatorid='{$row["UserID"]}'> 
								<i class='icon-remove remove-button'></i> 
								{$row["Username"]} 
								<span class='subText mapperid'>{$row["UserID"]}</span>
								<select class='roles-select'>";
                                
								foreach ($roles as $role) {
									$selectedString = "";
									if ($role == $row["Name"])
										$selectedString = "selected";
									
									echo "<option value='${role}' ${selectedString}>${role}</option>";
								}
								
								echo "</select></li>";
							}
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
        if ($loggedIn && isIdEditRequestAdmin($userId)) {
            ?>
            <button style="background-color:#477769;" type="button" onclick="window.location.href = `AcceptRequest.php?BeatmapID=<?php echo $beatmapID; ?>`;">ACCEPT</button>
            <?php
        }

        if ($loggedIn && isIdEditRequestAdmin($userId)) {
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
                        <button type="button" id="add-mapper-btn-<?php echo $beatmapID; ?>" onclick="addMapperItem(this)" style="float:right;">Add</button>
                    </label>
                </div>
                <div style="flex-grow: 1;">
                    <ul class="mapperList nominatorList" difficultyID="1">
                        <?php
                        $stmt = $conn->prepare("SELECT CreatorID, u.Username FROM beatmap_creators bc LEFT JOIN mappernames u ON u.UserID = bc.CreatorID WHERE BeatmapID = ?");
                        $stmt->bind_param('i', $beatmapID);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        while($row = $result->fetch_assoc())
                            echo "<li><i class='icon-remove remove-button'></i> {$row["Username"]} <span class='subText mapperid'>{$row["CreatorID"]}</span></li>";
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
		const roles = <?php echo $rolesJson; ?>;
		
        $(document).on("submit", "form", function(event) {
            event.preventDefault();
			let isValid = true;

            const form = $(this);
            const mapperListData = [];
            form.find(".nominatorList li").each(function() {
                const mapperID = $(this).find(".mapperid").text();
                mapperListData.push(mapperID);
            });
			
			const creditsListData = [];
			form.find(".creditList li").each(function() {
				const userID = $(this).attr('data-creatorid');
				const selectedRole = $(this).find(".roles-select").val();
				if (selectedRole == null) {
					alert("Please ensure you've selected a role for every credit.");
					isValid = false;
					return false;
				}
				creditsListData.push({ userID: userID, role: selectedRole });
			});
			
			if (!isValid) {
				return;
			}

            const mapperListInput = $("<input>")
                .attr("type", "hidden")
                .attr("name", "mapperListData")
                .val(JSON.stringify(mapperListData));
            form.append(mapperListInput);

			const creditsListInput = $("<input>")
				.attr("type", "hidden")
				.attr("name", "creditsListData")
				.val(JSON.stringify(creditsListData));

			form.append(creditsListInput);

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

        function addMapperItem(button) {
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
                            const listItem = `<li data-creatorid='${value}'><i class='icon-remove remove-button'></i>  ${username} <span class='subText mapperid'>${value}</span></li>`;
                            list.append(listItem);
                            input.val('');
                        }
                    }
                });
            }
        }
		
		function addCreditItem(button) {
			const beatmapID = $(button).attr("id").split("-").pop();
			const input = $(`#add-credit-input-${beatmapID}`);
			const value = input.val().trim();
			const list = $(button).closest(".flex-container").find(".mapperList");

			if (value !== '') {
				$.ajax({
					type: "GET",
					url: "GetUsernameFromID.php",
					data: { id: value },
					success: function(username) {
						if (username !== '') {
							let options = '';
							roles.forEach(function(role) {
								options += `<option value="${role}">${role}</option>`;
							});
						
							const listItem = `
								<li data-creatorid='${value}'>
									<i class='icon-remove remove-button'></i> 
									${username} 
									<span class='subText mapperid'>${value}</span> 
									<select class='roles-select'>
									<option value="" selected disabled>Select role</option>
                                    ${options}
									</select>
								</li>`;
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