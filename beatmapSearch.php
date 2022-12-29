<?php
	include_once 'connection.php';
	
	$q=$_GET["q"];
	
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
	
	// This shit straight from chatgpt
	// Prepare the statement
	$stmt = $conn->prepare("SELECT `SetID`, Title, Artist, DifficultyName FROM `beatmaps` WHERE (DifficultyName LIKE ? OR Artist LIKE ? OR Title LIKE ?) AND Mode='0' ORDER BY CASE WHEN DifficultyName LIKE ? OR Artist LIKE ? OR Title LIKE ? THEN 1 ELSE 2 END LIMIT 25;");

	// Bind the parameters
	$like = "%$q%";
	$stmt->bind_param("ssssss", $like, $like, $like, $q, $q, $q);

	// Execute the statement
	$stmt->execute();

	// Bind the result variables
	$stmt->bind_result($setId, $title, $artist, $difficultyName);

	// Fetch the results
	$counter = 0;
	while ($stmt->fetch()) {
	$counter += 1;
	?>
<a href="/mapset/<?php echo $setId; ?>"><div style="<?php if ($counter % 2 == 0){ echo 'background-color:DarkSlateGrey;'; } else { echo 'background-color:#203838;'; } ?>margin:0;" ><?php echo $artist . " - " . $title . " [" . $difficultyName . "]"; ?></div></a>
	<?php
	}
?>