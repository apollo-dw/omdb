@extends('layouts.master')

@section('title', 'Home')

@section('content')

  <center>
    <h1>
      <a target="_blank" rel="noopener noreferrer"
        href="https://osu.ppy.sh/s/{{ $mapset->id }}">
        {{ $mapset->artist }} - {{ $mapset->title }}
      </a>
      by
      <a href='/profile/{{ $mapset->creator_id }}'>
        {{ $mapset->creator_user->username }}
      </a>
    </h1>
  </center>

  <div class="flex-container" style="justify-content: center;">
    <div class="flex-child">
      <img
        src="https://assets.ppy.sh/beatmaps/{{ $mapset->id }}/covers/cover.jpg"
        style="height:6rem;width:21.6rem;border-radius:16px;"
        onerror="this.onerror=null; this.src='INF.png';" />
    </div>

    <div class="flex-child">
      <?php if (true) {
          // $isLoved)
          echo 'Submitted: ';
      } else {
          echo 'Ranked: ';
      }
      // echo date("M jS, Y", strtotime($sampleRow['DateRanked']));
      ?>
      <br>

      Average Rating:
      <b>{{ $average_rating }}</b>
      <span style="font-size:12px;color:grey;">/ 5.00 from <?php
      /* echo $numberOfSetRatings; */
      ?>
        votes</span><br>
      <?php if (true) {
          // $isLoved)
          echo 'Loved Mapset';
      } ?>
    </div>
  </div>

  <br>
  <hr style="margin-bottom:1em;">

  <?php
  /*
    $counter = 0;
    while($row = $result->fetch_assoc()) {
        $ratedQueryResult = $conn->query("SELECT * FROM `ratings` WHERE `BeatmapID`='{$row["BeatmapID"]}' AND `UserID`='{$userId}';");
        $userHasRatedThis = $ratedQueryResult->num_rows == 1 ? true : false;
        $userMapRating = $ratedQueryResult->fetch_row()[3] ?? -1;
        $counter += 1;

        $ratingCounts = array();

        $ratingQuery = "SELECT `Score`, COUNT(*) as count FROM `ratings` WHERE `BeatmapID`='{$row["BeatmapID"]}' GROUP BY `Score`";
        $ratingResult = $conn->query($ratingQuery);

        $blackListed = $row["Blacklisted"] == 1;

        $hasRatings = true;
        if ($ratingResult->num_rows == 0 || $row["ChartYearRank"] == null){
            $hasRatings = false;
        }

        // Why do I need to do this here and not on the profile rating distribution chart. I don't get it
        $ratingCounts['0.0'] = 0;
        $ratingCounts['0.5'] = 0;
        $ratingCounts['1.0'] = 0;
        $ratingCounts['1.5'] = 0;
        $ratingCounts['2.0'] = 0;
        $ratingCounts['2.5'] = 0;
        $ratingCounts['3.0'] = 0;
        $ratingCounts['3.5'] = 0;
        $ratingCounts['4.0'] = 0;
        $ratingCounts['4.5'] = 0;
        $ratingCounts['5.0'] = 0;

        while ($ratingRow = $ratingResult->fetch_assoc()) {
            $ratingCounts[$ratingRow['Score']] = $ratingRow['count'];
        }

        $maxRating = max($ratingCounts);
        */
  ?>

  @foreach ($beatmaps as $beatmap)
    <div class="flex-container diffContainer <?php if (true || $blackListed) {
        echo 'faded';
    } ?>">
      <div class="flex-child diffBox" style="text-align:center;width:60%;">
        <a href="https://osu.ppy.sh/b/{{ $beatmap->id }}" target="_blank"
          rel="noopener noreferrer" <?php
          /* if ($row["ChartRank"] <= 250 && !is_null($row["ChartRank"])){ echo "class='bolded'"; } */
          ?>>
          {{ $beatmap->difficulty_name }}
          <?php
          /* echo mb_strimwidth(htmlspecialchars($row['DifficultyName']), 0, 35, "..."); */
          ?>
        </a>
        <a href="osu://b/{{ $beatmap->id }}"><i
            class="icon-download-alt">&ZeroWidthSpace;</i></a>
        <span class="subText"><?php echo number_format((float) $beatmap->star_rating, 2, '.', ''); ?>*</span>
        <?php
        /* if($row['SetCreatorID'] != $row['CreatorID']) { $mapperName = GetUserNameFromId($row["CreatorID"], $conn); echo "<br><span class='subText'>mapped by <a href='/profile/{$row["CreatorID"]}'> {$mapperName} </a></span>"; } */
        ?>
      </div>

      <?php if (true || !$blackListed) { ?>
      <div class="flex-child diffBox" style="width:20%;text-align:center;">
        @if (count($beatmap->ratings) > 0)
          @php
            $ratingCounts = [];
            
            for ($r = 0.0; $r <= 5.0; $r += 0.5) {
                $rs = number_format($r, 1);
                $ratingCounts[$rs] = 0;
            }
            
            foreach ($beatmap->ratings as $rating) {
                $rs = number_format($rating->score, 1);
                $ratingCounts[$rs] += 1;
            }
            
            $maxRating = max($ratingCounts);
          @endphp

          <div class="mapsetRankingDistribution">
            @for ($r = 0.0; $r <= 5.0; $r += 0.5)
              @php
                $rs = number_format($r, 1);
              @endphp

              <div class="mapsetRankingDistributionBar"
                style="height: {{ ($ratingCounts[$rs] / $maxRating) * 90 }}%;">
              </div>
            @endfor
          </div>
          <span class="subText" style="width:100%;">Rating Distribution</span>
        @endif
      </div>

      <div class="flex-child diffBox" style="text-align:right;width:40%;">
        @if (count($beatmap->ratings) > 0)
          Rating:
          <b><?php
          /* echo number_format($row["WeightedAvg"], 2); */
          ?></b>
          <span class="subText">
            / 5.00 from
            <span style="color:white">{{ count($beatmap->ratings) }}</span>
            votes
          </span>
          <br>

          <?php
          /*
                                    Ranking: <b>#<?php echo $row["ChartYearRank"]; ?>
          ?>
          ?></b> for <a
            href="/charts/?y=<?php echo $year; ?>"><?php echo $year; ?></a>,
          <b>#<?php echo $row['ChartRank']; ?></b> <a href="/charts/">overall</a>
          */
          ?>
        @endif
      </div>
      <div class="flex-child diffBox" style="padding:auto;width:30%;">
        @if (Auth::check())
          @php
            // TODO: Finish
            $userHasRatedThis = false;
            $userMapRating = 0;
          @endphp

          <span class="identifier" style="display: inline-block;">
            <ol class="star-rating-list <?php if (!$userHasRatedThis) {
                echo 'unrated';
            } ?>"
              beatmapid="{{ $beatmap->id }}" rating="<?php echo $userMapRating; ?>">
              <!-- The holy grail of PHP code. If I want to make this public on github i NEED to rewrite this-->
              <i class="icon-remove" style="opacity:0;"></i>
              <li class="star icon-star<?php if ($userMapRating == 0 || !$userHasRatedThis) {
                  echo '-empty';
              } elseif ($userMapRating == 0.5) {
                  echo '-half-empty';
              } ?>" value="1" />
              <li class="star icon-star<?php if ($userMapRating <= 1) {
                  echo '-empty';
              } elseif ($userMapRating == 1.5) {
                  echo '-half-empty';
              } ?>" value="2" />
              <li class="star icon-star<?php if ($userMapRating <= 2) {
                  echo '-empty';
              } elseif ($userMapRating == 2.5) {
                  echo '-half-empty';
              } ?>" value="3" />
              <li class="star icon-star<?php if ($userMapRating <= 3) {
                  echo '-empty';
              } elseif ($userMapRating == 3.5) {
                  echo '-half-empty';
              } ?>" value="4" />
              <li class="star icon-star<?php if ($userMapRating <= 4) {
                  echo '-empty';
              } elseif ($userMapRating == 4.5) {
                  echo '-half-empty';
              } ?>" value="5" />
            </ol>
          </span>

          <span class="starRemoveButton <?php if (!$userHasRatedThis) {
              echo 'disabled';
          } ?>"
            beatmapid="{{ $beatmap->id }}">
            <i class="icon-remove"></i>
          </span>

          <span style="display: inline-block; padding-left:0.25em;"
            class="star-value <?php if (!$userHasRatedThis) {
                echo 'unrated';
            } ?>"><?php if ($userHasRatedThis) {
                echo $userMapRating;
            } else {
                echo '&ZeroWidthSpace;';
            } ?></span>
        @else
          Log in to rate maps!
        @endif
      </div>
      <?php } else { ?>
      <div class="flex-child diffBox" style="padding:auto;width:91%;">
        <b>This difficulty has been blacklisted from OMDB.</b><br>
        Reason: <?php
        /* echo $row["BlacklistReason"]; */
        ?>
      </div>
      <?php } ?>
    </div>
  @endforeach

  <hr style="margin-bottom:1em;margin-top:1em;">

  <div class="flex-container">
    <div class="flex-child" style="width:40%;">
      Latest Ratings<br><br>
      <div id="setRatingsDisplay">
        <x-ratings.latest :beatmapset-id="$mapset->id" />
      </div>
    </div>

    <div class="flex-child" style="width:60%;">
      Comments
      <br><br>
      <div class="flex-container commentContainer" style="width:100%;">
        {{-- Comment submission form. --}}
        @if (Auth::check())
          <div class="flex-child commentComposer">
            <form>
              <textarea id="commentForm" name="commentForm"
                placeholder="Write your comment here!" value="" autocomplete='off'></textarea>

              <input type='button' name="commentSubmit" id="commentSubmit"
                value="Post" onclick="submitComment()" />
            </form>
          </div>
        @endif

        @foreach ($comments as $comment)
          <div class="flex-container flex-child commentHeader">
            <div class="flex-child" style="height:24px;width:24px;">
              <a href="/profile/{{ $comment->user_id }}">
                <img src="https://s.ppy.sh/a/{{ $comment->user_id }}"
                  style="height:24px;width:24px;"
                  title="{{ $comment->osu_user->username }}" />
              </a>
            </div>
            <div class="flex-child">
              <a href="/profile/{{ $comment->user_id }}">
                {{ $comment->osu_user->username }}
              </a>
            </div>
            <div class="flex-child" style="margin-left:auto;">
              @php
                $auth_user = Auth::user();
              @endphp

              @if ($comment->user_id == $auth_user->user_id)
                <i class="icon-remove removeComment" style="color:#f94141;"
                  value="{{ $comment->id }}"></i>
                {{ $comment->created_at->diffForHumans() }}
              @endif
            </div>
          </div>

          {{-- Comment text itself --}}
          <div class="flex-child comment" style="min-width:0;overflow: hidden;">
            <p>
              <!-- TODO: ParseOsuLinks -->
              {{ nl2br($comment->comment) }}
            </p>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <script>
    function submitComment() {
      // console.log("yeah");
      var comment = encodeURIComponent($('#commentForm').val());
      // console.log(comment);

      if (!(comment.length > 3 && comment.length < 8000)) {
        return;
      }

      $('#commentSubmit').prop('disabled', true);

      let payload = {
        comment
      };
      fetch("/mapset/{{ $mapset->id }}/comment", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": "{{ csrf_token() }}",
        },
        body: JSON.stringify(payload),
      }).then(result => {
        if (result.status == 200) {
          location.reload();
        }
      });
    }

    function submitRating(beatmap_id, rating) {
      if (!(rating >= 0 && rating <= 5)) {
        return;
      }

      let payload = {
        beatmap_id,
        rating
      };
      fetch("/mapset/{{ $mapset->id }}/rating", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": "{{ csrf_token() }}",
        },
        body: JSON.stringify(payload),
      }).then(result => {
        if (result.status == 200) {
          location.reload();
        }
      });
    }

    $('#commentForm').keydown(function(event) {
      if ((event.keyCode == 10 || event.keyCode == 13) && event.ctrlKey)
        submitComment();
    });

    $(".removeComment").click(function(event) {
      var $this = $(this);

      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          console.log(this.responseText);
          location.reload();
        }
      };

      xhttp.open("POST", "RemoveComment.php", true);
      xhttp.setRequestHeader("Content-type",
        "application/x-www-form-urlencoded");
      xhttp.send("sID={{ $mapset->id }}&cID=" + $this.attr('value'));

    });

    $(".star-rating-list").mousemove(function(event) {
      var $this = $(this);
      var sel = event.target.value;
      var $options = $this.find(".star");
      var rating = 0;

      for (var i = 0; i < 5; i++) {
        if (i < sel) {
          if (event.pageX - event.target.getBoundingClientRect().left <= 6 &&
            sel - 1 == i) {
            $options.eq(i).attr('class', 'star icon-star-half-empty');
            rating += 0.5;
          } else {
            $options.eq(i).attr('class', 'star icon-star');
            rating += 1;
          }
        } else {
          $options.eq(i).attr('class', 'star icon-star-empty');
        }
      }
      $this.parent().parent().find('.star-value').html(rating.toFixed(1));
    });

    $(".star-rating-list").mouseleave(function(event) {
      var $this = $(this);
      var sel = $this.attr("rating");
      var $options = $this.find(".star");

      for (var i = 0; i < 5; i++) {
        if (i < sel) {
          if (sel - 0.5 == i) {
            $options.eq(i).attr('class', 'star icon-star-half-empty');
          } else {
            $options.eq(i).attr('class', 'star icon-star');
          }
        } else {
          $options.eq(i).attr('class', 'star icon-star-empty');
        }
      }

      if (sel == -1) {
        $this.parent().parent().find('.star-value').html("&ZeroWidthSpace;");
      } else {
        $this.parent().parent().find('.star-value').html(sel);
      }
    });

    $(".starRemoveButton").click(function(event) {
      var $this = $(this);
      var bID = $(this).attr("beatmapid");

      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          console.log(this.responseText);

          $this.addClass("disabled");
          $this.parent().find('.star-value').html("&ZeroWidthSpace;");
          $this.parent().find('.star-value').addClass("unrated");
          $this.parent().find('.identifier').find('.star-rating-list')
            .addClass("unrated");
        }
      };

      $this.attr("rating", "");
      xhttp.open("POST", "SubmitRating.php", true);
      xhttp.setRequestHeader("Content-type",
        "application/x-www-form-urlencoded");
      xhttp.send("bID=" + bID + "&rating=" + -2);
      $this.parent().find('.star-value').html("removing...");

    });

    $(".star-rating-list").click(function(event) {
      var $this = $(this);
      var bID = $(this).attr("beatmapid");
      var sel = event.target.value;
      var rating = 0;

      for (var i = 0; i < 5; i++) {
        if (i < sel) {
          if (event.pageX - event.target.getBoundingClientRect().left <= 6 &&
            sel - 1 == i) {
            rating += 0.5;
          } else {
            rating += 1;
          }
        }
      }

      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          console.log(this.responseText);

          $this.removeClass("unrated");
          $this.parent().parent().find('.star-value').removeClass(
            "unrated");
          $this.parent().parent().find('.star-value').html(rating.toFixed(
            1));
          $this.parent().parent().find('.starRemoveButton').removeClass(
            "disabled");
        }
      };

      $this.attr("rating", rating.toFixed(1));
      submitRating(bID, rating);
      /*
      		xhttp.open("POST", "SubmitRating.php", true);
      		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      		xhttp.send("bID=" + bID + "&rating=" + rating);
            */
      $this.parent().parent().find('.star-value').html("rating...");

    });
  </script>

@endsection
