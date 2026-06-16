<?php
    $PageTitle = "Labs | Recommendations";

    require "../base.php";

    // For the POST reqs this page does
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || !isset($body['setId'])) {
            echo 'Alright man';
            exit;
        }

        $setId = (int)$body['setId'];
        $overrides = [];

        if (!empty($body['weights']) && is_array($body['weights']))
            $overrides['weights'] = array_map('floatval', $body['weights']);
        if (!empty($body['settings']) && is_array($body['settings']))
            $overrides['settings'] = array_map('floatval', $body['settings']);

        $seed = null;
        $similarMaps = GetSimilarBeatmaps($conn, $setId, 8, $seed, null, $overrides, false);

        if (empty($similarMaps)) {
            echo '<span class="subText">No similar maps, try other weights + settings or something</span>';
            exit;
        }

        echo '<div class="flex-container">';
        RenderSimilarMapCards($conn, $similarMaps);
        echo '</div>';
        exit;
    }

    $weights = [
        "avgScore" => 5, // weighted avg rating from users who like the diff
        "descriptorScore" => 1, // Overall multiplier for the descriptor scores provided in ../descriptors.json
        "monthProximity" => 6, // when ranked within settings.yearWindow years of the seed
        "sharedNominator" => 1, // per nominator shared with the set
        "sharedMapper" => 4, // per mapper shared with the diff
        "cohortLift" => 8, // how much higher the fans rate the diff vs everyone else
        "cohortCoverage" => 16, // share of the fans vs everyone so big fanbases of the diff get no bump in this
        "correlation" => 0, // how the similar users rated both diffs generally (PEARSON CORRELATION COEFF)
        "srProximity" => 1, // how close the diffs are in star rating
    ];

    $settings = [
        "likedThreshold" => 3.5, // ratings at/above this count as "positive"
        "proximityMonths" => 24,  // abs(diff rank date - TARGET) <= window
        "bayesMean" => 3.0, // site-wide mean for bayesian avg
        "bayesN" => 2, // n for bayesian avg
        "minAvgScore" => 3, // similar diffs need at least this bayesian avg rating
        "minScoreShare" => 0.06, // min fraction of fans
        "maxScoreShare" => 0.5, // max fraction of fans
        "maxScoreFloor" => 80, // avoid overfiltering cuz of the share settings
        "liftShrink" => 10, // n = u need 50% of the cohortLift value
        "coverageFade" => 80, // diminish the effect of cohort if the fanbase is n large
        "coverageCurve" => 2, // exponent setting so 90% fans is more than twice vs 45% fans
        "corrShrink" => 10, // similar to bayes avg, correlations are shrunk by n/(n+this)
        "minCoRaters" => 5, // diffs need at least this many users who rated BOTH maps
        "minCorrelation" => 0, // ignore candidates correlated below this (0 = anything negatively),
        "srWindow" => 0.5, // SR diff via fraction, so 0.5 = 50% of the diff's SR as the limit
    ];

    $weightDescriptions = [
        "avgScore" => "weighted avg rating from users who like the diff",
        "descriptorScore" => "Overall multiplier for the descriptor scores provided in ../assets/descriptors.json",
        "monthProximity" => "when ranked within settings.proximityMonths months of the seed",
        "sharedNominator" => "per nominator shared with the set",
        "sharedMapper" => "per mapper shared with the diff",
        "cohortLift" => "how much higher the fans rate the diff vs everyone else",
        "cohortCoverage" => "share of the fans vs everyone so big fanbases of the diff get no bump in this",
        "correlation" => "pearson correlation of how similar users rated both diffs",
        "srProximity" => "how close the diffs are in star rating",
    ];

    $settingDescriptions = [
        "likedThreshold" => "ratings at/above this count as \"positive\"",
        "proximityMonths" => "abs(diff rank date - TARGET) <= window",
        "bayesMean" => "site-wide mean for bayesian avg which is 3 currently, change at ur own risk",
        "bayesN" => "n for bayesian avg",
        "minAvgScore" => "similar diffs need at least this bayesian avg rating",
        "minScoreShare" => "min fraction of fans",
        "maxScoreShare" => "max fraction of fans",
        "maxScoreFloor" => "avoid overfiltering cuz of the share settings",
        "liftShrink" => "n = u need 50% of the cohortLift value",
        "coverageFade" => "diminish the effect of cohort if the fanbase is n large",
        "coverageCurve" => "exponent setting so 90% fans is more than twice vs 45% fans",
        "corrShrink" => "similar to bayes avg, correlations are shrunk by n/(n+this)",
        "minCoRaters" => "diffs need at least this many users who rated BOTH maps",
        "minCorrelation" => "ignore candidates correlated below this (0 = anything negatively)",
        "srWindow" => "SR diff via fraction, so 0.5 = 50% of the diff's SR as the limit",
    ];
