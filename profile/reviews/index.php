<?php
    $PageTitle = "Reviews";

    require "../../base.php";
    require '../../header.php';

	$profileId = GetIntParam('id', -1, "Invalid page bro");
	$page = GetIntParam('p', 1, "Invalid page bro");

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
    $stmt = $conn->prepare("SELECT COUNT(*) FROM `reviews` WHERE `UserID` = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $amntOfPages = floor($count / $limit) + 1;
?>
<center><h1><a href="/profile/<?php echo $profileId; ?>"><?php echo htmlspecialchars(GetUserNameFromId($profileId, $conn), ENT_QUOTES); ?></a>'s reviews</h1></center>

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
				
		$stmt = $conn->prepare("SELECT * FROM `reviews` WHERE `UserID`=? ORDER BY date DESC {$pageString}");
		$stmt->bind_param("s", $profileId);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows != 0) {
			while ($row = $result->fetch_assoc()) {
                $stmt = $conn->prepare("SELECT * FROM `beatmapsets` WHERE `SetID` = ?");
                $stmt->bind_param("i", $row["SetID"]);
                $stmt->execute();
                $beatmap = $stmt->get_result()->fetch_assoc();
				$stmt->close();


				$stmt = $conn->prepare("
                        SELECT
                        COUNT(*) AS totalHearts
                        FROM review_hearts
                        WHERE ReviewID = ?
                    ");
				$stmt->bind_param("i", $row["ReviewID"]);
				$stmt->execute();

				$heartData = $stmt->get_result()->fetch_assoc();
				$stmt->close();

				$reviewHeartCount = (int)$heartData['totalHearts'];
				?>
				<div class="flex-container flex-child commentHeader">
					<div class="flex-child" style="height:24px;width:24px;">
						<a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo htmlspecialchars(GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>"/></a>
					</div>
					<div class="flex-child">
						<a href="/profile/<?php echo $row["UserID"]; ?>"><?php echo htmlspecialchars(GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?></a>  on <a href="../../mapset/<?php echo $row["SetID"]; ?>"><?php echo htmlspecialchars("${beatmap["Artist"]} - ${beatmap["Title"]}", ENT_QUOTES); ?></a>
					</div>
					<div class="flex-child" style="margin-left:auto;">
						<?php if ($row["UserID"] == -1) { ?> <i class="icon-remove removeComment" style="color:#f94141;" value="<?php echo $row["CommentID"]; ?>"></i> <?php } echo GetHumanTime($row["date"]); ?>
					</div>
				</div>
				<div class="flex-child comment" style="min-width:0;overflow: hidden;width: 100%;">
					<div>
						<a href="../../mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" onerror="this.onerror=null; this.src='/charts/INF.png';" style="height:64px;width:64px;float:left;margin:0.5rem;"></a>
					</div>
					<p><?php echo ParseCommentLinks($conn, $row["Comment"]); ?></p>
					<div style="float:right;">
						<span class="subText">[<?php echo $reviewHeartCount; ?>]</span>

						<i
							style="cursor: pointer;"
							id="review-heart"
							class="icon-heart<?php if (!$userHasLikedReview) echo "-empty"; ?>"
							value="<?php echo $row["ReviewID"]; ?>"
						></i>
					</div>
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