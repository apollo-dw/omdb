<?php
    $PageTitle = "Labs | User Map";

    require "../base.php";

    // Provides the nodes and links
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        $body = json_decode(file_get_contents('php://input'), true);
        $minRatings = isset($body['minRatings']) ? (int)$body['minRatings'] : 10;
        if ($minRatings < 1) $minRatings = 1;

        $stmt = $conn->prepare("SELECT u.UserID, u.Username, COUNT(r.RatingID) AS RatingCount
            FROM users u
            INNER JOIN ratings r ON r.UserID = u.UserID
            WHERE (u.HideRatings = 0 OR u.HideRatings IS NULL)
              AND (u.banned = 0 OR u.banned IS NULL)
              AND u.Username IS NOT NULL AND u.Username != ''
            GROUP BY u.UserID, u.Username
            HAVING RatingCount >= ?
            ORDER BY RatingCount DESC
        ");
        $stmt->bind_param("i", $minRatings);
        $stmt->execute();
        $result = $stmt->get_result();

        $nodes = [];
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $nodes[] = [
                "id" => (int)$row["UserID"],
                "name" => $row["Username"],
                "ratings" => (int)$row["RatingCount"],
            ];
            $ids[] = (int)$row["UserID"];
        }
        $stmt->close();

        $links = [];
        if (!empty($ids)) {
            $idList = implode(',', $ids);

            $corrResult = $conn->query("WITH UserRatingCounts AS (
                    SELECT u.UserID, COUNT(r.RatingID) AS RatingCount
                    FROM users u
                    INNER JOIN ratings r ON r.UserID = u.UserID
                    WHERE (u.HideRatings = 0 OR u.HideRatings IS NULL)
                      AND (u.banned = 0 OR u.banned IS NULL)
                      AND u.Username IS NOT NULL AND u.Username != ''
                    GROUP BY u.UserID
                ),
                ScoredCorrelations AS (
                    SELECT uc.user1_id, uc.user2_id,
                        uc.correlation * LEAST(1, LEAST(ur1.RatingCount, ur2.RatingCount) / 100) AS adjusted_correlation
                    FROM user_correlations uc
                    INNER JOIN UserRatingCounts ur1 ON ur1.UserID = uc.user1_id
                    INNER JOIN UserRatingCounts ur2 ON ur2.UserID = uc.user2_id
                    WHERE uc.user1_id IN ($idList)
                      AND uc.user2_id IN ($idList)
                      AND uc.correlation >= 0.7
                ),
                RankedCorrelations AS (
                    SELECT user1_id, user2_id, adjusted_correlation,
                        ROW_NUMBER() OVER (PARTITION BY user1_id ORDER BY adjusted_correlation DESC) AS rank_num
                    FROM ScoredCorrelations
                )
                SELECT user1_id, user2_id, adjusted_correlation AS correlation
                FROM RankedCorrelations
                WHERE rank_num <= 8
            ");

            if ($corrResult) {
                while ($row = $corrResult->fetch_assoc()) {
                    $links[] = [
                        "source" => (int)$row["user1_id"],
                        "target" => (int)$row["user2_id"],
                        "value" => (float)$row["correlation"],
                    ];
                }
            }
        }

        echo json_encode([
            "nodes" => $nodes,
            "links" => $links,
            "minRatings" => $minRatings,
        ]);
        exit;
    }

    require '../header.php';
?>

<h1>User map</h1>
<span class="subText">
    Every dot is a user based on correlation data (and people with less ratings are deweighted)
    <br>
    Hover a user to see their strongest connections, click to go to their profile
</span>

<div class="flex-row-container" style="align-items:center; gap:0.75em; margin:1em 0;">
    <label for="minRatings">Minimum ratings to appear on the map:</label>
    <input type="number" id="minRatings" value="10" min="1" max="1000" style="width:5em;">
    <button onclick="loadUserMap()">reload</button>
    <span id="mapStatus" class="subText"></span>
</div>
<div style="margin:0 0 1em 0;">
    <label for="usernameHighlight" style="display:block; margin-bottom:0.35em;">Username to highlight on the map:</label>
    <input type="text" id="usernameHighlight" placeholder="Enter a username" style="width:min(24em, 100%);">
</div>

<div id="mapContainer" style="position:relative; width:100%; aspect-ratio:16/10; min-height:320px; background:#0c1515; border-radius:4px; overflow:hidden;">
    <svg id="usermap" width="100%" height="100%" style="display:block;"></svg>
    <div id="usermapTooltip" style="position:absolute; pointer-events:none; opacity:0; transition:opacity 0.1s; background:#182828; border:1px solid #395f5f; border-radius:4px; padding:0.4em 0.6em; font-size:0.85em; white-space:nowrap; z-index:5;"></div>
