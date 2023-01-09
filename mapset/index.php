<?php
	$mapset_id = $_GET['mapset_id'] ?? -1;
	require '../base.php';
	require '../header.php';
	
	if($mapset_id == -1){
		header("Location: https://omdb.nyahh.net/");
	}

    // this needs to be databased instead .
	if($mapset_id == "1063080"){
		die("mapper blacklisted this set from OMDB :(");
	}
	
	$foundSet = false;
	$result = $conn->query("SELECT * FROM `beatmaps` WHERE `SetID`='${mapset_id}' AND `mode`='0' ORDER BY `SR` DESC;");
	$sampleRow = $result->fetch_assoc();

	mysqli_data_seek($result, 0);

	$PageTitle = htmlspecialchars($sampleRow['Title']) . "mapped by" . GetUserNameFromId($sampleRow['CreatorID'], $conn);
    $year = date("Y", strtotime($sampleRow['DateRanked']));
?>

<style>
	.diffContainer{
		background-color:DarkSlateGrey;
		justify-content: space-between;
		align-items: center;
	}
	
	.diffBox{
		padding:0.5em;
		flex-grow: 1;
		height:100%;
	}
	
	.diffBox a{
		color: white;
	}
	
	.unrated{
		color: grey;
	}
	
	ol{
		margin:0;
		padding:0;
	}
	
	li{
		margin:0;
		padding:0;
	}
</style>

<center><h1><a target="_blank" rel="noopener noreferrer" href="https://osu.ppy.sh/s/<?php echo $sampleRow['SetID']; ?>"><?php echo $sampleRow['Artist'] . " - " . htmlspecialchars($sampleRow['Title']) . "</a> by <a href='/profile/{$sampleRow['CreatorID']}'>" .  GetUserNameFromId($sampleRow['CreatorID'], $conn); ?></a></h1></center>

<div class="flex-container" style="justify-content: center;">
	<div class="flex-child">
		<img src="https://assets.ppy.sh/beatmaps/<?php echo $sampleRow['SetID']; ?>/covers/cover.jpg" style="height:6rem;width:21.6rem;border-radius:16px;" onerror="this.onerror=null; this.src='INF.png';" />
	</div>
	<div class="flex-child">
		Ranked: <?php echo date("M jS, Y", strtotime($sampleRow['DateRanked'])); ?><br>
		Average Rating: <b><?php echo $conn->query("SELECT ROUND(AVG(Score), 2) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID='${mapset_id}');")->fetch_row()[0]; ?></b> <span style="font-size:12px;color:grey;">/ 5.00 from <?php echo $conn->query("SELECT Count(*) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID='${mapset_id}');")->fetch_row()[0]; ?> votes</span><br>
	</div>
</div>
<br>
<hr style="margin-bottom:1em;">

