@extends('layouts.master')

@section('title', 'Comments')

@section('content')

  <center>
    <h1>
      <a href="/profile/{{ $omdb_user->user_id }}">
        {{ $omdb_user->osu_user->username }}</a>'s
      {{ number_format($score, 1) }} ratings
    </h1>
  </center>

  <hr>

  <div style="text-align:center;">
    <x-paginator :page="$page" :num-pages="$num_pages" />
  </div>

  <div class="flex-container">
    <div class="flex-child" style="width:100%;">
      @foreach ($ratings as $rating)
        <div class="flex-container ratingContainer" <?php if ($loop->index) {
            echo "style='background-color:#203838;' altcolour";
        } ?>>
          <div class="flex-child">
            <a href="/mapset/{{ $rating->beatmapset_id }}"><img
                src="https://b.ppy.sh/thumb/{{ $rating->beatmapset_id }}l.jpg"
                class="diffThumb"/
                onerror="this.onerror=null; this.src='../../charts/INF.png';"></a>
          </div>
          <div class="flex-child" style="flex:0 0 60%;">
            <x-ratings.display :rating="$rating" />
            on
            <a href="/mapset/{{ $rating->beatmapset_id }}">
              {{ $rating->beatmapset->artist }} -
              {{ $rating->beatmapset->title }}
              [{{ $rating->beatmap->difficulty_name }}]
            </a>
          </div>
          <div class="flex-child" style="width:100%;text-align:right;">
            <x-timestamp :time="$rating->updated_at" />
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <div style="text-align:center;">
    <x-paginator :page="$page" :num-pages="$num_pages" />
  </div>

@endsection
