<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo $PageTitle; ?> | OMDB</title>
		<meta charset="utf-8">
		<link rel="stylesheet" href="/font-awesome/css/font-awesome.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
		<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
		<link rel="manifest" href="/site.webmanifest">
		<style>
			body {
				font-family: Verdana, sans-serif;
				font-size: 13px;
				background-color: black;
				color: white;
				margin: 0;
				overflow-y: scroll;
			}
			
			.centered {
				margin-left: auto;
				margin-right: auto;
				width: 50%;
			}

			.topBar {
				position: fixed;
				left: 0px;
				right: 0px;
				top: 0px;
				background-color: DarkSlateGrey;
				width: 100%;
				box-sizing: border-box;
				font-size: 0.75rem;
				line-height: 3rem;
				padding-left: 20%;
				padding-right: 20%;
			}
			
			.footerBar {
				display: block;
				width: 100%;
				padding-left: 20%;
				padding-right: 20%;
				box-sizing: border-box;
				margin-top: 15rem;
				margin-bottom: 5rem;
				margin-left: 0;
				color: grey;
				text-align: center;
			}
			
			a {
				color: white;
				text-decoration: none;
			}
			
			a:hover {
				text-decoration: underline;
			}
			
			.topBarLink {
				height:100%;
				min-width:3rem;
				background-color: #182828;
				display:inline-block;
				text-align:center;
				padding-left:0.25rem;
				padding-right:0.25rem;
				margin: 0px;
			}
			
			a .topBarLink {
				color: white;
			}
			
			.topBarLink:hover {
				background-color: #0C1515;
			}
			
			.topBarDropDown {
				display: inline-block;
				margin: 0px;
				padding: 0px;
			}
			
			.topBarSearch {
				display: inline-block;
				margin: 0px;
				padding: 0px;
			}
			
			#topBarSearchResults {
				display: none;
				position: absolute;
				z-index: 1;
				line-height: 1.5em;
				overflow-y: auto;
				height: 19em;
				width: 25em;
				border-radius: 6px;
			}
			
			.topBarSearchBar {
				margin-left: 8px;
				border-radius: 8px;
				font-family: MS UI Gothic, Verdana, sans-serif;
				z-index: 2;
			}
			
			#topBarSearchResults a {
				float: none;
				text-decoration: none;
				display: block;
				text-align: left;
				color: white;
			}
			
			#topBarSearchResults a:hover {
				text-decoration: underline;
			}
			
			.dropdown-content {
				display: none;
				position: absolute;
				background-color: #395f5f;
				min-width: 8rem;
				z-index: 1;
			}

			.dropdown-content a {
				float: none;
				color: white;
				padding: 0.01rem 1rem;
				text-decoration: none;
				display: block;
				text-align: left;
			}
			
			.dropdown-content a:hover {
				background-color: DarkSlateGrey;
			}

			.topBarDropDown:hover .dropdown-content {
				display: block;
			}
			
			.content {
				margin-top: 3rem;
				padding-left: 20%;
				padding-right: 20%;
				min-height: 50%;
			}
			
			.warningBar {
				margin-top: 5rem;
				padding: 1rem;
				border: 1px solid FireBrick;
				border-radius: 16px;
				width: 27.5%;
				min-height: 3.5em;
				text-align: center;
				margin-left: auto;
				margin-right: auto;
			}
			
			.star-rating-list {
				cursor: pointer;
				white-space: nowrap;
				display: flex;
				padding: 0;
				font-size: 18px;
				box-sizing: border-box;
			}
			
			.starRemoveButton {
				opacity: 0;
				font-size: 16px;
				padding-left: 0.25em;
				box-sizing: border-box;
			}
			
			@media only screen and (max-width: 900px) {
				.starRemoveButton {
					opacity: 1;
				}
			}
			
			.identifier:hover ~ .starRemoveButton:not(.disabled), .starRemoveButton:hover:not(.disabled) {
				opacity: 0.5;
				cursor: pointer;
			}
			
			.disabled {
				pointer-events: none;
			}
			
			.star-value {
				font-size: 18px;
			}
			
			.star {
				white-space: nowrap;
			}
			
			span.subText{
				font-size: 11px;
				color: #a8a8a8;
			}
			
			.ratingContainer {
				background-color: DarkSlateGrey;
				align-items: center;
			}
			
			.flex-container {
				display: flex;
				width: 100%;
			}
			
			.flex-child {
				margin: 0.25em;
				vertical-align: middle;
			}
				
			.diffThumb {
				height: 32px;
				width: 32px;
				border: 1px solid #ddd;
				object-fit: cover;
			}
			
			select {
				margin: 0.2em;
				padding: 0.2em;
				border: none;
				border-radius: 1px;
				background-color: DarkSlateGrey;
				color: white;
			}
			
			.commentContainer {
				background-color: DarkSlateGrey;
				padding: 0px;
				align-items: center;
				align-content: center; /* new */
				flex-wrap: wrap;
			}
			
			.commentHeader {
				align-items: center;
				width: 100%;
				background-color: #203838;
				padding: 0.25em;
				margin: 0;
			}
			
			.comment {
				padding-left: 1em;
				padding-right: 1em;
				margin-bottom: 1.5em;
				min-height: 3em;
				width: 100%;
			}
			
			.commentComposer {
				width: 100%;
				text-align: right;
				margin: 1em;
			}
			
			textarea#commentForm {
				width: 100%;
				text-align: left;
				resize: none;
				height: 6em;
				background-color: #203838;
				color: white;
				border: 1px solid white;
			}
			
			input#commentSubmit {
				margin: 1em;
				width: 6em;
				border: 1px solid white;
				background-color: #203838;
				color: white;
			}
			
			input#commentSubmit:hover {
				background-color: #182828;
			}
			
			.icon-remove {
				cursor: pointer;
			}
			
			.osuTimestamp {
				background-color: #203838;
				border-radius: 2px;
				padding: 0.05em;
			}

            .bolded{
                font-weight: bold;
            }
		</style>
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
			<a href="/" style="margin-right: 8px;">OMDB - osu! map database</a>
			
			<a href="/"><div class="topBarLink">home</div></a>
			<a href="/charts/"><div class="topBarLink">charts</div></a>
			<div class="topBarDropDown">
				<div class="topBarLink topBarDropDownButton">maps</div>
				<div class="dropdown-content">
					<a href="/maps/?m=01&y=2023">latest</a>
					<a href="/random/">random</a>
				</div>
			</div>
			
			<form class="topBarSearch">
				<input class="topBarSearchBar" type="text" size="30" onfocusin="searchFocus()" onkeyup="showResult(this.value)" value="" autocomplete="off" placeholder="Search... (or paste link)">
				<div id="topBarSearchResults"></div>
			</form>
			
			<?php
				function FetchOsuOauthLink($oauthClientID) {
					// the creation of the local function scope in php was a disaster for humanity -t
					$oauthFields = array(
						"client_id" => $oauthClientID,
						"redirect_uri" => 'https://' . $_SERVER['SERVER_NAME'] . '/callback.php',
						"response_type" => "code",
						"scope" => "identify public",
						"state" => "z"
					);
					return 'https://osu.ppy.sh/oauth/authorize?' . http_build_query($oauthFields);
				}
			?>

			<span style="float:right;">
				<?php
					if ($loggedIn) {
				?>
					<a href="/profile/<?php echo $userId; ?>" style="color:white;"><img src="https://s.ppy.sh/a/<?php echo $userId; ?>" style="height:2rem;vertical-align:middle;">&ZeroWidthSpace;</img></a> <a href="/profile/<?php echo $userId; ?>" style="color:white;"><b><?php echo $userName; ?></b></a>
				<?php
					} else {
						include_once 'sensitiveStrings.php'; // needed for $clientID
				?>
					<b><a href=<?php echo FetchOsuOauthLink($clientID); ?> style="color:white;">log in</a></b>
				<?php
					}
				?>
			</span>
		</div>

		<!--
		<div class="warningBar">
			<i class="icon-warning-sign" style="color:FireBrick;"></i><br>Overall scores and charts are now influenced by per-user weighing! Users with poor rating quality will now contribute less to a map's overall score.
		</div>
		-->
		
		<div class="content" style="margin-top:6em;">
