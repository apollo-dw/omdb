<?php
    $PageTitle = "Charts";
	require "../base.php";
    require '../header.php';

    $year = $_GET["y"] ?? 2023;
    $page = $_GET['p'] ?? 1;
    $yearString = $year == "all-time" ? 'All Time' : $year;
?>

<h1 id="heading"><?php echo 'Highest Rated Maps of ' . htmlspecialchars($yearString, ENT_QUOTES, 'UTF-8'); ?></h1>

<div style="text-align:left;">
    <div class="pagination">
        <span onClick="changePage(<?php echo $page-1 ?>)">&laquo;</span>
        <?php for ($i = 1; $i <= 9; $i++) { ?>
            <span class="pageLink page<?php echo $i ?><?php if ($page == $i) echo ' active' ?>" onClick="changePage(<?php echo $i ?>)"><?php echo $i ?></span>
        <?php } ?>
        <span onClick="changePage(<?php echo $page+1 ?>)">&raquo;</span>
    </div>
</div>


<div class="flex-container">
	<div id="chart-container" class="flex-item" style="flex: 0 0 75%; padding:0.25em;">
		<?php
			include 'chart.php';
		?>
	</div>

	<div style="padding-top:0.5em;" class="flex-item">
		<span>Filters</span>
		<hr>
		<form>
			<select name="order" id="order" autocomplete="off" onchange="updateChart();">
				<option value="1" selected="selected">Highest Rated</option>
				<option value="2">Lowest Rated</option>
                <option value="3">Most Rated</option>
                <option value="4">Most Controversial</option>
                <option value="5">Most Underrated</option>
			</select> maps of
			<select name="year" id="year" autocomplete="off" onchange="updateChart();">
                <?php
                    echo '<option value="all-time"';
                    if ($year == -1) {
                        echo ' selected="selected"';
                    }
                    echo '>All Time</option>';

                    for ($i = 2007; $i <= date('Y'); $i++) {
                        echo '<option value="' . $i . '"';
                        if ($year == $i) {
                            echo ' selected="selected"';
                        }
                        echo '>' . $i . '</option>';
                    }
                ?>
			</select><br><br>
            <label>Genre:</label>
            <select name="genre" id="genre" autocomplete="off" onchange="updateChart();">
                <option value="0" selected="selected">Any</option>
                <?php
                    for ($i = 1; $i <= 14; $i++) {
                        $genre = getGenre($i);
                        if (is_null($genre))
                            continue;

                        echo "<option value='{$i}'>$genre</option>";
                    }
                ?>
            </select><br>
            <label>Language:</label>
            <select name="language" id="language" autocomplete="off" onchange="updateChart();">
                <option value="0" selected="selected">Any</option>
                <option value="2">English</option>
                <option value="3">Japanese</option>
                <option value="4">Chinese</option>
                <option value="5">Instrumental</option>
                <option value="6">Korean</option>
                <option value="7">French</option>
                <option value="8">German</option>
                <option value="9">Swedish</option>
                <option value="10">Spanish</option>
                <option value="11">Italian</option>
                <option value="12">Russian</option>
                <option value="13">Polish</option>
                <option value="14">Other</option>
            </select>

            <?php if ($loggedIn) { ?>
            <br><br>
            <input type="checkbox" id="friends" name="friends" onchange="updateChart();">
            <label for="friends">Only include friend ratings<br> <span class="subText">Only the <b>Highest Rated</b> and <b>Lowest Rated</b> sort works with the friend filter right now.</span></label><br>
            <?php } ?>

        </form><br><br>
		<span>Info</span>
		<hr>
		The chart is based on an implementation of the Bayesian average method. It updates <b>once every six hours.</b><br><br>
		The next update will happen in <span id="updateText">---</span><br><br>
        Ratings are weighed based on user rating quality, one contributing factor being their rating distribution.
	</div>

