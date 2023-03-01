@extends('layouts.master')

@section('title', 'Home')

@section('content')

welcome to OMDB - a place to rate maps! discover new maps, check out people's ratings, AND STUFF. <br>
<span style="color:grey;">
	{{ $counts->user_count }} users,
	{{ $counts->rating_count }} ratings,
	{{ $counts->comment_count }} comments
</span><hr>

<p style="width:66%;">This website is still in development pretty much. Some things might be weird. Mobile will definitely work pretty bad rn so I recommend using ur computor for this.</p>

<div class="flex-container">
	<div class="flex-child" style="width:40%;height:32em;overflow-y:scroll;position:relative;">
		@foreach ($recent_ratings as $rating)
			<div class="flex-container ratingContainer" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;' altcolour"; } ?>>
			  <div class="flex-child">
				<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
			  </div>
			  <div class="flex-child" style="height:24px;width:24px;">
				<a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
			  </div>
			  <div class="flex-child" style="flex:0 0 50%;">
				<?php
				  echo renderRating($conn, $row) . " on " . "<a href='/mapset/" . $row["SetID"] . "'>" . mb_strimwidth(htmlspecialchars($row["DifficultyName"]), 0, 35, "...") . "</a>";
				?>
			  </div>
			  <div class="flex-child" style="width:100%;text-align:right;min-width:0%;">
				<?php echo GetHumanTime($row["date"]); ?>
			  </div>
			</div>
		@endforeach
	</div>
	<div class="flex-child" style="width:60%;height:32em;overflow-y:scroll;">
		@foreach ($recent_comments as $comment)
			<div class="flex-container ratingContainer" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
			  <div class="flex-child">
				<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
			  </div>
			  <div class="flex-child" style="height:24px;width:24px;">
				<a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
			  </div>
			  <div class="flex-child" style="flex:0 0 60%;text-overflow:elipsis;min-width:0%;">
                    <a style="color:white;" href="/mapset/<?php echo $row["SetID"]; ?>"><?php
				  echo mb_strimwidth(htmlspecialchars($row["Comment"]), 0, 55, "...");
				?></a>
			  </div>
			  <div class="flex-child" style="width:100%;text-align:right;min-width:0%;">
				<?php echo GetHumanTime($row["date"]); ?>
			  </div>
			</div>
		@endforeach
	</div>
</div>

<br>
Latest mapsets:<br>
<div class="flex-container" style="width:100%;background-color:DarkSlateGrey;justify-content: space-around;padding:0px;">
	<br>
	@foreach ($latest_mapsets as $mapset)
		<div class="flex-child" style="text-align:center;width:11%;padding:0.5em;display: inline-block;margin-left:auto;margin-right:auto;">
			<a href="/mapset/{{ $mapset->id }}">
				<img src="https://b.ppy.sh/thumb/{{ $mapset->id }}l.jpg" class="diffThumb" style="aspect-ratio: 1 / 1;width:90%;height:auto;" onerror="this.onerror=null; this.src='/charts/INF.png';">
			</a>
			<br />
			<span class="subtext">
				<a href="/mapset/{{ $mapset->id }}">
					{{ $mapset->artist }} - {{ $mapset->title }}
				</a>
				<br />
				by
				<a href="/profile/{{ $mapset->creator_id }}">
					{{ $mapset->creator_id }}
				</a> <br>
				{{ $mapset->date_ranked->diffForHumans() }}
			</span>
		</div>
	@endforeach
</div>
<br>

<? /*
Most rated beatmaps in the last 7 days:<br>
<div style="width:100%;height:40em;">
    <?php
    $counter = 0;

    $stmt = $conn->prepare("SELECT b.BeatmapID, b.SetID, b.Title, b.Artist, b.DifficultyName, num_ratings
                                  FROM beatmaps b
                                  INNER JOIN (
                                        SELECT BeatmapID, COUNT(*) as num_ratings
                                        FROM ratings
                                        WHERE date >= now() - interval 1 week
                                        GROUP BY BeatmapID
                                  ) r ON b.BeatmapID = r.BeatmapID
                                  INNER JOIN (
                                        SELECT SetID, MAX(num_ratings) as max_ratings
                                        FROM (
                                            SELECT b.SetID, b.BeatmapID, COUNT(*) as num_ratings
                                            FROM beatmaps b
                                            INNER JOIN ratings r ON b.BeatmapID = r.BeatmapID
                                            WHERE r.date >= now() - interval 1 week
                                            GROUP BY b.SetID, b.BeatmapID
                                        ) t
                                        GROUP BY SetID
                                  ) m ON b.SetID = m.SetID AND r.num_ratings = m.max_ratings
                                  ORDER BY num_ratings DESC, b.BeatmapID DESC
                                  LIMIT 10;
                                  ");
    $stmt->execute();

    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()) {
        $counter += 1;
        ?>
        <div class="flex-container ratingContainer" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;' altcolour"; } ?>>
            <div class="flex-child" style="min-width:2em;">
                #<?php echo $counter; ?>
            </div>
            <div class="flex-child">
                <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
            </div>
            <div class="flex-child" style="flex:0 0 80%;">
                <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo "{$row["Artist"]} - {$row["Title"]} [{$row["DifficultyName"]}]";?></a>
            </div>
            <div class="flex-child" style="width:100%;text-align:right;min-width:0%;">
                <?php echo $row["num_ratings"];?> ratings
            </div>
        </div>
        <?php
    }

    $stmt->close();
?> */ ?>
</div>

@stop
