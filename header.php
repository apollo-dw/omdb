<?php require_once 'base.php'; ?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo $PageTitle; ?> | OMDB</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <meta name="description" content="osu! map database is a platform that allows for the rating of osu! beatmaps."/>
        <meta property="og:title" content="<?php echo $PageTitle; ?> | OMDB">
        <meta property="og:site_name" content="OMDB">
        <meta name="theme-color" content="#2F4F4F">
		<link rel="stylesheet" href="/font-awesome/css/font-awesome.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/chroma-js/2.4.2/chroma.min.js"></script>
		<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
		<link rel="manifest" href="/site.webmanifest">
        <link rel="stylesheet" type="text/css" href="/style.css?v=20" />
        <script src="/script.js?v=3"></script>
	</head>
	<body>
		<div class="topBar">
			<a href="/" style="margin-right: 8px;color:white;">OMDB</a>
			<a href="/"><div class="topBarLink">home</div></a>
			<a href="/charts/"><div class="topBarLink">charts</div></a>
			<div class="topBarDropDown">
				<div class="topBarLink topBarDropDownButton">maps</div>
				<div class="dropdown-content">
					<a href=" <?php echo '/maps/?m=' . date('m') . '&y=' . date('Y'); ?>">latest</a>
					<a href="/random/">random</a>
				</div>
			</div>
			
			<form class="topBarSearch" onsubmit="return false">
				<input class="topBarSearchBar" type="text" size="30" onfocusin="searchFocus()" onkeyup="showResult(this.value)" value="" autocomplete="off" placeholder="Search... (or paste link)">
				<div id="topBarSearchResults"></div>
			</form>

			<?php
				function FetchOsuOauthLink($oauthClientID, $redirect_url = "/") {
					if (empty($_SESSION["LOGIN_CSRF_TOKEN"])) {
						$csrf_token = bin2hex(random_bytes(24));
						$_SESSION["LOGIN_CSRF_TOKEN"] = $csrf_token;
					}
					$csrf_token = $_SESSION["LOGIN_CSRF_TOKEN"];

					$state = array(
						"csrf_token" => $csrf_token,
						"redirect_url" => $redirect_url,
					);
					$state_encoded = urlencode(json_encode($state));

					// the creation of the local function scope in php was a disaster for humanity -t
					$oauthFields = array(
						"client_id" => $oauthClientID,
						"redirect_uri" => relUrl("/callback.php"),
						"response_type" => "code",
						"scope" => "identify public",
						"state" => $state_encoded,
					);
					return 'https://osu.ppy.sh/oauth/authorize?' . http_build_query($oauthFields);
				}
			?>

			<span style="float:right;">
                <div class="topBarDropDown">
                    <div class="topBarLink topBarDropDownButton"><i class="icon-pencil"></i></div>
                    <div class="dropdown-content">
                        <a href="/descriptor/proposal/list">descriptor proposals</a>
                        <a href="/edit-queue/">edit queue</a>
                        <a href="/project-legacy/">project legacy</a>
                    </div>
			    </div>
                <div class="topBarDropDown">
                    <div class="topBarLink topBarDropDownButton">
                        <?php
                            switch($mode){
                                case 0:
                                    echo "<div class='ruleset-icon osu'></div>"; break;
                                case 1:
                                    echo "<div class='ruleset-icon taiko'></div>"; break;
                                case 2:
                                    echo "<div class='ruleset-icon catch'></div>"; break;
                                case 3:
                                    echo "<div class='ruleset-icon mania'></div>"; break;
                            }
                        ?>
                    </div>
                    <div class="dropdown-content" style="font-size: 0.75rem;min-width: 3.5rem;text-align:center;">
                        <a id="osuLink" href=""><div class="ruleset-icon osu"></div></a>
                        <a id="taikoLink" href=""><div class="ruleset-icon taiko"></div></a>
                        <a id="catchLink" href=""><div class="ruleset-icon catch"></div></a>
                        <a id="maniaLink" href=""><div class="ruleset-icon mania"></div></a>
                    </div>
			    </div>
				<?php
					if ($loggedIn) {
				?>
                        <a href="/dashboard/"><div class="topBarLink">dashboard</div></a>
                        <a href="/settings/"><b><i class="icon-cogs" style="margin-right:0.5em;"></i></b></a>
                        <a href="/profile/<?php echo $userId; ?>" style="color:white;"><img src="https://s.ppy.sh/a/<?php echo $userId; ?>" style="height:2rem;vertical-align:middle;">&ZeroWidthSpace;</img></a>
                        <a class="topBarUsername" href="/profile/<?php echo $userId; ?>" style="color:white;"><b><?php echo $userName; ?></b></a>
				<?php
					} else {
						include_once 'sensitiveStrings.php'; // needed for $clientID
				?>
					<b><a href=<?php echo FetchOsuOauthLink($clientID, $_SERVER["REQUEST_URI"]); ?>>log in</a></b>
				<?php
					}
				?>
			</span>
		</div>
		
		<div class="content" style="margin-top:6em;">
            <!--
            <div class="warningBar">
                <i class="icon-warning-sign" style="color:FireBrick;"></i><br>Overall scores and charts are now influenced by per-user weighing! Users with poor rating quality will now contribute less to a map's overall score.
            </div>
            -->