@extends('layouts.master')

@section('title', e($osu_user->username))

@section('content')

  <div class="profileContainer">
    <div class="profileCard">
      <div class="profileTitle">
        <a href="https://osu.ppy.sh/u/{{ $osu_user->user_id }}" target="_blank"
          rel="noopener noreferrer">
          {{ $osu_user->username }}
        </a>

        <a href="https://osu.ppy.sh/u/{{ $osu_user->user_id }}" target="_blank"
          rel="noopener noreferrer">
          <i class="icon-external-link" style="font-size:10px;"></i>
        </a>
      </div>

      <div class="profileImage">
        <img src="https://s.ppy.sh/a/{{ $osu_user->user_id }}"
          style="width:146px;height:146px;" />
      </div>

      <div class="profileStats">
        @if ($osu_user->omdb_user)
          <b>Ratings:</b> {{ $total_ratings }}<br />

          <a href="/profile/{{ $osu_user->user_id }}/comments">
            <b>Comments:</b> {{ $comment_count }}
          </a><br>

          <b>Ranked Mapsets:</b> {{ count($ranked_beatmaps) }}<br />
        @endif
      </div>

      @if ($osu_user->omdb_user && $rating_counts)
        <div class="profileRankingDistribution" style="margin-bottom:0.5em;">
          @for ($r = 0; $r <= 10; $r++)
            @php
              $rs = number_format((10 - $r) / 2, 1);
              $rs1 = strval($r);
              $l = $rating_counts[$rs] ?? 0;
              $names = json_decode($osu_user->omdb_user->custom_ratings, true) ?? [];
            @endphp

            <div class="profileRankingDistributionBar"
              style="width: {{ ($l / $max_rating) * 90 }}%;">
              <a
                href="/profile/{{ $osu_user->user_id }}/ratings/?score={{ $rs }}">
                {{ $rs }}
                @if (!empty($names[$rs]))
                  -
                  {{ $names[$rs] ?? '' }}
                @endif
              </a>
            </div>
          @endfor
        </div>
        <div style="margin-bottom:1.5em;">
          Rating Distribution<br>
        </div>
      @endif

      @if ($is_you)

        @if (Auth::check() && !$is_you)
          @php
            $widthPercentage = abs(($correlation / 2) * 100);
            $leftMargin = 0;
            
            if ($correlation < 0) {
                $leftMargin = 50 - $widthPercentage;
            }
            if ($correlation > 0) {
                $leftMargin = 50;
            }
          @endphp

          <div class="profileRankingDistribution"
            style="margin-bottom:0.5em;height:1.5em;">
            <div class="profileRankingDistributionBar"
              style="width: <?php echo $widthPercentage; ?>%;height:1.5em;position:relative;margin-left:<?php echo $leftMargin; ?>%;padding:0px;box-sizing: border-box;">
            </div>
            <span class="verticalLine"></span>
          </div>

          <div style="margin-bottom:1em;">
            <div style="margin-bottom:0.5em;"><span
                class="subText"><?php echo round($correlation, 3); ?></span></div>
            Rating Similarity To You<br>
          </div>
        @endif
      @endif
    </div>

    <div class="ratingsCard">
      <center>
        <div class="ratingChoices">
          @for ($r = 0.0; $r <= 5.0; $r += 0.5)
            @php
              $rs = number_format($r, 1);
            @endphp

            <a id="{{ $rs }}Rating"
              href="/profile/{{ $osu_user->user_id }}/ratings?score={{ $rs }}"
              class="ratingChoice">
              @for ($j = 0; $j < 5; $j++)
                @if ($r <= $j)
                  <i class="icon-star-empty"></i>
                @elseif ($r < $j + 1)
                  <i class="icon-star-half-empty"></i>
                @else
                  <i class="icon-star"></i>
                @endif
              @endfor
            </a>
          @endfor
        </div>
      </center>

      @if ($osu_user->omdb_user)
        <div id="ratingDisplay">
          <center>Latest 50 Ratings</center>
          <x-ratings.latest :user-id="$osu_user->user_id" :show-user="false" />
        </div>
      @else
        This person is not an OMDB user :(
      @endif
    </div>
  </div>

  <hr style="margin-bottom:2rem;">

  <div style="text-align:center;">
    @foreach ($ranked_beatmaps as $beatmapset)
      <a href="/mapset/{{ $beatmapset->id }}" target='_blank'
        rel='noopener noreferrer'>
        <div class="beatmapCard"
          style="background-image:
              linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
              url('https://assets.ppy.sh/beatmaps/{{ $beatmapset->id }}/covers/cover.jpg');">
          {{ $beatmapset->artist }} - {{ $beatmapset->title }}
        </div>
      </a>
    @endforeach
  </div>

@endsection
