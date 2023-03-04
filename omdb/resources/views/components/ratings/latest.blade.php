@foreach ($ratings as $rating)
  <div class="flex-container ratingContainer" <?php
  if ($loop->odd) {
      echo "style='background-color:#203838;' altcolour";
  }
  ?>>
    <div class="flex-child">
      <a href="/profile/{{ $rating->user_id }}">
        <img src="https://s.ppy.sh/a/{{ $rating->user_id }}"
          style="height:24px;width:24px;"
          title="{{ $rating->osu_user->username }}" />
      </a>
    </div>
    <div class="flex-child" style="flex:0 0 70%;">
      <x-ratings.display :rating="$rating" />
      on
      {{ $rating->beatmap->difficulty_name }}
    </div>
    <div class="flex-child" style="width:100%;text-align:right;">
      {{ $rating->updated_at->diffForHumans() }}
    </div>
  </div>
@endforeach

{{--
<br>
<div style="text-align:center;">
    <div class="pagination">
        <b><span><?php if ($page > 1) {
            echo "<a href='javascript:lowerRatingPage()'>&laquo; </a>";
        } ?></span></b>
        <span id="page"><?php echo $page; ?></span>
        <b><span><?php if ($page < $amountOfSetPages) {
            echo "<a href='javascript:increaseRatingPage()'>&raquo; </a>";
        } ?></span></b><br>
        <span class="subText">Page</span>
    </div>
</div>
<script>
    var ratingPage = 1;

    function lowerRatingPage() {
        changeRatingPage(ratingPage - 1)
    }

    function increaseRatingPage() {
        changeRatingPage(ratingPage + 1)
    }

    function changeRatingPage(newPage) {
        ratingPage = Math.min(Math.max(newPage, 1), <?php echo $amountOfSetPages; ?>);
        updateRatings();
    }

    function updateRatings() {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange=function() {
            if (this.readyState==4 && this.status==200) {
                document.getElementById("setRatingsDisplay").innerHTML=this.responseText;
            }
        }
        xmlhttp.open("GET","ratings.php?p=" + ratingPage + "&id=" + <?php echo $mapset_id; ?>, true);
        xmlhttp.send();
    }

</script>

    --}}
