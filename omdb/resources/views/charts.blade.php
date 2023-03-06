@extends('layouts.master')

@section('title', 'Home')

@section('content')

  <h1 id="heading">Highest Rated Maps of {{ $year ?? 'All Time' }}</h1>

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

    .pagination {
      display: inline-block;
      color: DarkSlateGrey;
    }

    .pagination span {
      float: left;
      padding: 8px 16px;
      text-decoration: none;
      cursor: pointer;
    }

    .active {
      font-weight: 900;
      color: white;
    }
  </style>

  <div style="text-align:left;">
    <div class="pagination">
      <x-paginator :page="$page" :num-pages="$num_pages" />
    </div>
  </div>

  <div class="flex-container">
    <!-- QUERY: {{ $query_string }} -->
    <div id="chartContainer" class="flex-item" style="flex: 0 0 75%; padding:0.5em;">
      <x-charts.display :beatmaps="$beatmaps" :startAt="$start_at" />
    </div>

    <div style="padding:1em;" class="flex-item">
      <span>Filters</span>
      <hr>
      <form>
        <select name="order" id="order" autocomplete="off"
          onchange="updateChart();">
          <option value="1" selected="selected">Highest Rated</option>
          <option value="2">Lowest Rated</option>
          <option value="3">Most Rated</option>
        </select> maps of
        <select name="year" id="year" autocomplete="off"
          onchange="updateChart();">
          <option value="-1"
            @if ($year == -1) selected="selected" @endif>All Time
          </option>

          @for ($i = 2007; $i <= date('Y'); $i++)
            <option value="{{ $i }}"
              @if ($year == $i) echo ' selected="selected"'; @endif>
              {{ $i }}</option>
          @endfor
        </select>
        <br><br>
        <label>Genre:</label>
        <select name="genre" id="genre" autocomplete="off"
          onchange="updateChart();">
          <option value="0" selected="selected">Any</option>
          <option value="2">Video Game</option>
          <option value="3">Anime</option>
          <option value="4">Rock</option>
          <option value="5">Pop</option>
          <option value="6">Other</option>
          <option value="7">Novelty</option>
          <option value="9">Hip Hop</option>
          <option value="10">Electronic</option>
          <option value="11">Metal</option>
          <option value="12">Classical</option>
          <option value="13">Folk</option>
          <option value="14">Jazz</option>
        </select>
      </form><br>
      <span>Info</span>
      <hr>
      Chart is based on an implementation of the Bayesian average method.<br><br>
      The chart updates once every <b>hour.</b><br><br>
      Ratings are weighed based on user rating quality, one contributing factor
      being their rating distribution.
    </div>

  </div>

  <div style="text-align:left;">
    <x-paginator :page="$page" :num-pages="$num_pages" />
  </div>

  <script>
    const numOfPages = {{ $num_pages }};
    var page = 1;

    var genres = {
      0: "",
      2: "Video Game",
      3: "Anime",
      4: "Rock",
      5: "Pop",
      6: "Other Genre",
      7: "Novelty",
      9: "Hip Hop",
      10: "Electronic",
      11: "Metal",
      12: "Classical",
      13: "Folk",
      14: "Jazz",
    }

    function changePage(newPage) {
      page = Math.min(Math.max(newPage, 1), 9);
      updateChart();
    }

    function resetPaginationDisplay() {
      $(".pageLink").removeClass("active");

      var pageLink = '.page' + page;

      $(pageLink).addClass("active");

      var year = document.getElementById("year").value;
      var order = document.getElementById("order").value;
      var genre = document.getElementById("genre").value;

      var orderString = 'Highest Rated ';
      if (order == 2)
        orderString = 'Lowest Rated ';
      else if (order == 3)
        orderString = 'Most Rated ';
      var genreString = " " + genres[genre] + " ";
      var yearString = year == -1 ? 'All Time' : year;

      $('#heading').html(orderString + genreString + 'Maps of ' + yearString);
    }

    function updateChart() {
      var year = document.getElementById("year").value;
      var order = document.getElementById("order").value;
      var genre = document.getElementById("genre").value;

      /*
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          document.getElementById("chartContainer").innerHTML = this
            .responseText;
          resetPaginationDisplay();
        }
      }
      xmlhttp.open("GET", "chart.php?y=" + year + "&p=" + page + "&o=" + order +
        "&g=" + genre, true);
      xmlhttp.send();
      */

      let url = "?year=" + year + "&page=" + page + "&order=" + order;
      location.href = url;
    }
  </script>

@endsection
