@extends('layouts.master')

@section('title', 'Maps')

@section('content')

  <h1>Map List - {{ $month }} / {{ $year }}</h1>
  <div style="text-align:center;">
    <div class="pagination">
      <x-paginator :page='$page' :num-pages='$num_pages' />
    </div>
  </div>

  @foreach ($beatmapsets as $beatmapset)
    <div class="flex-container ratingContainer mapList" <?php if ($loop->odd) {
        echo "style='background-color:#203838;'";
    } ?>>
      <div class="flex-child" style="flex: 0 0 8%;">
        <a href="/mapset/{{ $beatmapset->id }}">
          <img src="https://b.ppy.sh/thumb/{{ $beatmapset->id }}l.jpg"
            class="diffThumb" style="height:82px;width:82px;"
            onerror="this.onerror=null; this.src='/images/chart-INF.png';"></a>
      </div>
      <div class="flex-child" style="flex: 0 0 50%;min-width: 0;">
        <a href="/mapset/{{ $beatmapset->id }}">
          {{ $beatmapset->artist }} - {{ $beatmapset->title }}
          by
          <a href='/profile/{{ $beatmapset->creator_id }}'>
            {{ $beatmapset->creator_user->username }}
          </a>
          <a href="osu://s/{{ $beatmapset->id }}"><i
              class="icon-download-alt">&ZeroWidthSpace;</i></a>
      </div>
      <div class="flex-child" style="flex: 0 0 3%;min-width: 0;">
        <x-timestamp :time="$beatmapset->date_ranked" />
      </div>
      <div class="flex-child" style="flex: 0 0 32%;text-align:right;min-width:0;">
        <b>{{ $beatmapset->rating_avg }}
        </b> <span style="font-size:12px;color:grey;">
          / 5.00
          from
          {{ $beatmapset->rating_count }}
          votes</span><br>
      </div>
    </div>
  @endforeach

  <div style="text-align:center;">
    <div class="pagination">
      <x-paginator :page='$page' :num-pages='$num_pages' />
    </div>
  </div>

@endsection
