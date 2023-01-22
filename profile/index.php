<?php
	$profileId = $_GET['id'] ?? -1;
    $PageTitle = "Profile";
	
	if($profileId == -1 || !is_numeric($profileId))
		header("Location: https://omdb.nyahh.net/");

	require "../base.php";
    require '../header.php';
	
	$profile = $conn->query("SELECT * FROM `users` WHERE `UserID`='${profileId}';")->fetch_assoc();
	$isUser = true;
	
	if ($profile == NULL)
		$isUser = false;
	
	$ratingCounts = array();

    if ($isUser) {
        $query = "SELECT `Score`, COUNT(*) as count FROM `ratings` WHERE `UserID`='{$profileId}' GROUP BY `Score`";
        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {
            $ratingCounts[$row['Score']] = $row['count'];
        }

        $maxRating = max($ratingCounts);
    }
	
 	if ($loggedIn && $profileId != $userId){
		$userScores = array();
		$profileScores = array();

		// Prepare the SELECT statement
		$stmt = $conn->prepare("SELECT r1.Score, r2.Score FROM ratings r1 JOIN ratings r2 ON r1.BeatmapID = r2.BeatmapID WHERE r1.UserID = ? AND r2.UserID = ?");

		// Bind the parameters for the prepared statement
		$stmt->bind_param("ii", $userId, $profileId);

		// Execute the prepared statement
		$stmt->execute();

		// Bind the result variables
		$stmt->bind_result($score1, $score2);

		// Fetch the rows and add the scores to the arrays
		while ($stmt->fetch()) {
		  $userScores[] = $score1;
		  $profileScores[] = $score2;
		}

		// Close the prepared statement
		$stmt->close();
		
		$correlation = CalculatePearsonCorrelation($userScores, $profileScores);
	} 
?>

<style>
	.profileContainer{
		display: flex;
		height:44em;
	}
	
	.profileCard{
		display: inline-flex;
		flex-direction: column;
		border:1px solid DarkSlateGrey;
		padding:1.5em;
		text-align: center;
		width: 16rem;
		margin: 0.5rem;
		align-items: center;
	}
	
	.ratingsCard{
		background-color: DarkSlateGrey;
		padding:1.5em;
		margin: 0.5rem;
		width:100%;
		overflow-y: scroll;
	}
	
	.profileStats{
		text-align: left;
		margin: 0.5em;
	}
	
	.beatmapCard{
		margin:0.5rem;
		display:inline-block;
		background-size: cover;
		width:50%;
		padding: 2em;
		text-align:center;
		color:white;
		font-size: 16px;
		font-weight: 900;
		text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
	}
	
	.ratingChoices{
		display: inline-block;
		color: white;
		margin-bottom:0.5rem;
	}
	
	.ratingChoice{
		border:1px solid white;
		padding:0.1em 0.5em;
		min-width:0.2em;
		cursor:pointer;
		font-size:10px;
		color: white;
	}
	
	.active{
		background-color: #203838;
		font-weight: 900;
	}
	
	.profileRankingDistribution{
		border:1px solid DarkSlateGrey;
		width:14em;
		height:14em;
		padding:0px;
		color:rgba(125, 125, 125, 0.66);
        overflow: clip;
	}
	
	.profileRankingDistribution a{
		color:rgba(125, 125, 125, 0.66);
	}
	
	.profileRankingDistributionBar{
		height: calc(100% / 11);
		width:100%;
		margin:0px;
		padding:0px;
		text-align:left;
		background-color:#282828;
		padding-left:0.25em;
        white-space: nowrap;
        text-overflow: ellipsis;
	}

    .verticalLine{
        height: 100%;
        margin: 0;
        padding: 0;
        border-left: 1px solid rgba(255, 255, 255, 0.25);
        position: relative;
        bottom: 100%;
        display: inline-block;
    }
</style>