</div>
<span class="subText">Scroll/pinch to zoom, drag the background to pan</span>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
<script>
    let usermapSimulation = null;
    let adjacency = null;
    let nodes = null;
    let links = null;

    let nodeSel = null;
    let linkSel = null;

    function highlight(d) {
        const connected = adjacency.get(d.id) || new Set();
        nodeSel
            .attr('fill', n => (n.id === d.id || connected.has(n.id)) ? '#ff8fb1' : '#6fffea')
            .attr('fill-opacity', n => (n.id === d.id || connected.has(n.id)) ? 1 : 0.2);
        linkSel
            .attr('stroke-opacity', l => (l.source.id === d.id || l.target.id === d.id) ? Math.min(1, 0.3 + l.value) : 0);
    }

    function clearHighlight(normalizedUsernameHighlight) {
        nodeSel.attr('fill', '#6fffea').attr('fill-opacity', 0.8);
        linkSel.attr('stroke-opacity', 0);
        if (normalizedUsernameHighlight) {
            highlightUsername(normalizedUsernameHighlight);
        }
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[c]);
    }

    function loadUserMap() {
        let minRatings = parseInt(document.getElementById('minRatings').value, 10);
        if (!minRatings || minRatings < 1) minRatings = 10;

        const status = document.getElementById('mapStatus');
        status.textContent = 'loading...';

        fetch('/labs/usermap.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ minRatings: minRatings })
        })
            .then(r => r.json())
            .then(data => {
                status.textContent = data.nodes.length + ' users, ' + data.links.length + ' connections';
                renderUserMap(data);
            })
            .catch(() => {
                status.textContent = 'failed to load the map :(';
            });
    }

    function renderUserMap(data) {
        if (usermapSimulation) usermapSimulation.stop();

        const container = document.getElementById('mapContainer');
        const width = container.clientWidth;
        const height = container.clientHeight;

        const svg = d3.select('#usermap').attr('viewBox', `0 0 ${width} ${height}`);
        svg.selectAll('*').remove();

        const tooltip = d3.select('#usermapTooltip');

        const zoomLayer = svg.append('g');
        const linkLayer = zoomLayer.append('g');
        const nodeLayer = zoomLayer.append('g');

        svg.call(d3.zoom().scaleExtent([0.15, 8]).on('zoom', (event) => {
            zoomLayer.attr('transform', event.transform);
        }));

        nodes = data.nodes.map(d => ({ ...d }));
        links = data.links.map(d => ({ ...d }));
        const normalizedUsernameHighlight = String(document.getElementById('usernameHighlight').value || '').trim().toLowerCase();

        adjacency = new Map();
        nodes.forEach(n => adjacency.set(n.id, new Set()));
        links.forEach(l => {
            if (adjacency.has(l.source)) adjacency.get(l.source).add(l.target);
            if (adjacency.has(l.target)) adjacency.get(l.target).add(l.source);
        });

        const isConnected = d => adjacency.get(d.id).size > 0;

        const nodeSpacing = 4; 

        const maxRatings = Math.max(...nodes.map(d => typeof d === 'object' && d.ratings !== undefined ? d.ratings : 10));
        function getRadius(d) {
            const ratings = typeof d === 'object' && d.ratings !== undefined ? d.ratings : 10;
            return Math.min(10, 10 * Math.pow(ratings / maxRatings, 1 / 3));
        }

        usermapSimulation = d3.forceSimulation(nodes)
            .force('linkPos', d3.forceLink(links.filter(d => d.value >= 0)).id(d => d.id)
                .distance(d => {
                    const base = getRadius(d.source) + getRadius(d.target) + nodeSpacing;
                    return base * (1 + (1 - d.value));
                })
                .strength(d => Math.pow(d.value, 2) * 2)
            )
            .force('charge', d3.forceManyBody().strength(d => {
                const degree = adjacency.get(d.id)?.size || 0;
                return -40 - degree * 15;
            }))
            .force('collide', d3.forceCollide().radius(d => getRadius(d) + (nodeSpacing / 2)).iterations(3))
            .force('center', d3.forceCenter(width / 2, height / 2))
            .force('x', d3.forceX(width * 0.9).strength(d => isConnected(d) ? 0 : 0.15))
            .force('y', d3.forceY(height * 0.8).strength(d => isConnected(d) ? 0 : 0.15))
            .stop();

        const status = document.getElementById('mapStatus');
        status.textContent = 'calculating layout...';

        for (let i = 0; i < 600; ++i) {
            usermapSimulation.tick();
        }

        status.textContent = data.nodes.length + ' users, ' + data.links.length + ' connections';

        linkSel = linkLayer.selectAll('line').data(links).join('line')
            .attr('stroke', '#6fffea')
            .attr('stroke-width', d => d.value * 1.5)
            .attr('stroke-opacity', 0) 
            .attr('x1', d => d.source.x)
            .attr('y1', d => d.source.y)
            .attr('x2', d => d.target.x)
            .attr('y2', d => d.target.y);

        nodeSel = nodeLayer.selectAll('circle').data(nodes).join('circle')
            .attr('r', d => getRadius(d)) 
            .attr('fill', '#6fffea')
            .attr('fill-opacity', 0.8)
            .style('cursor', 'pointer')
            .attr('cx', d => d.x)
            .attr('cy', d => d.y)
            .on('mouseenter', (event, d) => {
                highlight(d);
                showTooltip(event, d);
            })
            .on('mousemove', (event, d) => showTooltip(event, d))
            .on('mouseleave', () => {
                clearHighlight();
                tooltip.style('opacity', 0);
            })
            .on('click', (event, d) => {
                window.open('/profile/' + d.id, '_blank');
            });

        clearHighlight(normalizedUsernameHighlight);

        function showTooltip(event, d) {
            const [x, y] = d3.pointer(event, container);
            tooltip.style('opacity', 1)
                .style('left', (x + 15) + 'px')
                .style('top', (y + 15) + 'px')
                .html(
                    '<strong style="color:#6fffea;">' + escapeHtml(d.name) + '</strong><br>' +
                    '<span class="subText">' + d.ratings + ' ratings</span>'
                );
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('usernameHighlight').addEventListener('input', () => {
            nodeSel.attr('fill', '#6fffea').attr('fill-opacity', 0.8);
            linkSel.attr('stroke-opacity', 0);
            const normalizedName = String(document.getElementById('usernameHighlight').value || '').trim().toLowerCase();
            const match = nodes.find(n => String(n.name || '').trim().toLowerCase() === normalizedName);
            if (match) {
                highlight(match);
            }
        });
        loadUserMap();
    });
</script>

<?php
require '../footer.php';
?>