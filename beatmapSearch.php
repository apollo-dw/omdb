<?php
	include_once 'base.php';
	
	$q=$_GET["q"] ?? "";
    if ($q === "") {
        die("Ok Buddy");
    }

    // If it's a link in the query, we should just show the map.
	if(preg_match('/https:\/\/osu\.ppy\.sh\/(beatmapsets|beatmapset|s)\/(\d+)/', $q, $matches)){
		$setID = $matches[2];
		
		$stmt = $conn->prepare("SELECT SetID, Title, Artist FROM `beatmapsets` WHERE `SetID`= ?;");
		$stmt->bind_param('s', $setID);
		$stmt->execute();
		$res = $stmt->get_result(); 
		$row = $res->fetch_row();
		$value = $row ? $row : null;
		if ($value == null){
			die("Mapset not found!");
		}
		?>
		<a href="/mapset/<?php echo $setID; ?>"><div style="margin:0;background-color:DarkSlateGrey;" ><?php echo safe_htmlspecialchars($value[2] . " - " . $value[1], ENT_QUOTES); ?></div></a>
		<?php
		die();
	}
    $like = "%$q%";

    $stmt = $conn->prepare("SELECT `DescriptorID`, `Name`
        FROM `descriptors`
        WHERE `Usable` = 1 AND `Name` LIKE ?
        ORDER BY (`Name` = ?) DESC, LENGTH(`Name`) ASC
        LIMIT 5;
    ");
    $stmt->bind_param("ss", $like, $q);
    $stmt->execute();
    $stmt->bind_result($descriptorID, $descriptorName);
    $stmt->store_result();

    if ($stmt->num_rows > 0){
        echo "<div style='background-color:#182828;'><b>Descriptors</b></div>";
        while ($stmt->fetch()) {
            ?>
            <div class="alternating-bg" style="padding:0.25em;margin:0;" ><a href="/descriptor/?id=<?php echo $descriptorID; ?>" style="display:block;width:100%;height:100%;"><?php echo safe_htmlspecialchars($descriptorName, ENT_QUOTES); ?></a></div>
            <?php
        }
    }
    $stmt->close();

    $sql = "
        (SELECT `UserID`, `Username` FROM users WHERE username LIKE ? LIMIT 5)
        UNION ALL
        (SELECT `UserID`, `Username` FROM mappernames WHERE username LIKE ? LIMIT 5)
        LIMIT 5;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $stmt->bind_result($userID, $username);
    $stmt->store_result();

    $seenUsers = [];
    if ($stmt->num_rows > 0) {
        echo "<div style='background-color:#182828;'><b>Users</b></div>";
        while ($stmt->fetch()) {
            if (isset($seenUsers[$userID])) 
                continue;
            
            $seenUsers[$userID] = true;
            ?>
            <div class="alternating-bg" style="padding:0.25em;display:flex;vertical-align: middle;">
                <a href="/profile/<?php echo $userID; ?>" style="display:inline-block;width:100%;height:100%;margin:0;padding:0;">
                    <img src="https://s.ppy.sh/a/<?php echo $userID; ?>" style="height:24px;width:24px;" title="<?php echo safe_htmlspecialchars($username, ENT_QUOTES); ?>"/> 
                    <?php echo safe_htmlspecialchars($username, ENT_QUOTES); ?>
                </a>
            </div>
            <?php
        }
    }
    $stmt->close();
            
    $types = "";
    $params = [];
    $sql = "SELECT s.`SetID`, s.Title, s.Artist, s.CreatorID, COALESCE(mn.Username, u.Username) AS MapperName
            FROM `beatmapsets` s
            LEFT JOIN users u ON u.UserID = s.CreatorID
            LEFT JOIN mappernames mn ON mn.UserID = s.CreatorID
            INNER JOIN (
                SELECT b.SetID, MAX(b.RatingCount) as MaxRating
                FROM `beatmaps` b
                LEFT JOIN beatmapsets s2 ON b.SetID = s2.SetID
                LEFT JOIN beatmap_creators bc ON b.BeatmapID = bc.BeatmapID
                LEFT JOIN users u2 ON u2.UserID = COALESCE(bc.CreatorID, s2.CreatorID)
                LEFT JOIN mappernames mn2 ON mn2.UserID = COALESCE(bc.CreatorID, s2.CreatorID)
                WHERE b.Mode = ?";
                
    $types .= "i";
    $params[] = $mode;

    $terms = array_slice(array_filter(explode(" ", $q)), 0, 5);
    $termClauses = [];
    
    foreach ($terms as $term) {
        $likeTerm = "%" . addcslashes($term, '%_\\') . "%";
        $textSearch = "(s2.Artist LIKE ? OR s2.Title LIKE ? OR b.DifficultyName LIKE ? OR u2.Username LIKE ? OR mn2.Username LIKE ?)";
        
        if (is_numeric($term)) {
            $termClauses[] = "(" . $textSearch . " OR b.BeatmapID = ? OR s2.SetID = ? OR s2.CreatorID = ? OR bc.CreatorID = ?)";
            $types .= "sssssiiii";
            array_push($params, $likeTerm, $likeTerm, $likeTerm, $likeTerm, $likeTerm, (int)$term, (int)$term, (int)$term, (int)$term);
        } else {
            $termClauses[] = $textSearch;
            $types .= "sssss";
            array_push($params, $likeTerm, $likeTerm, $likeTerm, $likeTerm, $likeTerm);
        }
    }

    if (!empty($termClauses)) {
        $sql .= " AND " . implode(" AND ", $termClauses);
    }

    $sql .= " GROUP BY b.SetID 
              ORDER BY MaxRating DESC 
              LIMIT 25
            ) as top_sets ON s.SetID = top_sets.SetID
            ORDER BY top_sets.MaxRating DESC;";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    // Added $mapperName directly to the bound results
    $stmt->bind_result($setId, $title, $artist, $hostId, $mapperName);
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<div style='background-color:#182828;'><b>Maps</b></div>";
        while ($stmt->fetch()) {
            // If both tables lacked a username for some reason, fallback to a placeholder
            $displayName = $mapperName ?? "Unknown Mapper";
            ?>
            <div class="alternating-bg" style="margin:0;">
                <a href="/mapset/<?php echo $setId; ?>">
                    <?php echo safe_htmlspecialchars($artist . " - " . $title . " (" . $displayName . ")", ENT_QUOTES); ?>
                </a>
            </div>
            <?php
        }
    }

    $stmt->close();

    $stmt = $conn->prepare("SELECT l.ListID, l.Title, l.UserID, mn.Username FROM `lists` l LEFT JOIN mappernames mn on l.UserID = mn.UserID WHERE MATCH (Title) AGAINST (? IN NATURAL LANGUAGE MODE) AND (`Private` = 0 OR l.`UserID` = ?) LIMIT 5;");
    $stmt->bind_param("si", $like, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0){
        echo "<div style='background-color:#182828;'><b>Lists</b></div>";
        while ($row = $result->fetch_assoc()) {
            $stmt = $conn->prepare("SELECT * FROM list_items WHERE `ListID` = ? AND `order` = 1;");
            $stmt->bind_param("i", $row["ListID"]);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();

            list($imageUrl, $title, $linkUrl) = getListItemDisplayInformation($item, $conn);
            ?>
            <div class="alternating-bg" style="margin:0;">
                <div>
                    <a href="/list/?id=<?php echo $row["ListID"]; ?>"><?php echo safe_htmlspecialchars($row["Title"], ENT_QUOTES); ?> <span class="subText">by <?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?></span></a>
                </div>
            </div>
            <?php
        }
    }
    $stmt->close();
?>