</div>

    <div style="text-align:left;">
        <div class="pagination">
            <span onClick="changePage(<?php echo $page-1 ?>)">&laquo;</span>
            <?php for ($i = 1; $i <= 9; $i++) { ?>
                <span class="pageLink page<?php echo $i ?><?php if ($page == $i) echo ' active' ?>" onClick="changePage(<?php echo $i ?>)"><?php echo $i ?></span>
            <?php } ?>
            <span onClick="changePage(<?php echo $page+1 ?>)">&raquo;</span>
        </div>
    </div>

<script>
    const cronInterval = 6 * 60 * 60 * 1000; // 6 hours
	var page = 1;

    var genres = {
        0 : "",
        2 : "Video Game",
        3 : "Anime",
        4 : "Rock",
        5 : "Pop",
        6 : "Other Genre",
        7 : "Novelty",
        9 : "Hip Hop",
        10 : "Electronic",
        11 : "Metal",
        12 : "Classical",
        13 : "Folk",
        14 : "Jazz",
    }

    var languages = {
        0: "",
        2: "English",
        3: "Japanese",
        4: "Chinese",
        5: "Instrumental",
        6: "Korean",
        7: "French",
        8: "German",
        9: "Swedish",
        10: "Spanish",
        11: "Italian",
        12: "Russian",
        13: "Polish",
        14: "Other Language"
    };
	 
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
        var language = document.getElementById("language").value;

        var orderString = 'Highest Rated ';
        if (order == 2)
            orderString = 'Lowest Rated ';
        else if (order == 3)
            orderString = 'Most Rated ';
        else if (order == 4)
            orderString = 'Most Controversial ';
        else if (order == 5)
            orderString = 'Most Underrated ';
        var genreString = " " + genres[genre] + "   ";
        var languageString = " " + languages[language] + "   ";
        var yearString = year == "all-time" ? 'All Time' : year;

        window.history.replaceState({}, document.title, "?y=" + year + "&p=" + page);

        $('#heading').html(orderString + languageString + genreString + 'Maps of ' + yearString);
	    window.scrollTo({top: 0, behavior: 'smooth'});
	}
	 
	function updateChart() {
		var year = document.getElementById("year").value;
		var order = document.getElementById("order").value;
        var genre = document.getElementById("genre").value;
        var language = document.getElementById("language").value;
        		
        var friendsElement = document.getElementById("friends");
		var onlyFriends = friendsElement ? friendsElement.checked : false;

		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange=function() {
			if (this.readyState==4 && this.status==200) {
				document.getElementById("chart-container").innerHTML=this.responseText;
				resetPaginationDisplay();
			}
		}
		xmlhttp.open("GET","chart.php?y=" + year + "&p=" + page + "&o=" + order + "&g=" + genre + "&l=" + language + "&f=" + String(onlyFriends), true);
		xmlhttp.send();
	}

    function displayTimeRemaining() {
        const currentTime = new Date().getTime();
        const timeSinceLastCronJob = currentTime % cronInterval;
        const timeRemaining = cronInterval - timeSinceLastCronJob;

        const hoursRemaining = Math.floor(timeRemaining / (1000 * 60 * 60));
        const minutesRemaining = Math.floor((timeRemaining / (1000 * 60)) % 60);
        const secondsRemaining = Math.floor((timeRemaining / 1000) % 60);

        const hoursText = hoursRemaining === 1 ? 'hour' : 'hours';
        const minutesText = minutesRemaining === 1 ? 'minute' : 'minutes';
        const secondsText = secondsRemaining === 1 ? 'second' : 'seconds';

        document.getElementById('updateText').textContent = `${hoursRemaining} ${hoursText}, ${minutesRemaining} ${minutesText}, ${secondsRemaining} ${secondsText}`;
    }

    displayTimeRemaining();
    setInterval(displayTimeRemaining, 1000);
</script>

<?php
    require '../footer.php';
?>
