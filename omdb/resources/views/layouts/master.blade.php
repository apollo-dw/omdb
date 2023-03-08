<!DOCTYPE html>
<html lang="en">

<head>
  <title>@yield('title') | OMDB</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description"
    content="osu! map database is a platform that allows for the rating of osu! beatmaps." />
  <meta property="og:title" content="@yield('title') | OMDB">
  <meta property="og:site_name" content="OMDB">
  <meta name="theme-color" content="#2F4F4F">
  <link rel="stylesheet" href="/font-awesome/css/font-awesome.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js">
  </script>
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32"
    href="/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16"
    href="/favicon-16x16.png">
  <link rel="manifest" href="/site.webmanifest">
  @vite(['resources/scss/app.scss', 'resources/js/app.js'])

  <script>
    // Debounce user input so it's not constantly bombarding the server with requests
    // TODO: Put this in some helper file
    function debounce(func, timeout = 300) {
      let timer;
      return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
          func.apply(this, args);
        }, timeout);
      };
    }

    function showResult(query) {
      debounce(showResultHelper, 1000)(query);
    }

    async function showResultHelper(query) {
      if (query.length == 0) {
        document.getElementById("topBarSearchResults").innerHTML = "";
        document.getElementById("topBarSearchResults").style.display = "none";
        return;
      }

      if (query.length < 3) return;

      let payload = {
        query
      };

      let url = '/search?query=' + encodeURI(query);
      let response = await fetch(url);

      if (!response.ok) return;

      // alert('result ' + JSON.stringify(response.json()));

      let result = await response.json();
      let searchResultsHtml = '';
      for (let beatmap of result) {
        let diff_name = '';
        if ('difficulty_name' in beatmap)
          diff_name = `[${beatmap.difficulty_name}]`;

        searchResultsHtml += `
        <a href="/mapset/${beatmap.beatmapset_id}">
          <div style="margin: 0; background-color: DarkSlateGrey;">
            ${beatmap.artist} - ${beatmap.title}
            ${diff_name}
          </div>
        </a>
        `;
      }
      document.getElementById("topBarSearchResults").innerHTML =
        searchResultsHtml;
      document.getElementById("topBarSearchResults").style.display =
        "block";
    }

    function searchFocus() {
      document.getElementById("topBarSearchResults").style.display = "block";
    }
  </script>
</head>

<body>
  <div class="topBar">
    @if (config('app.env') != 'production')
      <div
        style="background-color: darkOrange; color: black; width: 100%; padding:
        15px 30px; line-height: 1;">
        <b>NOTICE:</b> This is the <u>{{ config('app.env') }}</u> environment,
        not live. Data saved here is not guaranteed to be permanent. If
        something is wrong, please report it to the <a
          href="https://discord.gg/NwcphppBMG">omdb
          dev discord</a>. For the
        production version, click <a
          href="https://omdb.nyahh.net{{ Request::getRequestUri() }}">here</a>.
      </div>
    @endif

    <a href="/" style="margin-right: 8px;color:white;">OMDB - osu! map
      database</a>

    <a href="/" class="topBarLink">home</a>
    <a href="/charts/?year={{ now()->year }}" class="topBarLink">charts</a>
    <div class="topBarDropDown">
      <div class="topBarLink topBarDropDownButton">maps</div>
      <div class="dropdown-content">
        <a href="/maps">latest</a>
        <a href="/maps/random/">random</a>
      </div>
    </div>

    <form class="topBarSearch">
      <input class="topBarSearchBar" type="text" size="30"
        onfocusin="searchFocus()" oninput="showResult(this.value)"
        value="" autocomplete="off"
        placeholder="Search... (or paste link)">
      <div id="topBarSearchResults"></div>
    </form>

    <span style="float:right;">
      @if (Auth::check())
        @php($user = Auth::user())

        <a href="/settings/"><b><i class="icon-cogs"
              style="margin-right:0.5em;"></i></b></a>
        <a href="/profile/{{ $user->user_id }}" style="color:white;">
          <img src="https://s.ppy.sh/a/{{ $user->user_id }}"
               style="height:2rem;vertical-align:middle;" /></a>
        <a href="/profile/{{ $user->user_id }}" style="color:white;">
          <b>{{ $user->osu_user->username }}</b>
        </a>
      @else
        <b><a href="/auth/login">log in</a></b>
      @endif
    </span>
  </div>

  <!--
  <div class="warningBar">
   <i class="icon-warning-sign" style="color:FireBrick;"></i><br>Overall scores and charts are now influenced by per-user weighing! Users with poor rating quality will now contribute less to a map's overall score.
  </div>
  -->

  <div class="content">
    @yield('content')
  </div>

  <div class="footerBar">
    omdb made by <a href="https://omdb.nyahh.net/profile/9558549">apollo</a> |
    icon made by <a href="https://omdb.nyahh.net/profile/7081160">olc</a> |
    <a href="https://github.com/apollo-dw/omdb">github</a> |
    <a href="https://discord.gg/NwcphppBMG">discord</a> |
    <a href="https://ko-fi.com/meowswares">donate</a>
  </div>
</body>

</html>
