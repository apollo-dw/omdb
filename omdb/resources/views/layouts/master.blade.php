<!DOCTYPE html>
<html lang="en">
	<head>
		<title>@yield('title') | OMDB</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <meta name="description" content="osu! map database is a platform that allows for the rating of osu! beatmaps."/>
        <meta property="og:title" content="@yield('title') | OMDB">
        <meta property="og:site_name" content="OMDB">
        <meta name="theme-color" content="#2F4F4F">
		<link rel="stylesheet" href="/font-awesome/css/font-awesome.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
		<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
		<link rel="manifest" href="/site.webmanifest">
		@vite(['resources/scss/app.scss', 'resources/js/app.js'])

        <script>
			function showResult(str) {
			  if (str.length==0) {
				document.getElementById("topBarSearchResults").innerHTML="";
				document.getElementById("topBarSearchResults").style.display="none";
				return;
			  }
			  var xmlhttp=new XMLHttpRequest();
			  xmlhttp.onreadystatechange=function() {
				if (this.readyState==4 && this.status==200) {
				  document.getElementById("topBarSearchResults").innerHTML=this.responseText;
				  document.getElementById("topBarSearchResults").style.display="block";
				}
			  }
			  xmlhttp.open("GET","/beatmapSearch.php?q="+str,true);
			  xmlhttp.send();
			}

			function searchFocus() {
				document.getElementById("topBarSearchResults").style.display="block";
			}
		</script>
	</head>
	<body>
		<div class="topBar">
			<a href="/" style="margin-right: 8px;color:white;">OMDB - osu! map database</a>

			<a href="/"><div class="topBarLink">home</div></a>
			<a href="/charts/"><div class="topBarLink">charts</div></a>
			<div class="topBarDropDown">
				<div class="topBarLink topBarDropDownButton">maps</div>
				<div class="dropdown-content">
					<a href="/maps/?m=02&y=2023">latest</a>
					<a href="/random/">random</a>
				</div>
			</div>

			<form class="topBarSearch">
				<input class="topBarSearchBar" type="text" size="30" onfocusin="searchFocus()" onkeyup="showResult(this.value)" value="" autocomplete="off" placeholder="Search... (or paste link)">
				<div id="topBarSearchResults"></div>
			</form>

			<span style="float:right;">
				@if (Auth::check())
					@php($user = Auth::user())

					<a href="/settings/"><b><i class="icon-cogs" style="margin-right:0.5em;"></i></b></a>
					<a href="/profile/{{ $user->user_id }}" style="color:white;">
						<img src="https://s.ppy.sh/a/{{ $user->user_id }}" style="height:2rem;vertical-align:middle;" />
						&nbsp;
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

		<div class="content" style="margin-top:6em;">
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
