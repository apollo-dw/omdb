<?php
	$profileId = $_GET['id'] ?? -1;
	$page = $_GET['p'] ?? 1;
    $PageTitle = "Comments";

    require "../../base.php";
    require '../../header.php';
	
	if($profileid == -1 || $rating == -1){
		die("Invalid page bro");
	}

    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
    $isUser = true;

    if ($profile == NULL)
        die("Can't view this bros friends cuz they aint an OMDB user");

	$limit = 25;
	$prevPage = $page - 1;
	$nextPage = $page + 1;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM `comments` WHERE `UserID` = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $amntOfPages = floor($count / $limit) + 1;
?>
<center><h1><a href="/profile/<?php echo $profileId; ?>"><?php echo GetUserNameFromId($profileId, $conn); ?></a>'s comments</h1></center>

<hr>

<div style="text-align:center;">
	<div class="pagination">
	  <b><span><?php if($page > 1) { echo "<a href='?id={$profileId}&p={$prevPage}'>&laquo; </a>"; } ?></span></b>
	  <span id="page"><?php echo $page; ?></span>
	  <b><span><?php if($page < $amntOfPages) { echo "<a href='?id={$profileId}&p={$nextPage}'>&raquo; </a>"; } ?></span></b>
	</div>
</div>

<div class="flex-container commentContainer" style="width:100%;">
	<?php
	
		$pageString = "LIMIT {$limit}";
				
		if ($page > 1){
			$lower = ($page - 1) * $limit;
			$pageString = "LIMIT ${lower}, {$limit}";
		}
				
		$stmt = $conn->prepare("SELECT * FROM `comments` WHERE `UserID`=? ORDER BY date DESC {$pageString}");
		$stmt->bind_param("s", $profileId);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows != 0) {
			while ($row = $result->fetch_assoc()) {
                $stmt = $conn->prepare("SELECT * FROM `beatmaps` WHERE `SetID` = ?");
                $stmt->bind_param("i", $row["SetID"]);
                $stmt->execute();
                $beatmap = $stmt->get_result()->fetch_assoc();
				?>
				<div class="flex-container flex-child commentHeader">
					<div class="flex-child" style="height:24px;width:24px;">
						<a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
					</div>
					<div classz="flex-child">
						<a href="/profile/<?php echo $row["UserID"]; ?>"><?php echo GetUserNameFromId($row["UserID"], $conn); ?></a>  on <a href="../../mapset/<?php echo $row["SetID"]; ?>"><?php echo "${beatmap["Artist"]} - ${beatmap["Title"]}"; ?></a>
					</div>
					<div class="flex-child" style="margin-left:auto;">
						<?php if ($row["UserID"] == -1) { ?> <i class="icon-remove removeComment" style="color:#f94141;" value="<?php echo $row["CommentID"]; ?>"></i> <?php } echo GetHumanTime($row["date"]); ?>
					</div>
				</div>
				<div class="flex-child comment" style="min-width:0;overflow: hidden;">
					<div>
						<a href="../../mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" onerror="this.onerror=null; this.src='/charts/INF.png';" style="height:64px;width:64px;float:left;margin:0.5rem;"></a>
					</div>
					<p><?php echo ParseCommentLinks($conn, nl2br(htmlspecialchars($row["Comment"], ENT_COMPAT, "ISO-8859-1"))); ?></p>
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