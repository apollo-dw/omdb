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
            <div class="alternating-bg" style="padding:0.25em;margin:0;" ><a href="/descriptors/<?php echo $descriptorID; ?>" style="display:block;width:100%;height:100%;"><?php echo safe_htmlspecialchars($descriptorName, ENT_QUOTES); ?></a></div>
            <?php
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("
        (SELECT DISTINCT `UserID`, `Username` FROM users WHERE username LIKE ? OR UserID = ?)
        UNION
        (SELECT DISTINCT `UserID`, `Username` FROM mappernames WHERE username LIKE ? OR UserID = ?)
        LIMIT 5;
    ");
    $idMatch = ctype_digit($q) ? (int)$q : null;
    $stmt->bind_param("sisi", $like, $idMatch, $like, $idMatch);
    $stmt->execute();
    $stmt->bind_result($userID, $username);
    $stmt->store_result();

    if ($stmt->num_rows > 0){
        echo "<div style='background-color:#182828;'><b>Users</b></div>";
        while ($stmt->fetch()) {
            ?>
            <div class="alternating-bg" style="padding:0.25em;display:flex;vertical-align: middle;" ><a href="/profile/<?php echo $userID; ?>" style="display:inline-block;width:100%;height:100%;margin:0;padding:0;"><img src="https://s.ppy.sh/a/<?php echo $userID; ?>" style="height:24px;width:24px;" title="<?php echo safe_htmlspecialchars($username, ENT_QUOTES); ?>"/> <?php echo safe_htmlspecialchars($username, ENT_QUOTES); ?></a></div>
            <?php
        }
    }
    $stmt->close();

    $types = "";
    $params = [];
    $sql = "SELECT s.`SetID`, s.Title, s.Artist, s.CreatorID
            FROM `beatmaps` b 
            LEFT JOIN beatmapsets s ON b.SetID = s.SetID 
            WHERE b.Mode = ?";
            
    $types .= "i";
    $params[] = $mode;

    // Only check the first 10 terms basically
    $terms = array_slice(array_filter(explode(" ", $q)), 0, 10);
    $termClauses = [];
    foreach ($terms as $term) {
        $likeTerm = "%" . addcslashes($term, '%_\\') . "%";
        if (is_numeric($term)) { // Potentially just bID/sID
            $termClauses[] = "(CONCAT_WS(' ', s.Artist, s.Title, b.DifficultyName) LIKE ? OR b.BeatmapID = ? OR s.SetID = ?)";
            $types .= "sii";
            $params[] = $likeTerm;
            $params[] = (int)$term;
            $params[] = (int)$term;
        } else {
            $termClauses[] = "(CONCAT_WS(' ', s.Artist, s.Title, b.DifficultyName) LIKE ?)";
            $types .= "s";
            $params[] = $likeTerm;
        }
    }

    if (!empty($termClauses)) {
        $sql .= " AND " . implode(" AND ", $termClauses);
    }

    // Collapse to one row per set + sort sets by their most popular diff.
    $sql .= " GROUP BY s.SetID ORDER BY MAX(b.RatingCount) DESC LIMIT 25;";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $stmt->bind_result($setId, $title, $artist, $hostId);
    $stmt->store_result();

    if ($stmt->num_rows > 0){
        echo "<div style='background-color:#182828;'><b>Maps</b></div>";
        while ($stmt->fetch()) {
            $mapperName = GetUserNameFromId($hostId, $conn);
            ?>
            <div class="alternating-bg" style="margin:0;" ><a href="/mapset/<?php echo $setId; ?>"><?php echo safe_htmlspecialchars($artist . " - " . $title . " (" . $mapperName . ")", ENT_QUOTES); ?></a></div>
            <?php
        }
    }

    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM `lists` WHERE MATCH (Title) AGAINST (? IN NATURAL LANGUAGE MODE) AND (`Private` = 0 OR `UserID` = ?) LIMIT 5;");
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
                    <a href="/list/?id=<?php echo $row["ListID"]; ?>"><?php echo safe_htmlspecialchars($row["Title"], ENT_QUOTES); ?> <span class="subText">by <?php echo safe_htmlspecialchars(GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?></span></a>
                </div>
            </div>
            <?php
        }
    }
    $stmt->close();
?>