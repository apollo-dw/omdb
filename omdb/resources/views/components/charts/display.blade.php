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
  @foreach ($beatmaps as $beatmap)
    <div class="flex-container diffContainer" <?php if ($loop->odd) {
        echo "style='background-color:#203838;'";
    } ?>>
      <div class="diffBox"
        style="text-align:center;padding-left:1.5em;flex: 0 0 6%;">
        <b>#{{ $start_at + $loop->index + 1 }}</b>
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
          <a href="https://osu.ppy.sh/b/{{ $beatmap->id }}" target="_blank"
            rel="noopener noreferrer"><i class="icon-external-link"
              style="font-size:10px;"></i></a><br></a>
        <a
          href="/mapset/{{ $beatmap->beatmapset_id }}"><b>{{ $beatmap->difficulty_name }}</b></a>
        <span class="subText">
          {{ number_format($beatmap->star_rating, 2) }}*</span><br>
        {{ \Carbon\Carbon::parse($beatmap->date_ranked)->diffForHumans() }}<br>
        <a href="/profile/{{ $beatmap->creator_id }}">
          {{ $beatmap->username }}</a>
        <a href="https://osu.ppy.sh/u/{{ $beatmap->creator_id }}"
          target="_blank" rel="noopener noreferrer"><i
            class="icon-external-link" style="font-size:10px;"></i></a><br>
      </div>
      <div class="diffBox">
        <b>{{ number_format($beatmap->cached_weighted_avg, 2) }}</b>
        <span class="subText">/ 5.00 from <span
            style="color:white">{{ $beatmap->cached_rating_count }}</span>
          votes</span><br>
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
