<?php
	include_once 'base.php';
	
	$q=$_GET["q"];

    // If it's a link in the query, we should just show the map.
	if(preg_match('/https:\/\/osu\.ppy\.sh\/(beatmapsets|beatmapset|s)\/(\d+)/', $q, $matches)){
		$setID = $matches[2];
		
		$stmt = $conn->prepare("SELECT SetID, Title, Artist FROM `beatmaps` WHERE `SetID`= ?;");
		$stmt->bind_param('s', $setID);
		$stmt->execute();
		$res = $stmt->get_result(); 
		$row = $res->fetch_row();
		$value = $row ? $row : null;
		if ($value == null){
			die("Mapset not found!");
		}
		?>
		<a href="/mapset/<?php echo $setID; ?>"><div style="margin:0;background-color:DarkSlateGrey;" ><?php echo $value[2] . " - " . $value[1]; ?></div></a>
		<?php
		die();
	}

    $stmt = $conn->prepare("(SELECT DISTINCT `UserID`, `Username` FROM users WHERE username LIKE ?) UNION (SELECT DISTINCT `UserID`, `Username` FROM mappernames WHERE username LIKE ?) LIMIT 5;");
    $like = "%$q%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $stmt->bind_result($userID, $username);
    $stmt->store_result();

    if ($stmt->num_rows > 0){
        echo "<div style='background-color:#182828;'><b>Users</b></div>";
        while ($stmt->fetch()) {
            ?>
            <div class="alternating-bg" style="padding:0.25em;display:flex;vertical-align: middle;" ><a href="/profile/<?php echo $userID; ?>" style="display:inline-block;width:100%;height:100%;margin:0;padding:0;"><img src="https://s.ppy.sh/a/<?php echo $userID; ?>" style="height:24px;width:24px;" title="<?php echo $username; ?>"/> <?php echo $username; ?></a></div>
            <?php
        }
    }

    $stmt->close();

	$stmt = $conn->prepare("SELECT `SetID`, Title, Artist, DifficultyName FROM `beatmaps` WHERE MATCH (DifficultyName, Artist, Title) AGAINST(? IN NATURAL LANGUAGE MODE) AND Mode = ? LIMIT 25;");
	$stmt->bind_param("si", $like, $mode);
	$stmt->execute();
	$stmt->bind_result($setId, $title, $artist, $difficultyName);
    $stmt->store_result();

    if ($stmt->num_rows > 0){
        echo "<div style='background-color:#182828;'><b>Maps</b></div>";
        while ($stmt->fetch()) {
            ?>
            <div class="alternating-bg" style="margin:0;" ><a href="/mapset/<?php echo $setId; ?>"><?php echo $artist . " - " . $title . " [" . $difficultyName . "]"; ?></a></div>
            <?php
        }
    }

    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM `lists` WHERE MATCH (Title) AGAINST (? IN NATURAL LANGUAGE MODE) LIMIT 5;");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0){
        echo "<div style='background-color:#182828;'><b>Lists</b></div>";
        while ($row = $result->fetch_assoc()) {
            $stmt = $conn->prepare("SELECT * FROM list_items WHERE `ListID` = ? AND `order` = 1;");
            $stmt->bind_param("i", $row["ListID"]);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();

            list($imageUrl, $title) = getListItemDisplayInformation($item, $conn);
            ?>
            <div class="alternating-bg" style="margin:0;">
                <div>
                    <a href="/list/?id=<?php echo $row["ListID"]; ?>"><?php echo htmlspecialchars($row["Title"]); ?> <span class="subText">by <?php echo GetUserNameFromId($row["UserID"], $conn); ?></span></a>
                </div>
            </div>
            <?php
        }
    }

    $stmt->close();