<div class="profileContainer">
	<div class="profileCard">
		<div class="profileTitle">
            <a href="https://osu.ppy.sh/u/<?php echo $profileId; ?>" target="_blank" rel="noopener noreferrer"><?php echo GetUserNameFromId($profileId, $conn); ?></a> <a href="https://osu.ppy.sh/u/<?php echo $profileId; ?>" target="_blank" rel="noopener noreferrer"><i class="icon-external-link" style="font-size:10px;"></i></a>
		</div>
		<div class="profileImage">
			<img src="https://s.ppy.sh/a/<?php echo $profileId; ?>" style="width:146px;height:146px;"/>
		</div>
		<div class="profileStats">
			<b>Ratings:</b> <?php echo $conn->query("SELECT Count(*) FROM `ratings` WHERE `UserID`='{$profileId}';")->fetch_row()[0]; ?><br>
			<a href="comments/?id=<?php echo $profileId; ?>"><b>Comments:</b> <?php echo $conn->query("SELECT Count(*) FROM `comments` WHERE `UserID`='{$profileId}';")->fetch_row()[0]; ?></a><br>
			<b>Ranked Mapsets:</b> <?php echo $conn->query("SELECT Count(DISTINCT SetID) FROM `beatmaps` WHERE `CreatorID`='{$profileId}';")->fetch_row()[0]; ?><br>
		</div>
		<?php
			if ($isUser){
		?>
			<div class="profileRankingDistribution" style="margin-bottom:0.5em;">
                <div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["5.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=5.0&p=1">5.0 <?php if ($profile["Custom50Rating"] != "") { echo " - " . $profile["Custom50Rating"]; } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["4.5"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=4.5&p=1">4.5 <?php if ($profile["Custom45Rating"] != "") { echo " - " . $profile["Custom45Rating"]; } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["4.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=4.0&p=1">4.0 <?php if ($profile["Custom40Rating"] != "") { echo " - " . $profile["Custom40Rating"]; } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["3.5"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=3.5&p=1">3.5 <?php if ($profile["Custom35Rating"] != "") { echo " - " . $profile["Custom35Rating"]; } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["3.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=3.0&p=1">3.0 <?php if ($profile["Custom30Rating"] != "") { echo " - " . $profile["Custom30Rating"]; } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["2.5"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=2.5&p=1">2.5 <?php if ($profile["Custom25Rating"] != "") { echo " - " . $profile["Custom25Rating"]; } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["2.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=2.0&p=1">2.0 <?php if ($profile["Custom20Rating"] != "") { echo " - " . $profile["Custom20Rating"]; } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["1.5"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=1.5&p=1">1.5 <?php if ($profile["Custom15Rating"] != "") { echo " - " . $profile["Custom15Rating"]; } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["1.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=1.0&p=1">1.0 <?php if ($profile["Custom10Rating"] != "") { echo " - " . $profile["Custom10Rating"]; } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["0.5"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=0.5&p=1">0.5 <?php if ($profile["Custom05Rating"] != "") { echo " - " . $profile["Custom05Rating"]; } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["0.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=0.0&p=1">0.0 <?php if ($profile["Custom00Rating"] != "") { echo " - " . $profile["Custom00Rating"]; } ?></a></div>
			</div>
			<div style="margin-bottom:1.5em;">
				Rating Distribution<br>
			</div>
			<?php
				if ($loggedIn && $profileId != $userId){
                    $widthPercentage = abs(($correlation / 2) * 100);
                    $leftMargin = 0;

                    if($correlation < 0)
                        $leftMargin = 50 - $widthPercentage;
                    if($correlation > 0)
                        $leftMargin = 50;
			?>
				<div class="profileRankingDistribution" style="margin-bottom:0.5em;height:1.5em;">
					<div class="profileRankingDistributionBar" style="width: <?php echo $widthPercentage;?>%;height:1.5em;position:relative;margin-left:<?php echo $leftMargin;?>%;padding:0px;box-sizing: border-box;"></div>
				    <span class="verticalLine"></span>
                </div>
				<div style="margin-bottom:1em;">
                    <div style="margin-bottom:0.5em;"><span class="subText"><?php echo round($correlation, 3); ?></span></div>
					Rating Similarity To You<br>
				</div>
			<?php
				}
			?>
		<?php
			}
		?>
	</div>
	<div class="ratingsCard">
		<?php
			if($isUser){
		?>
		<center><div class="ratingChoices">
			<a id="0.0Rating" href="ratings/?id=<?php echo $profileId; ?>&r=0.0&p=1" class="ratingChoice"><i class="icon-star-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i></a>
			<a id="0.5Rating" href="ratings/?id=<?php echo $profileId; ?>&r=0.5&p=1" class="ratingChoice"><i class="icon-star-half-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i></a>
			<a id="1.0Rating" href="ratings/?id=<?php echo $profileId; ?>&r=1.0&p=1" class="ratingChoice"><i class="icon-star"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i></a>
			<a id="1.5Rating" href="ratings/?id=<?php echo $profileId; ?>&r=1.5&p=1" class="ratingChoice"><i class="icon-star"></i><i class="icon-star-half-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i></a>
			<a id="2.0Rating" href="ratings/?id=<?php echo $profileId; ?>&r=2.0&p=1" class="ratingChoice"><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i></a>
			<a id="2.5Rating" href="ratings/?id=<?php echo $profileId; ?>&r=2.5&p=1" class="ratingChoice"><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star-half-empty"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i></a>
			<a id="3.0Rating" href="ratings/?id=<?php echo $profileId; ?>&r=3.0&p=1" class="ratingChoice"><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star-empty"></i><i class="icon-star-empty"></i></a>
			<a id="3.5Rating" href="ratings/?id=<?php echo $profileId; ?>&r=3.5&p=1" class="ratingChoice"><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star-half-empty"></i><i class="icon-star-empty"></i></a>
			<a id="4.0Rating" href="ratings/?id=<?php echo $profileId; ?>&r=4.0&p=1" class="ratingChoice"><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star-empty"></i></a>
			<a id="4.5Rating" href="ratings/?id=<?php echo $profileId; ?>&r=4.5&p=1" class="ratingChoice"><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star-half-empty"></i></a>
			<a id="5.0Rating" href="ratings/?id=<?php echo $profileId; ?>&r=5.0&p=1" class="ratingChoice"><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i></a>
		</div></center>
		<div id="ratingDisplay">
			<center>Latest 50 Ratings</center>
			<?php
				include 'rating.php';
			?>
		</div>
		<?php
			} else {
		?>
			This person is not an OMDB user :(
		<?php
			}
		?>
	</div>
</div>

<hr style="margin-bottom:2rem;">
<div style="text-align:center;" >
	<?php
		$result = $conn->query("SELECT DISTINCT `SetID`, Artist, Title, DateRanked FROM `beatmaps` WHERE `CreatorID`='{$profileId}' AND `Mode`='0' ORDER BY `DateRanked` DESC;");
		while($row = $result->fetch_assoc()){		
	?>
		<a href="/mapset/<?php echo $row['SetID']; ?>"  target='_blank' rel='noopener noreferrer'>
			<div class="beatmapCard" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://assets.ppy.sh/beatmaps/<?php echo $row['SetID']; ?>/covers/cover.jpg');">
				<?php echo "{$row['Artist']} - {$row['Title']}"; ?>
			</div>
		</a>
	<?php
		}
	?>
</div>

<?php
    require '../footer.php';
?>