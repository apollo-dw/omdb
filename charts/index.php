<?php
    $PageTitle = "Charts";
	require "../base.php";
    require '../header.php';

    $year = $_GET["y"] ?? 2023;
    $page = $_GET['p'] ?? 1;
    $yearString = $year == "all-time" ? 'All Time' : $year;
?>

<h1 id="heading"><?php echo 'Highest Rated Maps of ' . $yearString; ?></h1>

<style>
	.flex-container{
		display: flex;
		width: 100%;
	}
	
	.diffContainer{
		background-color:DarkSlateGrey;
		align-items: center;
	}
	
	.diffBox{
		padding:0.5em;
		flex-grow: 1;
		height:100%;
	}
	
	.diffbox a{
		color: white;
	}
	
	.diffThumb{
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
        <span onClick="changePage(<?php echo $page-1 ?>)">&laquo;</span>
        <?php for ($i = 1; $i <= 9; $i++) { ?>
            <span class="pageLink page<?php echo $i ?><?php if ($page == $i) echo ' active' ?>" onClick="changePage(<?php echo $i ?>)"><?php echo $i ?></span>
        <?php } ?>
        <span onClick="changePage(<?php echo $page+1 ?>)">&raquo;</span>
    </div>
</div>


<div class="flex-container">
	<div id="chartContainer" class="flex-item" style="flex: 0 0 75%; padding:0.5em;">
		<?php
			include 'chart.php';
		?>
	</div>

	<div style="padding:1em;" class="flex-item">
		<span>Filters</span>
		<hr>
		<form>
			<select name="order" id="order" autocomplete="off" onchange="updateChart();">
				<option value="1" selected="selected">Highest Rated</option>
				<option value="2">Lowest Rated</option>
                <option value="3">Most Rated</option>
                <option value="4">Most Controversial</option>
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
			</select>
            <br><br>
            <label>Genre:</label>
            <select name="genre" id="genre" autocomplete="off" onchange="updateChart();">
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
            <br>
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

        </form><br>
		<span>Info</span>
		<hr>
		Chart is based on an implementation of the Bayesian average method.<br><br>
		The chart updates <b>once every six hours.</b><br><br>
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
	const numOfPages = <?php echo floor($conn->query("SELECT Count(*) FROM `beatmaps` WHERE `Rating` IS NOT NULL;")->fetch_row()[0] / 50) + 1; ?>;
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

		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange=function() {
			if (this.readyState==4 && this.status==200) {
				document.getElementById("chartContainer").innerHTML=this.responseText;
				resetPaginationDisplay();
			}
		}
		xmlhttp.open("GET","chart.php?y=" + year + "&p=" + page + "&o=" + order + "&g=" + genre + "&l=" + language, true);
		xmlhttp.send();
	}
</script>

<?php
    require '../footer.php';
?>
