<?php
	$profileId = $_GET['id'] ?? -1;
	$page = $_GET['p'] ?? 1;
    $PageTitle = "Comments";
	
    require '../../header.php';
	
	if($profileid == -1 || $rating == -1){
		die("Invalid page bro");
	}
	
	$profile = $conn->query("SELECT * FROM `users` WHERE `UserID`='${profileId}';")->fetch_row()[0];
	$isUser = true;
	
	if ($profile == NULL){
		die("Can't view this bros comments cuz they aint an OMDB user");
	}

	$limit = 25;
	$prevPage = $page - 1;
	$nextPage = $page + 1;
	$amntOfPages = floor($conn->query("SELECT Count(*) FROM `comments` WHERE `UserID`='${profileId}';")->fetch_row()[0] / $limit) + 1;
?>
<center><h1><a href="/profile/<?php echo $profileId; ?>"><?php echo GetUserNameFromId($profileId, $conn); ?></a>'s comments</h1></center>

<hr>

<style>
	.pagination {
		display: inline-block;
		color: white;
	}
	
	.pagination a{
		color: white;
	}

	.pagination span {
		float: left;
		padding: 8px 16px;
		width: 1em;
		text-decoration: none;
	}
	
	.flex-child a{
		color: white;
	}
</style>

<div style="text-align:center;">
	<div class="pagination">
	  <b><span><?php if($page > 1) { echo "<a href='?id=${profileId}&p=${prevPage}'>&laquo; </a>"; } ?></span></b>
	  <span id="page"><?php echo $page; ?></span>
	  <b><span><?php if($page < $amntOfPages) { echo "<a href='?id=${profileId}&p=${nextPage}'>&raquo; </a>"; } ?></span></b>
	</div>
</div>

<div class="flex-container commentContainer" style="width:100%;">
	<?php
	
		$pageString = "LIMIT ${limit}";
				
		if ($page > 1){
			$lower = ($page - 1) * $limit;
			$pageString = "LIMIT ${lower}, ${limit}";
		}
				
		$stmt = $conn->prepare("SELECT * FROM `comments` WHERE `UserID`=? ORDER BY date DESC ${pageString}");
		$stmt->bind_param("s", $profileId);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows != 0) {
			while ($row = $result->fetch_assoc()) {
				$beatmap = $conn->query("SELECT * FROM `beatmaps` WHERE `SetID`='${row["SetID"]}';")->fetch_assoc();
				?>
				<div class="flex-container flex-child commentHeader">
					<div class="flex-child" style="height:24px;width:24px;">
						<a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
					</div>
					<div classz="flex-child">
						<a href="/profile/<?php echo $row["UserID"]; ?>"><?php echo GetUserNameFromId($row["UserID"], $conn); ?></a>  on <a href="../../mapset/<?php echo $row["SetID"]; ?>"><?php echo "${beatmap["Artist"]} - ${beatmap["Title"]}"; ?></a>
					</div>
					<div class="flex-child" style="margin-left:auto;">
						<?php if ($row["UserID"] == -1) { ?> <i class="icon-remove removeComment" style="color:#f94141;" value="<?php echo $row["CommentID"]; ?>"></i> <?php } echo time_elapsed_string($row["date"]); ?>
					</div>
				</div>
				<div class="flex-child comment" style="min-width:0;overflow: hidden;">
					<div>
						<a href="../../mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" onerror="this.onerror=null; this.src='/charts/INF.png';" style="height:64px;width:64px;float:left;margin:0.5rem;"></a>
					</div>
					<p><?php echo parseOsuLinks(nl2br(htmlspecialchars($row["Comment"], ENT_COMPAT, "ISO-8859-1"))); ?></p>
				</div>
				<?php
			}
		}
	?>
</div>

<div style="text-align:center;">
	<div class="pagination">
	  <b><span><?php if($page > 1) { echo "<a href='?id=${profileId}&p=${prevPage}'>&laquo; </a>"; } ?></span></b>
	  <span id="page"><?php echo $page; ?></span>
	  <b><span><?php if($page < $amntOfPages) { echo "<a href='?id=${profileId}&p=${nextPage}'>&raquo; </a>"; } ?></span></b>
	</div>
</div>


<?php
	require '../../footer.php';
?>