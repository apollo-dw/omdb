<?php
    $PageTitle = "Charts";
	require "../base.php";
    require '../header.php';

    $year = ($_GET["y"] ?? "") === "all-time" ? "all-time" : GetIntParam("y", 2026, "NOO");
    $page = GetIntParam('p', 1, "NOO");
    $yearString = $year == "all-time" ? 'All Time' : $year;

    $result = $conn->query("SELECT DescriptorID, Name FROM descriptors WHERE Usable = 1");
    $descriptors = $result->fetch_all(MYSQLI_ASSOC);

    $requestedDescriptors = isset($_GET['descriptors']) ? explode(',', $_GET['descriptors']) : [];
    foreach ($descriptors as $descriptor) {
        if (in_array($descriptor['Name'], $requestedDescriptors)) {
            $selectedDescriptors[] = ['id' => $descriptor['DescriptorID'], 'name' => $descriptor['Name']];
        }
    }

    function generateTreeHTML($tree) {
        $html = '<ul>';
        foreach ($tree as $node) {
            $descriptorID = $node['descriptorID'];
            $isUsable = $node['Usable'];

            $class = $isUsable ? '' : 'class="unusable"';

            $html .= '<li class="descriptor" data-descriptor-id="' . $descriptorID . '"><span ' . $class . ' >' . $node['name'] . '</span>';
            if (isset($node['children'])) {
                $html .= generateTreeHTML($node['children']);
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    function buildTree(array &$elements, $parentID = null) {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['parentID'] === $parentID) {
                $children = buildTree($elements, $element['descriptorID']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
?>

<h1 id="heading"><?php echo 'Highest Rated Maps of ' . safe_htmlspecialchars($yearString, ENT_QUOTES, 'UTF-8'); ?></h1>

<div style="text-align:left;">
    <div class="pagination">
        <span onClick="changePage(page-1)">&laquo;</span>
        <?php for ($i = 1; $i <= 9; $i++) { ?>
            <span class="pageLink page<?php echo $i ?><?php if ($page == $i) echo ' active' ?>" onClick="changePage(<?php echo $i ?>)"><?php echo $i ?></span>
        <?php } ?>
        <span onClick="changePage(page+1)">&raquo;</span>
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
		<form onsubmit="return false">
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
            <?php include "../functions/filter.php"; ?>
            <br>

            <?php if ($loggedIn) { ?>
            <input type="checkbox" id="hideRated" name="hideRated" onchange="updateChart();">
            <label for="hideRated">Hide already rated maps</label>
            <br>
            <input type="checkbox" id="friends" name="friends" onchange="updateChart();">
            <label for="friends">Only include friend ratings<br></label>
            <?php } ?> <br>

            Exclude: <br>
            <input type="checkbox" id="excludeLoved" name="excludeLoved" onchange="updateChart();">
            <label for="excludeLoved">Loved maps</label> <br>
            <input type="checkbox" id="excludeGraveyard" name="excludeGraveyard" onchange="updateChart();">
            <label for="excludeGraveyard">Graveyard maps</label> <br>
			<input type="checkbox" id="excludeRanked" name="excludeRanked" onchange="updateChart();">
            <label for="excludeRanked">Ranked maps</label>

        </form><br><br>
		<span>Info</span>
		<hr>
		The chart is based on an implementation of the Bayesian average method. It updates <b>once every day.</b><br><br>
		The next update will happen in <span id="updateText">---</span><br><br>
        Ratings are weighed based on user rating quality, one contributing factor being their rating distribution.
	</div>

</div>

    <div style="text-align:left;">
        <div class="pagination">
            <span onClick="changePage(page-1)">&laquo;</span>
            <?php for ($i = 1; $i <= 9; $i++) { ?>
                <span class="pageLink page<?php echo $i ?><?php if ($page == $i) echo ' active' ?>" onClick="changePage(<?php echo $i ?>)"><?php echo $i ?></span>
            <?php } ?>
            <span onClick="changePage(page+1)">&raquo;</span>
        </div>
    </div>

<script>
    const cronInterval = 24 * 60 * 60 * 1000; // 1 day
    var page = parseInt("<?php echo (int)$page; ?>", 10) || 1;

    var currentFilters = [];

    $(document).on('omdbFiltersUpdated', function(event, activeFilters) {
        currentFilters = activeFilters;
        page = 1; // Reset to page 1 whenever a filter is added/removed
        updateChart(); 
    });

    function changePage(newPage) {
        var nextPage = parseInt(newPage, 10);
        page = Math.max(Math.min(nextPage, 9), 1);
        updateChart();
    }

    function resetPaginationDisplay() {
        $("#chart-container").removeClass("faded");
        $(".pageLink").removeClass("active");

        var pageLink = '.page' + page;
        $(pageLink).addClass("active");

        var year = document.getElementById("year").value;
        var order = document.getElementById("order").value;

        var orderString = 'Highest Rated ';
        if (order == 2) orderString = 'Lowest Rated ';
        else if (order == 3) orderString = 'Most Rated ';
        else if (order == 4) orderString = 'Most Controversial ';
        else if (order == 5) orderString = 'Most Underrated ';

        var g = 0, l = 0, c = 0;
        var genreName = "", languageName = "";
        var descriptorNames = [];

        currentFilters.forEach(function(filter) {
            if (filter.type === 'genre') { g = filter.id; genreName = filter.name + "   "; }
            if (filter.type === 'language') { l = filter.id; languageName = filter.name + "   "; }
            if (filter.type === 'country') { c = filter.id; }
            if (filter.type === 'descriptor') descriptorNames.push(filter.name);
        });

        var yearString = year === "all-time" ? 'All Time' : year;

        var urlParams = new URLSearchParams();
        urlParams.set('y', year);
        urlParams.set('p', page);

        if (g > 0) urlParams.set('g', g);
        if (l > 0) urlParams.set('l', l);
        if (c !== 0 && c !== "") urlParams.set('c', c);
        if (descriptorNames.length > 0) urlParams.set('descriptors', descriptorNames.join(','));

        window.history.replaceState({}, document.title, "?" + urlParams.toString());

        $('#heading').html(orderString + languageName + genreName + 'Maps of ' + yearString);
        window.scrollTo({top: 0, behavior: 'smooth'});
    }

    function updateChart() {
        $("#chart-container").addClass("faded");
        
        var year = document.getElementById("year").value;
        var order = document.getElementById("order").value;

        var genre = 0;
        var language = 0;
        var country = 0;
        var mappedDescriptors = [];

        currentFilters.forEach(function(filter) {
            if (filter.type === 'genre') genre = filter.id;
            if (filter.type === 'language') language = filter.id;
            if (filter.type === 'country') country = filter.id;
            if (filter.type === 'descriptor') mappedDescriptors.push({ id: filter.id, name: filter.name });
        });

        var friendsElement = document.getElementById("friends");
        var onlyFriends = friendsElement ? friendsElement.checked : false;

        var hideRatedElement = document.getElementById("hideRated");
        var hideRated = hideRatedElement ? hideRatedElement.checked : false;

        var excludeGraveyardElement = document.getElementById("excludeGraveyard");
        var excludeGraveyard = excludeGraveyardElement ? excludeGraveyardElement.checked : false;

        var excludeLovedElement = document.getElementById("excludeLoved");
        var excludeLoved = excludeLovedElement ? excludeLovedElement.checked : false;
        
        var excludeRankedElement = document.getElementById("excludeRanked");
        var excludeRanked = excludeRankedElement ? excludeRankedElement.checked : false;

        var descriptorsJSON = JSON.stringify(mappedDescriptors);

        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange=function() {
            if (this.readyState==4 && this.status==200) {
                document.getElementById("chart-container").innerHTML=this.responseText;
                resetPaginationDisplay();
            }
        }

        xmlhttp.open("POST", "chart.php", true);
        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        var params = "y=" + year +
            "&p=" + page +
            "&o=" + order +
            "&g=" + genre +
            "&l=" + language +
            "&c=" + country +
            "&f=" + String(onlyFriends) +
            "&descriptors=" + encodeURIComponent(descriptorsJSON) +
            "&alreadyRated=" + String(hideRated) +
            "&excludeLoved=" + String(excludeLoved) +
            "&excludeGraveyard=" + String(excludeGraveyard) +
            "&excludeRanked=" + String(excludeRanked);
        xmlhttp.send(params);
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

        const updateTextElement = document.getElementById('updateText');
        if (updateTextElement) {
            updateTextElement.textContent = `${hoursRemaining} ${hoursText}, ${minutesRemaining} ${minutesText}, ${secondsRemaining} ${secondsText}`;
        }
    }

    displayTimeRemaining();
    setInterval(displayTimeRemaining, 1000);
</script>

<?php
    require '../footer.php';
?>
