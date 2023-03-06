<style>
  .flex-container {
    display: flex;
    width: 100%;
  }

  .diffContainer {
    background-color: DarkSlateGrey;
    align-items: center;
  }

  .diffBox {
    padding: 0.5em;
    flex-grow: 1;
    height: 100%;
  }

  .diffbox a {
    color: white;
  }

  .diffThumb {
    height: 80px;
    width: 80px;
    border: 1px solid #ddd;
    object-fit: cover;
  }
</style>

<div class="flex-item" style="flex: 0 0 80%; padding:0.5em;">
  <?php /*
			$lim = 50;
			$counter = ($page - 1) * $lim;

			$pageString = "LIMIT {$lim}";

			if ($page > 1){
				$lower = ($page - 1) * $lim;
				$pageString = "LIMIT {$lower}, {$lim}";
			}

			$orderString = "ASC";

			if ($order == 2 || $order == 3){
				$orderString = "DESC";
			}

            $columnString = "ChartRank";

            if ($order == 3)
                $columnString = "RatingCount";

            $yearString = "ORDER BY {$columnString}";

            if ($year != -1){
                if ($order != 3)
                    $columnString = "ChartYearRank";
                $yearString = "AND YEAR(b.DateRanked) = '{$year}' ORDER BY {$columnString}";
            }

            $genreString = "";

            if ($genre > 0){
                $genreString = "AND `Genre`='{$genre}'";
            }

			$stmt = $conn->prepare("SELECT b.* FROM beatmaps b WHERE b.Rating IS NOT NULL {$genreString} {$yearString} {$orderString}, BeatmapID {$pageString};");
			$stmt->execute();
			$result = $stmt->get_result();

			while($row = $result->fetch_assoc()) {
				$stmt2 = $conn->prepare("SELECT `Score` FROM `ratings` WHERE `BeatmapID`=? AND `UserID`=?;");
				$stmt2->bind_param('ss', $row['BeatmapID'], $userId);
				$stmt2->execute();
				$userRatingResult = $stmt2->get_result();
				$userRating = $userRatingResult->fetch_row()[0] ?? "";
				$stmt2->close();

				$counter += 1;
                */
  ?>
  @foreach ($beatmaps as $beatmap)
    <div class="flex-container diffContainer" <?php if ($loop->odd) {
        echo "style='background-color:#203838;'";
    } ?>>
      <div class="diffBox"
        style="text-align:center;padding-left:1.5em;flex: 0 0 6%;">
        <b>#{{ $loop->index + 1 }}</b>
      </div>
      <div class="diffBox" style="flex: 0 0 6%;">
        <a href="/mapset/{{ $beatmap->beatmapset_id }}"><img
           src="https://b.ppy.sh/thumb/{{ $beatmap->beatmapset_id }}l.jpg"
            class="diffThumb"
            onerror="this.onerror=null; this.src='INF.png';" /></a>
      </div>
      <div class="diffBox" style="flex: 0 0 42%;">
        <a href="/mapset/{{ $beatmap->beatmapset_id }}">
          {{ $beatmap->artist }} - {{ $beatmap->title }}
          <a href="https://osu.ppy.sh/b/{{ $beatmap->id }}"
            target="_blank" rel="noopener noreferrer"><i
              class="icon-external-link"
              style="font-size:10px;"></i></a><br></a>
        <a href="/mapset/{{ $beatmap->beatmapset_id }}"><b>{{
          $beatmap->difficulty_name }}</b></a>
        <span class="subText">
          {{ number_format($beatmap->star_rating, 2) }}*</span><br>
        {{ \Carbon\Carbon::parse($beatmap->date_ranked)->diffForHumans() }}<br>
        <a href="/profile/{{ $beatmap->creator_id }}">
          {{ $beatmap->username }}</a>
        <a
          href="https://osu.ppy.sh/u/{{ $beatmap->creator_id }}" target="_blank"
          rel="noopener noreferrer"><i class="icon-external-link"
            style="font-size:10px;"></i></a><br>
      </div>
      <div class="diffBox">
        <b>{{ number_format($beatmap->cached_weighted_avg, 2) }}</b>
        <span class="subText">/ 5.00 from <span
              style="color:white">{{ $beatmap->cached_rating_count }}</span> votes</span><br>
      </div>
      <div class="diffBox">
        <b style="font-weight:900;">
          {{-- TODO Your rating
          <?php echo $userRating; ?> --}}
        </b>
      </div>
    </div>
  @endforeach
</div>