?>

<style>
    .formula-container { 
        padding: 15px;
        font-family: monospace; 
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 20px;
    }
    .math-line {
        color: #ddd;
    }
    .math-line strong {
        color: #fff;
    }
    .var-w, .var-s, .var-d {
        cursor: help;

    }
    .var-w {
        color: #ff9d00;
        font-weight: bold;
        border-bottom: 1px dotted #ff9d00;
    }
    .var-s {
        color: #00c3ff;
        font-weight: bold;
        border-bottom: 1px dotted #00c3ff;
    }
    .var-d {
        color: #888;
        font-style: italic;
        border-bottom: 1px dotted #888;
    }
</style>


<?php
    require '../header.php';
?>
<h1>Recommendations</h1>
<span class="subText">Play around with the weights and settings to check out different results</span>
<hr>

<div>
    <h4>Map</h4>
    <div style="margin-bottom: 1em;">
        <input type="text" id="mapSearch" placeholder="Search for a map..." autocomplete="off">
        <div id="mapSearchResults"></div>
        <div id="selectedMap">
            Selected: <b id="selectedMapName"></b>
            <input type="hidden" id="selectedSetId">
        </div>
    </div>
    <div class="flex-container" style="background-color: DarkSlateGrey;">
        <div class="flex-child" style="flex:1;color: #ff9d00;">
            <h4 style="margin-top: 0;">Weights</h4>
            <div>
                <?php foreach ($weights as $key => $default): ?>
                <div class="flex-container" style="flex-direction:column;margin:1em;">
                    <label>
                        <b><?php echo htmlspecialchars($key, ENT_QUOTES); ?></b>
                        <span class="subText"> = </span>
                        <input type="number" id="w_<?php echo $key; ?>" min="0" max="20" step="0.5" value="<?php echo $default; ?>">
                    </label>
                    <span class="subText"><?php echo htmlspecialchars($weightDescriptions[$key], ENT_QUOTES); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>


        <div class="flex-child" style="flex:1;color: #00c3ff;">
            <h4 style="margin-top: 0;">Settings</h4>
            <div>
                <?php
                $settingMeta = [
                    "likedThreshold"  => ["min" => 0,    "max" => 5,   "step" => 0.5],
                    "proximityMonths" => ["min" => 1,    "max" => 120, "step" => 1],
                    "bayesMean"       => ["min" => 0,    "max" => 5,   "step" => 0.1],
                    "bayesN"          => ["min" => 0,    "max" => 20,  "step" => 1],
                    "minAvgScore"     => ["min" => 0,    "max" => 5,   "step" => 0.5],
                    "minScoreShare"   => ["min" => 0,    "max" => 1,   "step" => 0.01],
                    "maxScoreShare"   => ["min" => 0,    "max" => 1,   "step" => 0.01],
                    "maxScoreFloor"   => ["min" => 0,    "max" => 500, "step" => 5],
                    "liftShrink"      => ["min" => 0,    "max" => 50,  "step" => 1],
                    "coverageFade"    => ["min" => 1,    "max" => 500, "step" => 5],
                    "coverageCurve"   => ["min" => 0.5,  "max" => 5,   "step" => 0.5],
                    "corrShrink"      => ["min" => 0,    "max" => 50,  "step" => 1],
                    "minCoRaters"     => ["min" => 1,    "max" => 50,  "step" => 1],
                    "minCorrelation"  => ["min" => -1,   "max" => 1,   "step" => 0.05],
                    "srWindow"        => ["min" => 0.05, "max" => 3,   "step" => 0.05],
                ];
                foreach ($settings as $key => $default):
                    $meta = $settingMeta[$key];
                ?>
                <div class="flex-container" style="flex-direction:column;margin:1em;">
                    <label>
                        <b><?php echo htmlspecialchars($key, ENT_QUOTES); ?></b>
                        <span class="subText"> = </span>
                        <input type="number" id="s_<?php echo $key; ?>"
                            min="<?php echo $meta['min']; ?>"
                            max="<?php echo $meta['max']; ?>"
                            step="<?php echo $meta['step']; ?>"
                            value="<?php echo $default; ?>">
                    </label>
                    <span class="subText"><?php echo htmlspecialchars($settingDescriptions[$key], ENT_QUOTES); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
    <div>
        <h4>Live Formulas</h4>
        <div class="formula-container">
            <div class="math-line">
                <strong>Pre-Filters (Candidates must pass all):</strong><br>
                1. B &ge; <span class="var-s f_s_minAvgScore" title="Setting: minAvgScore"></span><br>
                2. <span class="var-d" title="Data: ScoreCount (Fans who rated candidate)">n_c</span> &ge; max(2, <span class="var-s f_s_minScoreShare" title="Setting: minScoreShare"></span> &times; <span class="var-d" title="Data: Total Seed Fans">N</span>)<br>
                3. <span class="var-d" title="Data: ScoreCount (Fans who rated candidate)">n_c</span> &le; max(<span class="var-s f_s_maxScoreFloor" title="Setting: maxScoreFloor"></span>, <span class="var-s f_s_maxScoreShare" title="Setting: maxScoreShare"></span> &times; <span class="var-d" title="Data: Total Seed Fans">N</span>)<br>
                4. <i>FOR CORRELATION TO APPLY ONLY:</i> <span class="var-d" title="Data: CoRaters">n_co</span> &ge; <span class="var-s f_s_minCoRaters" title="Setting: minCoRaters"></span> AND R &ge; <span class="var-s f_s_minCorrelation" title="Setting: minCorrelation"></span><br>
            </div>
            <hr style="border-color: #333;">
            <div class="math-line">
                <strong>DB Variables:</strong><br>
                Seed: The map you are looking at right now and selected above<br>
                Candidate: The map that will potentially be suggested<br>
                A <strong>fan</strong> is considered someone who rates the seed diff >= <span class="var-s f_s_likedThreshold" title="Setting: likedThreshold"></span>
                <br><br>
                <span class="var-d" title="Data: Total Seed Fans">N</span>: # of seed fans<br>
                <span class="var-d" title="Data: ScoreCount (Fans who rated candidate)">n_c</span>: # of seed fans that rated the candidate map<br>
                <span class="var-d" title="Data: CoRaters">n_co</span>: # of seed raters that rated the candidate map (NOT JUST FANS). Called CoRaters for Correlation calc<br>
                <span class="var-d" title="Data: Avg Fan Score">FanAvg</span>: Average rating of the candidate map by seed fans<br>
                <span class="var-d" title="Data: Global WeightedAvg">GlobalAvg</span>: Average weighted rating of the candidate map. This is the rating you see on the charts page for a beatmap<br>
                <span class="var-d" title="Data: Seed Rank Month">SeedMo</span>: Ranked date for the seed map<br>
                <span class="var-d" title="Data: Cand Rank Month">CandMo</span>: Ranked date for the candidate map<br>
                <span class="var-d" title="Data: Seed SR">SeedSR</span>: Seed map's star rating<br>
                <span class="var-d" title="Data: Cand SR">CandSR</span>: Candidate map's star rating<br>
                <span class="var-d" title="Data: Shared Nominators">SharedNoms</span>: # of nominators that are the same between the seed and candidate map<br>
                <span class="var-d" title="Data: Shared Mappers">SharedMappers</span>: # of mappers that are the same between the seed and candidate map<br>
                <span class="var-d" title="Data: Descriptor Match Score">D</span>: A sum of the weights of matching descriptors, or 0 if a diametrically opposed descriptor is found in the candidate map
            </div>
            <hr style="border-color: #333;">
            <div class="math-line">
                <strong>Base Variables:</strong><br>
                B (Local Bayes) = ((<span class="var-d" title="Data: Avg Fan Score">FanAvg</span> &times; <span class="var-d" title="Data: ScoreCount">n_c</span>) + (<span class="var-s f_s_bayesMean" title="Setting: bayesMean"></span> &times; <span class="var-s f_s_bayesN" title="Setting: bayesN"></span>)) / (<span class="var-d" title="Data: ScoreCount">n_c</span> + <span class="var-s f_s_bayesN" title="Setting: bayesN"></span>)<br>
                L (Cohort Lift) = (<span class="var-d" title="Data: Avg Fan Score">FanAvg</span> - <span class="var-d" title="Data: Global WeightedAvg">GlobalAvg</span>) &times; (<span class="var-d" title="Data: ScoreCount">n_c</span> / (<span class="var-d" title="Data: ScoreCount">n_c</span> + <span class="var-s f_s_liftShrink" title="Setting: liftShrink"></span>))<br>
                C<sub>base</sub> (Coverage) = (<span class="var-d" title="Data: ScoreCount">n_c</span> / <span class="var-d" title="Data: Total Seed Fans">N</span>)<sup><span class="var-s f_s_coverageCurve" title="Setting: coverageCurve"></span></sup><br>
                W<sub>cov</sub> (Cov Weight) = max(0, 1 - (<span class="var-d" title="Data: Total Seed Fans">N</span> / <span class="var-s f_s_coverageFade" title="Setting: coverageFade"></span>))<br>
                T (Time Prox.) = max(0, 1 - (|<span class="var-d" title="Data: Seed Rank Month">SeedMo</span> - <span class="var-d" title="Data: Cand Rank Month">CandMo</span>| / <span class="var-s f_s_proximityMonths" title="Setting: proximityMonths"></span>))<br>
                Corr (Shrunk R) = <span class="var-d" title="Data: Pearson R">R</span> &times; (<span class="var-d" title="Data: CoRaters">n_co</span> / (<span class="var-d" title="Data: CoRaters">n_co</span> + <span class="var-s f_s_corrShrink" title="Setting: corrShrink"></span>))<br>
                P (SR Prox.) = max(0, 1 - (|<span class="var-d" title="Data: Seed SR">SeedSR</span> - <span class="var-d" title="Data: Cand SR">CandSR</span>| / (<span class="var-d" title="Data: Seed SR">SeedSR</span> &times; <span class="var-s f_s_srWindow" title="Setting: srWindow"></span>)))
            </div>
            <hr style="border-color: #333;">
            <div class="math-line">
                <strong>Total Recommendation Score (S<sub>total</sub>):</strong><br>
                S<sub>total</sub> = 
                (B &times; <span class="var-w f_w_avgScore" title="Weight: avgScore"></span>) + 
                (L &times; <span class="var-w f_w_cohortLift" title="Weight: cohortLift"></span>) + 
                (<span class="var-w f_w_cohortCoverage" title="Weight: cohortCoverage"></span> &times; C<sub>base</sub> &times; W<sub>cov</sub>) + <br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(<span class="var-d" title="Data: Descriptor Match Score">D</span> &times; <span class="var-w f_w_descriptorScore" title="Weight: descriptorScore"></span>) + 
                (T &times; <span class="var-w f_w_monthProximity" title="Weight: monthProximity"></span>) + 
                (<span class="var-d" title="Data: Shared Nominators">SharedNoms</span> &times; <span class="var-w f_w_sharedNominator" title="Weight: sharedNominator"></span>) + <br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(<span class="var-d" title="Data: Shared Mappers">SharedMappers</span> &times; <span class="var-w f_w_sharedMapper" title="Weight: sharedMapper"></span>) + 
                (Corr &times; <span class="var-w f_w_correlation" title="Weight: correlation"></span>) + 
                (P &times; <span class="var-w f_w_srProximity" title="Weight: srProximity"></span>)
            </div>
        </div>
    <div>
        <button onclick="getRecommendations()">Get Recommendations</button>
        <button onclick="resetDefaults()">Reset to Defaults</button>
        <h4>Results</h4>
        <div id="resultsContainer" style="background-color: DarkSlateGrey;">
            <span class="subText" style="padding:0.5em;">Pick a map and hit Get Recommendations</span>
        </div>
    </div>