<?php
	$counter = 0;
	while($row = $result->fetch_assoc()) {
		$ratedQueryResult = $conn->query("SELECT * FROM `ratings` WHERE `BeatmapID`='${row["BeatmapID"]}' AND `UserID`='${userId}';");
		$userHasRatedThis = $ratedQueryResult->num_rows == 1 ? true : false;
		$userMapRating = $ratedQueryResult->fetch_row()[3] ?? -1;
		$counter += 1;
?>

<div class="flex-container diffContainer" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
	<div class="flex-child diffBox" style="text-align:center;width:70%;">
		<a href="https://osu.ppy.sh/b/<?php echo $row['BeatmapID']; ?>" target="_blank" rel="noopener noreferrer"><b><?php echo mb_strimwidth(htmlspecialchars($row['DifficultyName']), 0, 35, "..."); ?></b></a> <a href="osu://b/<?php echo $row['BeatmapID']; ?>"><i class="icon-download-alt">&ZeroWidthSpace;</i></a> <span class="subText"><?php echo number_format((float)$row['SR'], 2, '.', ''); ?>*</span>
	</div>
	<div class="flex-child diffBox">
	</div>
	<div class="flex-child diffBox" style="text-align:right;width:40%;">
		Rating: <b><?php echo number_format($conn->query("SELECT WeightedAvg FROM beatmaps WHERE `BeatmapID`='${row["BeatmapID"]}';")->fetch_row()[0], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $conn->query("SELECT RatingCount FROM `beatmaps` WHERE `BeatmapID`='${row["BeatmapID"]}';")->fetch_row()[0]; ?></span> votes</span><br>
		Ranking: <b>#<?php echo $conn->query("SELECT ChartYearRank from beatmaps WHERE `BeatmapID`='${row["BeatmapID"]}';")->fetch_row()[0]; ?></b> for <a href="/charts/?y=<?php echo $year;?>"><?php echo $year;?></a>, <b>#<?php echo $conn->query("SELECT ChartRank from beatmaps WHERE `BeatmapID`='${row["BeatmapID"]}';")->fetch_row()[0]; ?></b> <a href="/charts/">overall</a>
	</div>
	<div class="flex-child diffBox" style="padding:auto;width:30%;">
		<?php
			if($loggedIn){
		?>
		<span class="identifier" style="display: inline-block;"><ol class="star-rating-list <?php if(!$userHasRatedThis) { echo 'unrated'; } ?>" beatmapid="<?php echo $row["BeatmapID"]; ?>" rating="<?php echo $userMapRating; ?>">
		<!-- The holy grail of PHP code. If I want to make this public on github i NEED to rewrite this-->
		<i class="icon-remove" style="opacity:0;"></i><li class="star icon-star<?php if($userMapRating==0 || !$userHasRatedThis){ echo '-empty'; } else if($userMapRating==0.5){ echo '-half-empty'; } ?>" value="1" /><li class="star icon-star<?php if($userMapRating<=1){ echo '-empty'; } else if($userMapRating==1.5){ echo '-half-empty'; } ?>" value="2" /><li class="star icon-star<?php if($userMapRating<=2){ echo '-empty'; } else if($userMapRating==2.5){ echo '-half-empty'; } ?>" value="3" /><li class="star icon-star<?php if($userMapRating<=3){ echo '-empty'; } else if($userMapRating==3.5){ echo '-half-empty'; } ?>" value="4" /><li class="star icon-star<?php if($userMapRating<=4){ echo '-empty'; } else if($userMapRating==4.5){ echo '-half-empty'; } ?>" value="5" />
		</ol></span><span class="starRemoveButton <?php if(!$userHasRatedThis) { echo 'disabled'; } ?>" beatmapid="<?php echo $row["BeatmapID"]; ?>"><i class="icon-remove"></i></span><span style="display: inline-block; padding-left:0.25em;" class="star-value <?php if(!$userHasRatedThis) { echo 'unrated'; } ?>"><?php if($userHasRatedThis){ echo $userMapRating; } else { echo '&ZeroWidthSpace;'; } ?></span>
		<?php
			} else {
				echo 'Log in to rate maps!';
			}
		?>
	</div>
</div>

<?php
	}
?>

<hr style="margin-bottom:1em;margin-top:1em;">

<div class="flex-container">
	<div class="flex-child" style="width:40%;">
		Latest Ratings<br><br>
			<?php
  $counter = 0;

  $stmt = $conn->prepare("SELECT * FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID=?) ORDER BY date DESC LIMIT 18;");
  $stmt->bind_param("s", $mapset_id);
  $stmt->execute();
  $result = $stmt->get_result();

  while($row = $result->fetch_assoc()) {
    $counter += 1;
?>
  <div class="flex-container ratingContainer" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
    <div class="flex-child">
      <a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
    </div>
    <div class="flex-child" style="flex:0 0 70%;">
      <?php
        $stmt2 = $conn->prepare("SELECT DifficultyName FROM `beatmaps` WHERE `BeatmapID`=?");
        $stmt2->bind_param("s", $row["BeatmapID"]);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $row2 = $result2->fetch_row();
        echo $row["Score"] . " on " . htmlspecialchars($row2[0]);
      ?>
    </div>
    <div class="flex-child" style="width:100%;text-align:right;">
      <?php echo GetHumanTime($row["date"]); ?>
    </div>
  </div>
  <?php
    }
  ?>
	</div>
	<div class="flex-child" style="width:60%;">
		Comments<br><br>
		<div class="flex-container commentContainer" style="width:100%;">
		
			<?php if($loggedIn) { ?>
			
			<div class="flex-child commentComposer">
				<form>
					<textarea id="commentForm" name="commentForm" placeholder="Write your comment here!" value="" autocomplete='off'></textarea>
					
					<input type='button' name="commentSubmit" id="commentSubmit" value="Post" onclick="submitComment()" />
				</form>
			</div>
			
			<?php } ?>
			
			<?php
				$stmt = $conn->prepare("SELECT * FROM `comments` WHERE SetID=? ORDER BY date DESC");
				$stmt->bind_param("s", $sampleRow["SetID"]);
				$stmt->execute();
				$result = $stmt->get_result();
				if ($result->num_rows != 0) {
					while ($row = $result->fetch_assoc()) {
						?>
						<div class="flex-container flex-child commentHeader">
							<div class="flex-child" style="height:24px;width:24px;">
								<a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
							</div>
							<div class="flex-child">
								<a href="/profile/<?php echo $row["UserID"]; ?>"><?php echo GetUserNameFromId($row["UserID"], $conn); ?></a>
							</div>
							<div class="flex-child" style="margin-left:auto;">
								<?php if ($row["UserID"] == $userId) { ?> <i class="icon-remove removeComment" style="color:#f94141;" value="<?php echo $row["CommentID"]; ?>"></i> <?php } echo GetHumanTime($row["date"]); ?>
							</div>
						</div>
						<div class="flex-child comment" style="min-width:0;overflow: hidden;">
							<p><?php echo ParseOsuLinks(nl2br(htmlspecialchars($row["Comment"], ENT_COMPAT, "ISO-8859-1"))); ?></p>
						</div>
						<?php
					}
				}
			?>
			
		</div>
	</div>
</div>

<script>
	function submitComment(){
		console.log("yeah");
		var text = encodeURIComponent($('#commentForm').val());
		console.log(text);
		
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				location.reload();
   			}
  		};
		
		if (text.length > 3 && text.length < 8000){
			$('#commentSubmit').prop('disabled', true);
			xhttp.open("POST", "SubmitComment.php", true);
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("sID=" + <?php echo $sampleRow["SetID"]; ?> + "&comment=" + text);
		}
	}

    $('#commentForm').keydown(function (event) {
        if ((event.keyCode == 10 || event.keyCode == 13) && event.ctrlKey)
            submitComment();
    });
		
	$(".removeComment").click(function(event){
		var $this = $(this);
		
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				console.log(this.responseText);
				location.reload();
   			}
  		};
		
  		xhttp.open("POST", "RemoveComment.php", true);
  		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  		xhttp.send("sID=" + <?php echo $sampleRow["SetID"]; ?> + "&cID=" + $this.attr('value'));
		
	});
	
	$(".star-rating-list").mousemove(function(event){
		var $this = $(this);
		var sel = event.target.value;
		var $options = $this.find(".star");
		var rating = 0;
		
		for (var i = 0; i < 5; i++) {
    		if (i < sel) {
    			if (event.pageX - event.target.getBoundingClientRect().left<= 6 && sel-1 == i) {
       				$options.eq(i).attr('class', 'star icon-star-half-empty');
					rating += 0.5;
     			} else {
        			$options.eq(i).attr('class', 'star icon-star');
					rating += 1;
      			}
    		} else {
      			$options.eq(i).attr('class', 'star icon-star-empty');
    		}
  		}
		$this.parent().parent().find('.star-value').html(rating.toFixed(1));
	});
	
	$(".star-rating-list").mouseleave(function(event){
		var $this = $(this);
		var sel = $this.attr("rating");
		var $options = $this.find(".star");
		
		for (var i = 0; i < 5; i++) {
    		if (i < sel) {
    			if (sel-0.5 == i) {
       				$options.eq(i).attr('class', 'star icon-star-half-empty');
     			} else {
        			$options.eq(i).attr('class', 'star icon-star');
      			}
    		} else {
      			$options.eq(i).attr('class', 'star icon-star-empty');
    		}
  		}
		
		if (sel == -1){
			$this.parent().parent().find('.star-value').html("&ZeroWidthSpace;");
		}else{
			$this.parent().parent().find('.star-value').html(sel);
		}
	});
	
	$(".starRemoveButton").click(function(event){
		var $this = $(this);
		var bID = $(this).attr("beatmapid");
		
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
      			console.log(this.responseText);
				
				$this.addClass("disabled");
				$this.parent().find('.star-value').html("&ZeroWidthSpace;");
				$this.parent().find('.star-value').addClass("unrated");
				$this.parent().find('.identifier').find('.star-rating-list').addClass("unrated");
   			}
  		};
		
		$this.attr("rating", "");
  		xhttp.open("POST", "SubmitRating.php", true);
  		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  		xhttp.send("bID=" + bID + "&rating=" + -2);
		$this.parent().find('.star-value').html("removing...");
		
	});
	
	$(".star-rating-list").click(function(event){
		var $this = $(this);
		var bID = $(this).attr("beatmapid");
		var sel = event.target.value;
		var rating = 0;

		for (var i = 0; i < 5; i++) {
    		if (i < sel) {
    			if (event.pageX - event.target.getBoundingClientRect().left <= 6 && sel-1 == i) {
       				rating += 0.5;
     			} else {
        			rating += 1;
      			}
    		}
  		}
		
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
      			console.log(this.responseText);
				
				$this.removeClass("unrated");
				$this.parent().parent().find('.star-value').removeClass("unrated");
				$this.parent().parent().find('.star-value').html(rating.toFixed(1));
				$this.parent().parent().find('.starRemoveButton').removeClass("disabled");
   			}
  		};
		
		$this.attr("rating", rating.toFixed(1));
  		xhttp.open("POST", "SubmitRating.php", true);
  		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  		xhttp.send("bID=" + bID + "&rating=" + rating);
		$this.parent().parent().find('.star-value').html("rating...");
		
	});
</script>

<?php
    require '../footer.php';
?>