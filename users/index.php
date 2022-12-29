<?php
    $PageTitle = "Users";
    require '../header.php';
?>

<p>im spying on who signed up here :3</p>

	<?php
		$result = $conn->query("SELECT * FROM `users` ORDER BY Username");
		while($row = $result->fetch_assoc()){		
	?>
		<a href="/profile/<?php echo $row['UserID']; ?>"  target='_blank' rel='noopener noreferrer'>
			<?php echo $row["Username"]; ?> <br>
		</a>
	<?php
		}
	?>