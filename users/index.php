<?php
	require("../header.php");
?>

<h2>adfgfadg </h2>
<style>
        table, tr, td, th{
            border-spacing: 0;
            padding: 0.5em;
        }
</style>
	<?php 
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
		$stmt = $conn->prepare("SELECT U.userid, U.username, COUNT(R.userid) AS ratings_count FROM users U LEFT JOIN ratings R ON U.userid = R.userid GROUP BY U.userid, U.username ORDER BY ratings_count DESC LIMIT 100;");
		$stmt->execute();
		$result = $stmt->get_result();
		$count = 0;
		?>
		
		<table style="width:100%;">
			<tr class='alternating-bg'>
				<th></th>
				<th></th>
				<th>Username</th>
				<th>Ratings Count</th>
				<th>Descriptor Vote Count</th>
				<th>Edit Count</th>
			</tr>
		
		<?php while ($row = $result->fetch_assoc()) { 
			$count += 1;
			
			$stmt = $conn->prepare("SELECT COUNT(*) as count FROM descriptor_votes WHERE UserID = ?;");
			$stmt->bind_param("i", $row["userid"]);
			$stmt->execute();
			$descriptorCount = $stmt->get_result()->fetch_assoc()["count"];			
			
			$stmt = $conn->prepare("SELECT COUNT(*) as count FROM beatmap_edit_requests WHERE UserID = ? and Status='approved';");
			$stmt->bind_param("i", $row["userid"]);
			$stmt->execute();
			$editCount = $stmt->get_result()->fetch_assoc()["count"];
		?>
			<tr class='alternating-bg'>
				<td>#<?php echo $count; ?></td>
				<td>
					<a style="display:flex;" href="/profile/<?php echo $row["userid"]; ?>">
                        <img src="https://s.ppy.sh/a/<?php echo $row["userid"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["userid"], $conn); ?>"/>
                    </a>
				</td>
				<td>
					<a href="/profile/<?php echo $row["userid"]; ?>">
						<?php echo $row["username"]; ?>
					</a>
				</td>
				<td><?php echo $row["ratings_count"]; ?></td>
				<td><?php echo $descriptorCount; ?></td>
				<td><?php echo $editCount; ?></td>
			</tr>
		<?php } ?>
	</table>
<?php
	require("../footer.php");
?>