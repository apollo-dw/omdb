<?php
    $PageTitle = "Charts";
    require "../base.php";
    require "../header.php";

    $year = ($_GET["y"] ?? "") === "all-time" ? "all-time" : GetIntParam("y", 2026, "NOO");
    $page = GetIntParam("p", 1, "NOO");
    $yearString = $year == "all-time" ? "All Time" : $year;

    // Notice we removed descriptor preprocessing here. chart.php parses it cleaner.
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

<h1 id="heading"><?php echo "Highest Rated Maps of " . safe_htmlspecialchars($yearString, ENT_QUOTES, "UTF-8"); ?></h1>

<div style="text-align:left;">
    <div class="pagination">
        <span onClick="changePage(page-1)">&laquo;</span>
        <?php for ($i = 1; $i <= 9; $i++) { ?>
            <span class="pageLink page<?php echo $i ?><?php if ($page == $i) echo ' active' ?>" onClick="changePage(<?php echo $i ?>)"><?php echo $i ?></span>
        <?php } ?>
        <span onClick="changePage(page+1)">&raquo;</span>
    </div>
</div>

<div class="flex-container column-when-mobile-container">
	<div id="chart-container" class="flex-item" style="flex: 0 0 75%; padding:0.25em;">
		<?php include 'chart.php'; ?>
	</div>

	<div style="padding-top:0.5em;" class="flex-item column-when-mobile mobile-filters">
        <?php
            $filterConfig = [
                'defaultYear' => $year
            ];
            require "../functions/filter/index.php";
        ?>
        <div style="width: 100%; text-align:right;">
            <button id="update-chart-button" value="Update chart" onClick="pressUpdateChartButton()" disabled>
                Update chart
            </button>
        </div>
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
    var page = parseInt("<?php echo (int)$page; ?>", 10) || 1;

    var orderLabels = {
        "1": "Highest Rated ",
        "2": "Lowest Rated ",
        "3": "Most Rated ",
        "4": "Most Controversial ",
        "5": "Most Underrated "
    };

    var currentPayload = null;

    function changePage(newPage) {
        var nextPage = parseInt(newPage, 10);
        page = Math.max(Math.min(nextPage, 9), 1);
        var payload = currentPayload || window.getOmdbFilterPayload();
        updateChart(payload, page);
    }

    function resetPaginationDisplay(payload) {
        $("#chart-container").removeClass("faded");
        $(".pageLink").removeClass("active");
        $(".page" + page).addClass("active");

        var year = payload ? String(payload.year) : "all-time";
        var order = payload ? String(payload.order) : "1";
        var tokens = payload ? payload.tokens : [];

        var orderString = orderLabels[order] || "Highest Rated ";
        var genreName = "";
        var languageName = "";

        tokens.forEach(function(t) {
            if (t.type === "genre" && !t.exclude) genreName = t.name + " ";
            if (t.type === "language" && !t.exclude) languageName = t.name + " ";
        });

        var yearString = (year === "all-time") ? "All Time" : year;
        $("#heading").html(orderString + languageName + genreName + "Maps of " + yearString);

        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function pressUpdateChartButton() {
        var payload = window.getOmdbFilterPayload();
        currentPayload = payload;
        page = 1;
        updateChart(payload, page);
        $("#update-chart-button").prop("disabled", true);
    }

    $(document).on("omdbFiltersSubmitted", function(event, payload) {
        $("#update-chart-button").prop("disabled", false);
    });

    function updateChart(payload, currentPage) {
        $("#chart-container").addClass("faded");

        var tokensJSON = encodeTokens(payload.tokens);

        var urlParams = new URLSearchParams();
        urlParams.set("y", payload.year);
        urlParams.set("p", currentPage);
        urlParams.set("o", payload.order);
        
        if (payload.tokens.length > 0) {
            urlParams.set("tokens", tokensJSON); 
        }
        
        window.history.replaceState({}, document.title, "?" + urlParams.toString());

        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("chart-container").innerHTML = this.responseText;
                resetPaginationDisplay(payload);
            }
        }

        xmlhttp.open("POST", "chart.php", true);
        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        var params = "y=" + encodeURIComponent(payload.year) +
            "&p=" + currentPage +
            "&o=" + encodeURIComponent(payload.order) +
            "&tokens=" + encodeURIComponent(tokensJSON);
        xmlhttp.send(params);
    }

    function displayTimeRemaining() {
        const now = new Date();
        let nextReset = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), 0, 0, 0, 0));

        if (now.getTime() >= nextReset.getTime()) {
            nextReset.setUTCDate(nextReset.getUTCDate() + 1);
        }

        const timeRemaining = nextReset.getTime() - now.getTime();
        const hoursRemaining = Math.floor((timeRemaining / (1000 * 60 * 60)) % 24);
        const minutesRemaining = Math.floor((timeRemaining / (1000 * 60)) % 60);
        const secondsRemaining = Math.floor((timeRemaining / 1000) % 60);

        const hoursText = hoursRemaining === 1 ? 'hour' : 'hours';
        const minutesText = minutesRemaining === 1 ? 'minute' : 'minutes';
        const secondsText = secondsRemaining === 1 ? 'second' : 'seconds';

        const textElement = document.getElementById('updateText');
        if (textElement) {
            textElement.textContent = `${hoursRemaining} ${hoursText}, ${minutesRemaining} ${minutesText}, ${secondsRemaining} ${secondsText}`;
        }
    }

    displayTimeRemaining();
    setInterval(displayTimeRemaining, 1000);
</script>

<?php require '../footer.php'; ?>