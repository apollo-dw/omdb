<?php
    $PageTitle = "Ratings";
    require "../../base.php";
    require '../../header.php';

    $profileId = GetIntParam('id', null, "Invalid page bro");

    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($profile == NULL)
        die("Can't view this bros friends cuz they aint an OMDB user");
?>

<center><h1><a href="/profile/<?php echo safe_htmlspecialchars($profileId, ENT_QUOTES, 'UTF-8'); ?>"><?php echo safe_htmlspecialchars(GetUserNameFromId($profileId, $conn), ENT_QUOTES); ?></a>'s ratings</h1></center>

<hr>

<?php
    $filterConfig = [
        'sortOptions' => [
            '1' => 'Latest',
            '2' => 'Oldest',
            '3' => 'Highest rated',
            '4' => 'Lowest rated'
        ],
        'showRating' => true,
        'showTag' => true,
        'categories' => ['status', 'descriptor', 'genre', 'language', 'country'] 
    ];
    require "../../functions/filter/index.php";
?><br>

<?php include 'RatingsListing.php'; ?>

<script>
    function debounce(func, delay) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
    }

    function buildRatingsParams(page) {
        var payload = window.getOmdbFilterPayload();
        var params  = new URLSearchParams();
        params.set('id', <?php echo $profileId; ?>);
        params.set('p',  page);
        if (payload.rating)
            params.set('r', payload.rating);
        if (payload.order)
            params.set('o', payload.order);
        if (payload.tag)
            params.set('t', payload.tag);
        if (payload.year)
            params.set('y', payload.year);
        if (payload.tokens && payload.tokens.length > 0)
            params.set('tokens', JSON.stringify(payload.tokens));
        return params;
    }
    
    function loadRatings(page) {
        var params = buildRatingsParams(page);
        history.replaceState(null, '', '?' + params.toString());
    
        var $list = $('#ratings-list');
        $list.css('opacity', 0.5);
    
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (this.readyState !== 4 || this.status !== 200)
                return;
            var parser = new DOMParser();
            var doc = parser.parseFromString(this.responseText, 'text/html');
            var newDiv = doc.getElementById('ratings-list');
            if (newDiv)
                $list.replaceWith(newDiv);
            else
                location.reload();
            $('#ratings-list').css('opacity', 1);
        };
    
        xhr.open('POST', 'RatingsListing.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(params.toString());
    }

    function changePage(page) {
        if (page < 1)
            page = 1;
        loadRatings(page);
    }
    
    const debouncedLoadRatings = debounce(function() {
        loadRatings(1);
    }, 100);
    
    $(document).on('omdbFiltersSubmitted', function() {
        debouncedLoadRatings();
    });
</script>

<?php
	require '../../footer.php';
?>