</div>

<script>
    const weightDefaults = <?php echo json_encode($weights); ?>;
    const settingDefaults = <?php echo json_encode($settings); ?>;

    let searchTimeout = null;
    let selectedSetId = null;

    document.getElementById('mapSearch').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) {
            document.getElementById('mapSearchResults').innerHTML = '';
            return;
        }
        searchTimeout = setTimeout(() => {
            fetch('/beatmapSearch.php?q=' + encodeURIComponent(q))
                .then(r => r.text())
                .then(html => {
                    // Parsing links from the html
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const links = doc.querySelectorAll('a[href^="/mapset/"]');
                    const container = document.getElementById('mapSearchResults');
                    container.innerHTML = '';
                    links.forEach(link => {
                        const div = document.createElement('div');
                        div.style.cssText = 'padding:0.25em;cursor:pointer;';
                        div.className = 'alternating-bg';
                        div.textContent = link.textContent;
                        const setId = link.getAttribute('href').replace('/mapset/', '');
                        div.addEventListener('click', () => {
                            selectedSetId = setId;
                            document.getElementById('selectedSetId').value = setId;
                            document.getElementById('selectedMapName').textContent = link.textContent;
                            document.getElementById('selectedMap').style.display = 'block';
                            document.getElementById('mapSearchResults').innerHTML = '';
                            document.getElementById('mapSearch').value = '';
                        });
                        container.appendChild(div);
                    });
                });
        }, 300);
    });

    function getRecommendations() {
        if (!selectedSetId) {
            alert('DUDE PICK A MAP FIRST');
            return;
        }

        const weights = {};
        <?php foreach ($weights as $key => $_): ?>
        weights[<?php echo json_encode($key); ?>] = parseFloat(document.getElementById('w_<?php echo $key; ?>').value);
        <?php endforeach; ?>

        const settings = {};
        <?php foreach ($settings as $key => $_): ?>
        settings[<?php echo json_encode($key); ?>] = parseFloat(document.getElementById('s_<?php echo $key; ?>').value);
        <?php endforeach; ?>

        const container = document.getElementById('resultsContainer');
        container.innerHTML = '<span class="subText">loading...</span>';

        const xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200)
                    container.innerHTML = this.responseText;
                else
                    container.innerHTML = '<span class="subText">something shit itself</span>';
            }
        };
        xhttp.open('POST', '/labs/recommendations.php', true);
        xhttp.setRequestHeader('Content-Type', 'application/json');
        xhttp.send(JSON.stringify({ setId: selectedSetId, weights, settings }));
    }

    function resetDefaults() {
        for (const [key, val] of Object.entries(weightDefaults)) {
            const el = document.getElementById('w_' + key);
            if (el) el.value = val;
        }
        for (const [key, val] of Object.entries(settingDefaults)) {
            const el = document.getElementById('s_' + key);
            if (el) el.value = val;
        }

        updateFormulaDisplay();
    }

    function updateFormulaDisplay() {
        const weightKeys = Object.keys(weightDefaults);
        const settingKeys = Object.keys(settingDefaults);

        weightKeys.forEach(w => {
            const inputEl = document.getElementById('w_' + w);
            if (inputEl) {
                const spans = document.querySelectorAll('.f_w_' + w);
                spans.forEach(s => s.textContent = inputEl.value);
            }
        });

        settingKeys.forEach(s => {
            const inputEl = document.getElementById('s_' + s);
            if (inputEl) {
                const spans = document.querySelectorAll('.f_s_' + s);
                spans.forEach(span => span.textContent = inputEl.value);
            }
        });
    }

    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', updateFormulaDisplay);
    });

    updateFormulaDisplay();
</script>

<?php
require '../footer.php';
?>