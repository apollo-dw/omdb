@extends('layouts.master')

@section('title', 'Home')

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
        <?php
        /*
                                                                                                            <b>Ratings:</b> <?php echo $conn->query("SELECT Count(*) FROM `ratings` WHERE `UserID`='{$profileId}';")->fetch_row()[0]; ?>
        ?>
        ?>
        ?>
        ?>
        ?>
        ?>
        ?>
        ?>
        ?>
        ?>
        ?>
        ?><br>
        <a href="comments/?id=<?php echo $profileId; ?>"><b>Comments:</b>
          <?php echo $conn->query("SELECT Count(*) FROM `comments` WHERE `UserID`='{$profileId}';")->fetch_row()[0]; ?></a><br>
        <b>Ranked Mapsets:</b> <?php echo $conn->query("SELECT Count(DISTINCT SetID) FROM `beatmaps` WHERE `SetCreatorID`='{$profileId}';")->fetch_row()[0]; ?><br>
        */
        ?>
      </div>

      @if ($is_you)
        <div class="profileRankingDistribution" style="margin-bottom:0.5em;">
          @if ($rating_counts)
            @for ($r = 0; $r <= 10; $r++)
              @php
                $rs = number_format((10 - $r) / 2, 1);
              @endphp

              <div class="profileRankingDistributionBar"
                style="width:
							<?php echo ($rating_counts[$rs] / $max_rating) * 90; ?>%;">
                <a href="ratings/?id={{ $osu_user->user_id }}&r=5.0&p=1">
                  {{ $rs }}
                  <?php
                  /* if ($profile["Custom50Rating"] != "") {
                                echo " - " .
                            htmlspecialchars($profile["Custom50Rating"]); } */
                  ?>
                </a>
              </div>
            @endfor
          @endif
        </div>
        <div style="margin-bottom:1.5em;">
          Rating Distribution<br>
        </div>

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
      @if ($is_you)
        <center>
          <div class="ratingChoices">
            @for ($r = 0.0; $r <= 5.0; $r += 0.5)
              @php
                $rs = number_format($r, 1);
              @endphp

              <a id="{{ $rs }}Rating"
                href="ratings/?id={{ $osu_user->user_id }}&r={{ $rs }}&p=1"
                class="ratingChoice">
                @for ($j = 0; $j < 5; $j++)
                  @if ($r < $j)
                    <i class="icon-star-empty"></i>
                  @elseif ($r > $j && $r < $j + 1)
                    <i class="icon-star-half-empty"></i>
                  @else
                    <i class="icon-star"></i>
                  @endif
                @endfor
              </a>
            @endfor
          </div>
        </center>

        <div id="ratingDisplay">
          <center>Latest 50 Ratings</center>
          <x-ratings.latest :user-id="$osu_user->user_id" />
        </div>
      @else
        This person is not an OMDB user :(
      @endif
    </div>
  </div>

  <?php
  /*
                        <hr style="margin-bottom:2rem;">
                        <div style="text-align:center;" >
                            <?php
                              $result = $conn->query("SELECT DISTINCT `SetID`, Artist, Title, DateRanked FROM `beatmaps` WHERE `SetCreatorID`='{$profileId}' AND `Mode`='0' ORDER BY `DateRanked` DESC;");
                              while($row = $result->fetch_assoc()){
                            ?>
  ?>
  ?>
  ?>
  ?>
  ?>
  ?>
  ?>
  ?>
  ?>
  ?>
  ?>
  ?>
  <a href="/mapset/<?php echo $row['SetID']; ?>" target='_blank'
    rel='noopener noreferrer'>
    <div class="beatmapCard"
      style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://assets.ppy.sh/beatmaps/<?php echo $row['SetID']; ?>/covers/cover.jpg');">
      <?php echo "{$row['Artist']} - {$row['Title']}"; ?>
    </div>
  </a>
  <?php
        }
    ?>
  </div>

  */
  ?>

@endsection
