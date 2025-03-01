<?php
    $PageTitle = "Charts";
	require "../base.php";
    require '../header.php';

    $year = $_GET["y"] ?? 2025;
    $page = $_GET['p'] ?? 1;
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
                <?php
                for ($i = 1; $i <= 14; $i++) {
                    $language = getLanguage($i);
                    if (is_null($language))
                        continue;

                    echo "<option value='{$i}'>$language</option>";
                }
                ?>
            </select><br>

            <label>Country:</label>
            <select name="country" id="country" autocomplete="off" onchange="updateChart();">
                <option value="0" selected="selected">Any</option>
                <?php
			
                $result = $conn->query("SELECT DISTINCT Country FROM mappernames WHERE Username IS NOT NULL AND Country IS NOT NULL;");

                while ($row = $result->fetch_assoc()) {
                    $countryCode = $row["Country"];
                    $fullName = getFullCountryName($countryCode);
                    $options[] = array('code' => $countryCode, 'name' => $fullName);
                }

                usort($options, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });

                foreach ($options as $option) {
					if (is_null($option['name']))
						continue;
					
                    echo "<option value=\"{$option['code']}\">{$option['name']}</option>";
                }
                ?>
            </select>
            <br><br>

            <style>
                .popover {
                    display: none;
                    position: absolute;
                    background-color: darkslategray;
                    border: 1px solid #ccc;
                    padding: 10px;
                    z-index: 1000;
                    font-size: 12px;
                    overflow-y: auto;
                    max-height: 30em;
                    margin: 0.5em;
                }

                .popover ul {
                    padding: 0;
                    margin: 0;
                }

                .popover ul li {
                    margin-left: 1em;
                }

                .descriptor span{
                    cursor: pointer;
                    color: white;
                }

                .descriptor span:hover{
                    text-decoration: underline;
                }

                .unusable {
                    color: grey !important;
                    cursor: revert !important;
                }

                .unusable:hover {
                    text-decoration: none !important;
                }

                .descriptor-item {
                    padding: 0.5em;
                    margin: 0.25em;
                    box-sizing: border-box;
                    background-color: DarkSlateGrey;
                    cursor: pointer;
                }

                .descriptor-item:hover {
                    background-color: #203838;
                }
            </style>

            <label for="descriptor-input">Descriptors:</label><br>
            <input type="text" id="searchInput" placeholder="Search...">
            <div id="descriptorTreePopover" class="popover">
                <?php
                $stmt = $conn->prepare("SELECT descriptorID, name, ShortDescription, parentID, Usable FROM descriptors");
                $stmt->execute();
                $result = $stmt->get_result();
                $descriptors = $result->fetch_all(MYSQLI_ASSOC);

                $tree = buildTree($descriptors);
                echo generateTreeHTML($tree);
                ?>
            </div>
            <div id="current-descriptors" class="flex-row-container"></div>
            <br>

            <?php if ($loggedIn) { ?>
            <input type="checkbox" id="hideRated" name="hideRated" onchange="updateChart();">
            <label for="hideRated">Hide already rated maps</label>
            <br>
            <input type="checkbox" id="friends" name="friends" onchange="updateChart();">
            <label for="friends">Only include friend ratings<br> <span class="subText">Only the <b>Highest Rated</b> and <b>Lowest Rated</b> sort works with the friend filter right now.</span></label><br>
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
            <span onClick="changePage(<?php echo $page-1 ?>)">&laquo;</span>
            <?php for ($i = 1; $i <= 9; $i++) { ?>
                <span class="pageLink page<?php echo $i ?><?php if ($page == $i) echo ' active' ?>" onClick="changePage(<?php echo $i ?>)"><?php echo $i ?></span>
            <?php } ?>
            <span onClick="changePage(<?php echo $page+1 ?>)">&raquo;</span>
        </div>
    </div>

<script>
    const cronInterval = 24 * 60 * 60 * 1000; // 1 day
	var page = 1;

    var selectedDescriptors = [];
    <?php foreach ($selectedDescriptors as $descriptor): ?>
    selectedDescriptors.push({ id: <?php echo $descriptor['id']; ?>, name: '<?php echo $descriptor['name']; ?>' });
    <?php endforeach; ?>

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

    $(document).ready(function() {
        updateCurrentDescriptors();

        $('#searchInput').on('focus', function () {
            $('#descriptorTreePopover').show();
        });

        $(document).on('click', function (event) {
            if (!$(event.target).closest('#descriptorTreePopover').length && !$(event.target).is('#searchInput')) {
                $('#descriptorTreePopover').hide();
            }
        });

        $('#searchInput').on('input', function () {
            const searchKeyword = $(this).val().toLowerCase();
            console.log(searchKeyword);
            $('#descriptorTreePopover li').each(function () {
                const text = $(this).text().toLowerCase();
                if (text.includes(searchKeyword)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        $(document).on("click", ".descriptor", function(event) {
            if ($(this).find('.unusable').length > 0) {
                event.stopPropagation();
                return;
            }

            var descriptor = $($(this).contents()[0]).text();
            var descriptorID = $(this).data('descriptor-id');
            selectedDescriptors.push({ id: descriptorID, name: descriptor });
            updateCurrentDescriptors();
            $("#descriptor-input").val("");
            $("#descriptor-suggestions").empty();
            updateChart()
            event.stopPropagation();
        });

        $(document).on("click", ".descriptor-item", function() {
            var index = $(this).data("index");
            selectedDescriptors.splice(index, 1);
            updateCurrentDescriptors();
            updateChart()
        });

        function updateCurrentDescriptors() {
            $("#current-descriptors").empty();
            selectedDescriptors.forEach(function(descriptor, index) {
                $("#current-descriptors").append(
                    $("<span>").addClass("descriptor-item").text(descriptor.name)
                );
            });
        }
    });

	function changePage(newPage) {
		page = Math.min(Math.max(newPage, 1), 9);
		updateChart();
	}

	function resetPaginationDisplay() {
        $("#chart-container").removeClass("faded");
		$(".pageLink").removeClass("active");

        console.log(selectedDescriptors)

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

        var descriptorUrlArg = "";
        if (selectedDescriptors.length > 0) {
            descriptorUrlArg = "&descriptors=" + selectedDescriptors.map((descriptor) => {
                return descriptor.name;
            }).join(',');
        }

        window.history.replaceState({}, document.title, "?y=" + year + "&p=" + page + descriptorUrlArg);

        $('#heading').html(orderString + languageString + genreString + 'Maps of ' + yearString);
	    window.scrollTo({top: 0, behavior: 'smooth'});
	}

	function updateChart() {
        $("#chart-container").addClass("faded");
		var year = document.getElementById("year").value;
		var order = document.getElementById("order").value;
        var genre = document.getElementById("genre").value;
        var language = document.getElementById("language").value;
        var country = document.getElementById("country").value;

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

        var descriptorsJSON = JSON.stringify(selectedDescriptors);

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

        document.getElementById('updateText').textContent = `${hoursRemaining} ${hoursText}, ${minutesRemaining} ${minutesText}, ${secondsRemaining} ${secondsText}`;
    }

    displayTimeRemaining();
    setInterval(displayTimeRemaining, 1000);
</script>

<?php
    require '../footer.php';
?>
