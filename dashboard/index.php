<?php
    $PageTitle = "Dashboard";
    require "../base.php";
    require '../header.php';
    require 'RetrieveRecommendations.php';

    if (!$loggedIn){
        die("log in to view this page lol");
    }

    $recommendations = RetrieveRecommendations($conn, $userId);
?>

    <style>
        #scrollable-element	{
            height:40em;
            display:flex;
            flex-direction:column;
            background-color: DarkSlateGrey;
            overflow-y: scroll;
            width: 50%;
            margin: 0.2em;
            box-sizing: border-box;
        }

        .scrollable{
            overflow-y: scroll;
        }

        .map {
            width:100%;
            background-color: DarkSlateGrey;
            display:flex;
            align-items: center;
            box-sizing: border-box;
            margin-top: 0.5em;
            margin-bottom: 0.5em;
            height: 4em;
        }

        .map:nth-child(even) {
            background-color: #203838;
        }

        .difficulty-name  {
            min-width:12em;
            padding-left: 0.5em;
            padding-right: 1em;
        }

        .unrated{
            color: grey;
        }

        .recommendation {
            background-color: DarkSlateGrey;
            padding: 0.5em;
            font-size: 11px;
        }

        .diffThumb{
            height: 5em;
            width: 5em;
            border: 1px solid #ddd;
            object-fit: cover;
        }
    </style>

    <h1>Dashboard</h1>
    <span class="subText">a page specifically tailored to you! this page currently features recommendations and recently played maps.</span><br><br>

    <div class="flex-container">
        <div class="flex-child scrollable" style="max-height:40em;width:50%;margin: 0.2em;box-sizing: border-box;">
            <?php
                if (isset($recommendations["error"])) {
                    ?>
                    Your recommendations are currently unavailable. Here's some potential reasons why:
                    <ul>
                        <li>You haven't rated enough beatmaps.</li>
                        <li>You're too new, or you only just rated enough maps recently (the system refreshes weekly).</li>
                        <li>Something's broken!</li>
                    </ul>

                    If you believe you should be seeing recommendations, and you waited longer than a week, then feel free to contact me.
                    <?php
                } else {
                    foreach($recommendations as $beatmap) {
                        ?>
                        <div class='recommendation alternating-bg'>
                            <div class="flex-container">
                                <div style="margin-right: 0.5em;">
                                    <a href="/mapset/<?php echo $beatmap['SetID']; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $beatmap['SetID']; ?>l.jpg" class="diffThumb" onerror="this.onerror=null; this.src='../charts/INF.png';" /></a>
                                </div>
                                <div>
                                    <a href="/mapset/<?php echo $beatmap['SetID']; ?>"><?php echo $beatmap['Artist']; ?> - <?php echo htmlspecialchars($beatmap['Title']); ?> <a href="https://osu.ppy.sh/b/<?php echo $beatmap['BeatmapID']; ?>" target="_blank" rel="noopener noreferrer"><i class="icon-external-link" style="font-size:10px;"></i></a><br></a>
                                    <a href="/mapset/<?php echo $beatmap['SetID']; ?>"><b><?php echo htmlspecialchars($beatmap['DifficultyName']); ?></b></a> <span class="subText"><?php echo number_format((float)$beatmap['SR'], 2, '.', ''); ?>*</span><br>
                                    <?php echo date("M jS, Y", strtotime($beatmap['DateRanked']));?><br>
                                    <a href="/profile/<?php echo $beatmap['CreatorID']; ?>"><?php echo GetUserNameFromId($beatmap['CreatorID'], $conn); ?></a> <a href="https://osu.ppy.sh/u/<?php echo $beatmap['CreatorID']; ?>" target="_blank" rel="noopener noreferrer"><i class="icon-external-link" style="font-size:10px;"></i></a><br>
                                    Recommendation Score: <?php echo $beatmap['Score']; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
            ?>
        </div>
        <div id="scrollable-element"></div>
    </div>

    <script>
        let displayedMaps = [];

        function isMapDisplayed(mapId) {
            return displayedMaps.includes(mapId);
        }

        function addMapToElement(score) {
            let map = score.beatmap;
            let set = score.beatmapset;

            if (isMapDisplayed(map.id))
                return;

            if (map.status == "graveyard" || map.status == "pending" || map.status == "qualified")
                return;

            const mapElement = $("<div>").attr("id", map.id).addClass("flex-container map");
            const imageElement = $("<div>").addClass("flex-child");
            const imgSrc = "https://b.ppy.sh/thumb/" + set.id + "l.jpg"; // Replace with the actual image source
            const imgElement = $("<img>").attr("src", imgSrc).addClass("diffThumb").css({ height: "42px", width: "42px" }).on("error", function () {
                this.onerror = null;
                this.src = "/charts/INF.png";
            });

            const imageLinkElement = $("<a>").attr("href", "../mapset/" + set.id);
            imageLinkElement.append(imgElement);

            imageElement.append(imageLinkElement);

            const versionElement = $("<div>").addClass("flex-child").text(map.version);
            versionElement.addClass("difficulty-name");

            const versionLinkElement = $("<a>").attr("href", "../mapset/" + set.id);
            versionLinkElement.append(versionElement);

            const identifierElement = $("<div>").addClass("flex-child");
            const spanElement = $("<span>").addClass("identifier").css("display", "inline-block");
            const olElement = $("<ol>").addClass("star-rating-list");
            olElement.attr("beatmapid", map.id);
            olElement.attr("rating", score.rating);

            let userMapRating = score.rating;
            for (let i = 1; i <= 5; i++) {
                const liElement = $("<li>").addClass("star").addClass(function () {
                    if (userMapRating == i - 0.5) {
                        return "icon-star-half-empty";
                    } else if (userMapRating < i) {
                        return "icon-star-empty";
                    } else {
                        return "icon-star";
                    }
                }).attr("value", i);
                olElement.append(liElement);
            }


            spanElement.append(olElement);

            const removeButton = $("<span>").addClass("starRemoveButton");
            if (true)
                removeButton.addClass("disabled");

            removeButton.attr("beatmapid", map.beatmapid);
            const removeIcon = $("<i>").addClass("icon-remove");
            removeButton.append(removeIcon);

            const starValue = $("<span>").addClass("star-value");

            if (userMapRating != "-1") {
                starValue.text(userMapRating);
            } else {
                starValue.html('&ZeroWidthSpace;');
                starValue.addClass("unrated");
            }

            identifierElement.append(spanElement);
            identifierElement.append(removeButton);
            identifierElement.append(starValue);

            mapElement.append(imageElement, versionLinkElement, identifierElement);

            $("#scrollable-element").prepend(mapElement);

            displayedMaps.unshift(map.id);
        }

        function fetchRecentScores() {
            $.ajax({
                url: 'FetchScores.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    const scores = response;
                    for (const score of scores) {
                        addMapToElement(score);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching recent scores:", error);
                }
            });
        }

        $(document).on("mousemove", ".star-rating-list", function(event) {
            var $this = $(this);
            var sel = event.target.value;
            var $options = $this.find(".star");
            var rating = 0;

            for (var i = 0; i < 5; i++) {
                if (i < sel) {
                    if (event.pageX - event.target.getBoundingClientRect().left<= 6 && sel-1 == i) {
                        $options.eq(i).attr('class', 'star icon-star-half-empty');
                        rating += 0.5;
                    } else {
                        $options.eq(i).attr('class', 'star icon-star');
                        rating += 1;
                    }
                } else {
                    $options.eq(i).attr('class', 'star icon-star-empty');
                }
            }
            $this.parent().parent().find('.star-value').html(rating.toFixed(1));
        });

        $(document).on("mouseleave", ".star-rating-list", function(event) {
            var $this = $(this);
            var sel = $this.attr("rating");
            var $options = $this.find(".star");

            for (var i = 0; i < 5; i++) {
                if (i < sel) {
                    if (sel-0.5 == i) {
                        $options.eq(i).attr('class', 'star icon-star-half-empty');
                    } else {
                        $options.eq(i).attr('class', 'star icon-star');
                    }
                } else {
                    $options.eq(i).attr('class', 'star icon-star-empty');
                }
            }

            if (sel == -1){
                $this.parent().parent().find('.star-value').html("&ZeroWidthSpace;");
            }else{
                $this.parent().parent().find('.star-value').html(sel);
            }
        });

        $(document).on("click", ".starRemoveButton", function(event) {
            var $this = $(this);
            var bID = $(this).attr("beatmapid");

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    console.log(this.responseText);

                    $this.addClass("disabled");
                    $this.parent().find('.star-value').html("&ZeroWidthSpace;");
                    $this.parent().find('.star-value').addClass("unrated");
                    $this.parent().find('.identifier').find('.star-rating-list').addClass("unrated");
                }
            };

            $this.attr("rating", "");
            xhttp.open("POST", "SubmitRating.php", true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send("bID=" + bID + "&rating=" + -2);
            $this.parent().find('.star-value').html("removing...");

        });

        $(document).on("click", ".star-rating-list", function(event) {
            var $this = $(this);
            var bID = $(this).attr("beatmapid");
            var sel = event.target.value;
            var rating = 0;

            for (var i = 0; i < 5; i++) {
                if (i < sel) {
                    if (event.pageX - event.target.getBoundingClientRect().left <= 6 && sel-1 == i) {
                        rating += 0.5;
                    } else {
                        rating += 1;
                    }
                }
            }

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    console.log(this.responseText);

                    $this.removeClass("unrated");
                    $this.parent().parent().find('.star-value').removeClass("unrated");
                    $this.parent().parent().find('.star-value').html(rating.toFixed(1));
                    $this.parent().parent().find('.starRemoveButton').removeClass("disabled");
                }
            };

            $this.attr("rating", rating.toFixed(1));
            xhttp.open("POST", "../mapset/SubmitRating.php", true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send("bID=" + bID + "&rating=" + rating);
            $this.parent().parent().find('.star-value').html("rating...");

        });

        setInterval(fetchRecentScores, 1000);
    </script>

<?php
include '../footer.php';
?>