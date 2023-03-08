@if (count($ratings) == 0)
  <center>No ratings yet!</center>
@else
  @foreach ($ratings as $rating)
    <div class="flex-container ratingContainer" <?php
    if ($loop->odd) {
        echo "style='background-color:#203838;' altcolour";
    }
    ?>>
      @if ($show_map_meta)
        <div class="flex-child">
          <a href="/mapset/{{ $rating->beatmapset_id }}"><img
              src="https://b.ppy.sh/thumb/{{ $rating->beatmapset_id }}l.jpg"
              class="diffThumb"/
              onerror="this.onerror=null;
            this.src='/images/chart-INF.png';"></a>
        </div>
      @endif

      @if ($show_user)
        <div class="flex-child">
          <a href="/profile/{{ $rating->user_id }}">
            <img src="https://s.ppy.sh/a/{{ $rating->user_id }}"
              style="height:24px;width:24px;"
              title="{{ $rating->osu_user->username }}" />
          </a>
        </div>
      @endif

      <div class="flex-child" style="flex-grow: 1;">
        <x-ratings.display :rating="$rating" />
        on
        <a href="/mapset/{{ $rating->beatmapset_id }}">
          @if ($show_map_meta)
            {{ $rating->beatmapset->artist }} - {{ $rating->beatmapset->title }}
            [{{ $rating->beatmap->difficulty_name }}]
          @else
            {{ $rating->beatmap->difficulty_name }}
          @endif
        </a>
      </div>
      <div class="flex-child" style="text-align:right;">
        <x-timestamp :time="$rating->updated_at" />
      </div>
    </div>
  @endforeach

  @if ($paginated)
    <div style="text-align: center;">
      <x-paginator :page="$page" :num-pages="$num_pages" :page-variable="$page_variable" />
    </div>
  @endif
@endif
