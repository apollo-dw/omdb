@extends('layouts.master')

@section('title', 'Home')

@section('content')

  welcome to OMDB - a place to rate maps! discover new maps, check out people's
  ratings, AND STUFF. <br>
  <span style="color:grey;">
    {{ $counts->user_count }} users,
    {{ $counts->rating_count }} ratings,
    {{ $counts->comment_count }} comments
  </span>
  <hr>

  <p style="width:66%;">This website is still in development pretty much. Some
    things might be weird. Mobile will definitely work pretty bad rn so I
    recommend using ur computor for this.</p>

  <div class="flex-container">
    <div class="flex-child"
      style="width:40%;height:32em;overflow-y:scroll;position:relative;">
      @foreach ($recent_ratings as $rating)
        <div class="flex-container ratingContainer" <?php if ($loop->odd) {
            echo "style='background-color:#203838;' altcolour";
        } ?>>
          <div class="flex-child">
            <a href="/mapset/{{ $rating->beatmapset_id }}">
              <img src="https://b.ppy.sh/thumb/{{ $rating->beatmapset_id }}l.jpg"
                class="diffThumb"
                onerror="this.onerror=null; this.src='/charts/INF.png';">
            </a>
          </div>

          <div class="flex-child" style="height:24px;width:24px;">
            <a href="/profile/{{ $rating->user_id }}"><img
                src="https://s.ppy.sh/a/{{ $rating->user_id }}"
                style="height:24px;width:24px;"
                title="{{ $rating->osu_user->username }}" /></a>
          </div>

          <div class="flex-child" style="flex:0 0 50%;">
            <x-ratings.display :rating="$rating" />
            on
            <a
              href='/mapset/{{ $rating->beatmapset_id }}'>{{ $rating->beatmap->difficulty_name }}</a>
            {{--
// echo renderRating($conn, $row) . " on " . "<a href='/mapset/" . $row["SetID"] . "'>" . mb_strimwidth(htmlspecialchars($row["DifficultyName"]), 0, 35, "...") . "</a>";
--}}
          </div>
          <div class="flex-child"
            style="width:100%;text-align:right;min-width:0%;">
            <x-timestamp :time="$rating->updated_at" />
          </div>
        </div>
      @endforeach
    </div>

    <div class="flex-child" style="width:60%;height:32em;overflow-y:scroll;">
      @foreach ($recent_comments as $comment)
        <div class="flex-container ratingContainer" <?php if ($loop->odd) {
            echo "style='background-color:#203838;'";
        } ?>>
          <div class="flex-child">
            <a href="/mapset/{{ $comment->beatmapset_id }}">
              <img
                src="https://b.ppy.sh/thumb/{{ $comment->beatmapset_id }}l.jpg"
                class="diffThumb"
                onerror="this.onerror=null; this.src='/charts/INF.png';">
            </a>
          </div>

          <div class="flex-child" style="height:24px;width:24px;">
            <a href="/profile/{{ $comment->user_id }}"><img
                src="https://s.ppy.sh/a/{{ $comment->user_id }}"
                style="height:24px;width:24px;"
                title="{{ $comment->osu_user->username }}" /></a>
          </div>

          <div class="flex-child"
            style="flex:0 0 60%;text-overflow:elipsis;min-width:0%;">
            <a style="color:white;" href="/mapset/{{ $comment->beatmapset_id }}">
              {{ $comment->comment }}
            </a>
          </div>

          <div class="flex-child"
            style="width:100%;text-align:right;min-width:0%;">
            <x-timestamp :time="$comment->created_at" />
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <br>
  Latest mapsets:<br>
  <div class="flex-container"
    style="width:100%;background-color:DarkSlateGrey;justify-content: space-around;padding:0px;">
    <br>
    @foreach ($latest_mapsets as $mapset)
      <div class="flex-child"
        style="text-align:center;width:11%;padding:0.5em;display: inline-block;margin-left:auto;margin-right:auto;">
        <a href="/mapset/{{ $mapset->id }}">
          <img src="https://b.ppy.sh/thumb/{{ $mapset->id }}l.jpg"
            class="diffThumb" style="aspect-ratio: 1 / 1;width:90%;height:auto;"
            onerror="this.onerror=null; this.src='/charts/INF.png';">
        </a>
        <br />
        <span class="subtext">
          <a href="/mapset/{{ $mapset->id }}">
            {{ $mapset->artist }} - {{ $mapset->title }}
          </a>
          <br />
          by
          <a href="/profile/{{ $mapset->creator_id }}">
            {{ $mapset->creator_user->username }}
          </a> <br>

          <span
            style="text-decoration-line: underline; text-decoration-style: dotted;"
            title="{{ $mapset->date_ranked }}">
            {{ $mapset->date_ranked->diffForHumans() }}
          </span>
        </span>
      </div>
    @endforeach
  </div>
  <br>

  Most rated beatmaps in the last 7 days:<br>
  <div style="width:100%;height:40em;">
    @foreach ($last_7_days_ratings as $row)
      <div class="flex-container ratingContainer" <?php if ($loop->odd) {
          echo "style='background-color:#203838;' altcolour";
      } ?>>
        <div class="flex-child" style="min-width:2em;">
          #{{ $loop->index + 1 }}
        </div>
        <div class="flex-child">
          <a href="/mapset/{{ $row->beatmapset_id }}"><img
              src="https://b.ppy.sh/thumb/{{ $row->beatmapset_id }}l.jpg"
              class="diffThumb"/
              onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
        </div>
        <div class="flex-child" style="flex:0 0 80%;">
          <a href="/mapset/{{ $row->beatmapset_id }}">{{ $row->artist }} -
            {{ $row->title }} [{{ $row->difficulty_name }}]
          </a>
        </div>
        <div class="flex-child" style="width:100%;text-align:right;min-width:0%;">
          {{ $row->num_ratings }} ratings
        </div>
      </div>
    @endforeach
  </div>

@endsection
