<?php
    $PageTitle = "Labs | User Map";

    require "../base.php";

    // Provides the nodes and links
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        $body = json_decode(file_get_contents('php://input'), true);
        $minRatings = isset($body['minRatings']) ? (int)$body['minRatings'] : 10;
        $minRatings = max(1, min(500, $minRatings));

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
        while ($row = $result->fetch_assoc()) {
            $nodes[] = [
                "id" => (int)$row["UserID"],
                "name" => $row["Username"],
                "ratings" => (int)$row["RatingCount"],
            ];
        }
        $stmt->close();

        $links = [];
        if (!empty($nodes)) {
            // pairs are stored user1_id < user2_id only and osu! IDs are monotonic with signup date, so PARTITION BY user1_id alone would always rank from the older account's perspective which is not wanted
            // So the symmetric part uses UNIO ALL with GROUP BY instead
            $stmt = $conn->prepare("WITH UserRatingCounts AS (
                    SELECT u.UserID, COUNT(r.RatingID) AS RatingCount
                    FROM users u
                    INNER JOIN ratings r ON r.UserID = u.UserID
                    WHERE (u.HideRatings = 0 OR u.HideRatings IS NULL)
                      AND (u.banned = 0 OR u.banned IS NULL)
                      AND u.Username IS NOT NULL AND u.Username != ''
                    GROUP BY u.UserID
                    HAVING RatingCount >= ?
                ),
                ScoredCorrelations AS (
                    SELECT uc.user1_id, uc.user2_id,
                           uc.correlation AS raw_correlation,
                           uc.`count`     AS shared_count,
                           uc.correlation * (uc.`count` / (uc.`count` + 25)) AS adjusted_correlation
                    FROM user_correlations uc
                    INNER JOIN UserRatingCounts ur1 ON ur1.UserID = uc.user1_id
                    INNER JOIN UserRatingCounts ur2 ON ur2.UserID = uc.user2_id
                    WHERE uc.correlation >= 0.7
                ),
                Symmetric AS (
                    SELECT user1_id AS a, user2_id AS b, raw_correlation, shared_count, adjusted_correlation
                    FROM ScoredCorrelations
                    UNION ALL
                    SELECT user2_id AS a, user1_id AS b, raw_correlation, shared_count, adjusted_correlation
                    FROM ScoredCorrelations
                ),
                Ranked AS (
                    SELECT a, b, raw_correlation, shared_count, adjusted_correlation,
                           ROW_NUMBER() OVER (PARTITION BY a ORDER BY adjusted_correlation DESC) AS rank_num
                    FROM Symmetric
                )
                SELECT LEAST(a, b) AS user1_id, GREATEST(a, b) AS user2_id,
                       MAX(adjusted_correlation) AS correlation,
                       MAX(raw_correlation)      AS raw_correlation,
                       MAX(shared_count)         AS shared_count
                FROM Ranked
                WHERE rank_num <= 8
                GROUP BY LEAST(a, b), GREATEST(a, b)
            ");
            $stmt->bind_param("i", $minRatings);
            $stmt->execute();
            $corrResult = $stmt->get_result();

            while ($row = $corrResult->fetch_assoc()) {
                $links[] = [
                    "source" => (int)$row["user1_id"],
                    "target" => (int)$row["user2_id"],
                    "value" => (float)$row["correlation"],
                    "raw" => (float)$row["raw_correlation"],
                    "count" => (int)$row["shared_count"],
                ];
            }
            $stmt->close();
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
    <br>
    Scroll/pinch to zoom, drag the background to pan
</span>

<div class="flex-row-container" style="align-items:center; gap:0.75em; margin:1em 0;">
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
<script>
    let usermapSimulation = null;
    let adjacency = null;
    let nodes = null;
    let links = null;
    let loneIds = new Set();

    let nodeSel = null;
    let linkSel = null;
    let highlightTimer = null;
    let searchMatch = null;

    const LINK_STRENGTH = 3;

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[c]);
    }

    function findSearchMatch() {
        const q = String(document.getElementById('usernameHighlight').value || '').trim().toLowerCase();
        if (!q || !nodes) return null;
        return nodes.find(n => String(n.name || '').trim().toLowerCase() === q) || null;
    }

    function renderTooltip(x, y, d) {
        const container = document.getElementById('mapContainer');
        const el = document.getElementById('usermapTooltip');
        const degree = adjacency.get(d.id)?.size || 0;

        el.innerHTML =
            '<strong style="color:#6fffea;">' + escapeHtml(d.name) + '</strong><br>' +
            '<span class="subText">' + d.ratings + ' ratings &middot; ' + degree + ' strong links</span>';
        el.style.opacity = 1;

        const pad = 4;
        el.style.left = Math.max(pad, Math.min(x + 15, container.clientWidth  - el.offsetWidth  - pad)) + 'px';
        el.style.top  = Math.max(pad, Math.min(y + 15, container.clientHeight - el.offsetHeight - pad)) + 'px';
    }

    function hideTooltip() {
        document.getElementById('usermapTooltip').style.opacity = 0;
    }

    function tooltipForNode(d) {
        if (d.x === undefined || d.y === undefined) return;
        const [sx, sy] = d3.zoomTransform(document.getElementById('usermap')).apply([d.x, d.y]);
        renderTooltip(sx, sy, d);
    }

    function highlight(d) {
        if (!nodeSel || !linkSel) return;
        const connected = adjacency.get(d.id) || new Set();
        nodeSel
            .attr('fill', n => (n.id === d.id || connected.has(n.id)) ? '#ff8fb1' : '#6fffea')
            .attr('fill-opacity', n => (n.id === d.id || connected.has(n.id)) ? 1 : 0.2);
        linkSel
            .attr('stroke-opacity', l => (l.source.id === d.id || l.target.id === d.id) ? Math.min(1, 0.25 + l.raw) : 0);
    }

    function clearHighlight() {
        if (!nodeSel || !linkSel)
            return;

        nodeSel.attr('fill', '#6fffea').attr('fill-opacity', n => loneIds.has(n.id) ? 0.35 : 0.8);
        linkSel.attr('stroke-opacity', 0);

        searchMatch = findSearchMatch();
        if (searchMatch) {
            highlight(searchMatch);
            tooltipForNode(searchMatch);
        } else {
            hideTooltip();
        }
    }

    function loadUserMap() {
        let minRatings = 10;

        const status = document.getElementById('mapStatus');
        status.textContent = 'loading...';

        fetch('/labs/usermap.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ minRatings: minRatings })
        })
            .then(r => r.json())
            .then(data => renderUserMap(data))
            .catch(() => {
                status.textContent = 'failed to load the map :(';
            });
    }

    function renderUserMap(data) {
        if (usermapSimulation) usermapSimulation.stop();

        const container = document.getElementById('mapContainer');
        const width = container.clientWidth;
        const height = container.clientHeight;
        const status = document.getElementById('mapStatus');

        const svg = d3.select('#usermap').attr('viewBox', `0 0 ${width} ${height}`);
        svg.selectAll('*').remove();

        const zoomLayer = svg.append('g');
        const linkLayer = zoomLayer.append('g');
        const nodeLayer = zoomLayer.append('g');

        const zoom = d3.zoom().scaleExtent([0.15, 8]).on('zoom', (event) => {
            zoomLayer.attr('transform', event.transform);
            if (searchMatch) tooltipForNode(searchMatch);
        });
        svg.call(zoom);

        nodes = data.nodes.map(d => ({ ...d }));
        links = data.links.map(d => ({ ...d }));

        adjacency = new Map();
        nodes.forEach(n => adjacency.set(n.id, new Set()));
        links.forEach(l => {
            if (adjacency.has(l.source)) adjacency.get(l.source).add(l.target);
            if (adjacency.has(l.target)) adjacency.get(l.target).add(l.source);
        });

        const isConnected = d => (adjacency.get(d.id)?.size || 0) > 0;
        const connectedNodes = nodes.filter(isConnected);
        const loneNodes = nodes.filter(d => !isConnected(d));
        loneIds = new Set(loneNodes.map(d => d.id));

        const nodeSpacing = 4;
        const maxRatings = nodes.reduce((m, d) => Math.max(m, d.ratings || 0), 1);

        function getRadius(d) {
            const ratings = (d && d.ratings !== undefined) ? d.ratings : 10;
            return Math.min(10, 10 * Math.pow(ratings / maxRatings, 1 / 3));
        }

        usermapSimulation = d3.forceSimulation(connectedNodes)
            .force('linkPos', d3.forceLink(links).id(d => d.id)
                .distance(d => {
                    const base = getRadius(d.source) + getRadius(d.target) + nodeSpacing;
                    return base * (1 + (1 - d.value));
                })
                .strength(d => Math.pow(d.value, 2) * LINK_STRENGTH)
            )
            .force('charge', d3.forceManyBody().strength(d => {
                const degree = adjacency.get(d.id)?.size || 0;
                return -40 - degree * 15;
            }))
            .force('collide', d3.forceCollide().radius(d => getRadius(d) + (nodeSpacing / 2)).iterations(3))
            .force('center', d3.forceCenter(width / 2, height / 2))
            .stop();

        status.textContent = 'calculating layout...';

        // apparently double requestframe is needed to actually paint the status above before thread blocking
        requestAnimationFrame(() => requestAnimationFrame(() => {
            for (let i = 0; i < 600; ++i) usermapSimulation.tick();
            parkLoneNodes();
            fitToView();
            draw();
        }));

        function parkLoneNodes() {
            if (!loneNodes.length) return;
            const main = bbox(connectedNodes) || { minX: width / 2, maxX: width / 2, minY: height / 2, maxY: height / 2 };

            const area = loneNodes.reduce((s, d) => {
                const r = getRadius(d) + 1;
                return s + Math.PI * r * r;
            }, 0);
            const clumpR = Math.sqrt(area / (Math.PI * 0.7));

            const cx = main.maxX + 40 + clumpR;
            const cy = (main.minY + main.maxY) / 2;

            loneNodes.forEach((d, i) => {
                const a = i * 2.39996323;
                const r = clumpR * Math.sqrt((i + 0.5) / loneNodes.length);
                d.x = cx + Math.cos(a) * r;
                d.y = cy + Math.sin(a) * r;
                d.vx = d.vy = 0;
            });

            const clumpSim = d3.forceSimulation(loneNodes)
                .force('collide', d3.forceCollide().radius(d => getRadius(d) + 1).iterations(3))
                .force('x', d3.forceX(cx).strength(0.08))
                .force('y', d3.forceY(cy).strength(0.08))
                .stop();
            for (let i = 0; i < 200; ++i)
                clumpSim.tick();
            clumpSim.stop();
        }

        function bbox(list) {
            let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
            list.forEach(d => {
                if (d.x === undefined || d.y === undefined) return;
                const r = getRadius(d);
                minX = Math.min(minX, d.x - r); maxX = Math.max(maxX, d.x + r);
                minY = Math.min(minY, d.y - r); maxY = Math.max(maxY, d.y + r);
            });
            if (!isFinite(minX)) return null;
            return { minX, maxX, minY, maxY };
        }

        function fitToView() {
            const b = bbox(nodes);
            if (!b)
                return;
            const pad = 20;
            const k = Math.max(0.15, Math.min(8,
                (width - pad * 2) / Math.max(1, b.maxX - b.minX),
                (height - pad * 2) / Math.max(1, b.maxY - b.minY)));
            svg.call(zoom.transform, d3.zoomIdentity
                .translate(width / 2 - k * (b.minX + b.maxX) / 2,
                           height / 2 - k * (b.minY + b.maxY) / 2)
                .scale(k));
        }

        function draw() {
            linkSel = linkLayer.selectAll('line').data(links).join('line')
                .attr('stroke', '#6fffea')
                .attr('stroke-width', d => 0.4 + Math.log10(Math.max(10, d.count)) * 0.8)
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
                    pointerTooltip(event, d);
                })
                .on('mousemove', (event, d) => pointerTooltip(event, d))
                .on('mouseleave', () => clearHighlight())
                .on('click', (event, d) => {
                    window.open('/profile/' + d.id, '_blank');
                });

            clearHighlight();

            status.textContent = nodes.length + ' users, ' + links.length + ' connections'
                + (loneNodes.length ? ' (' + loneNodes.length + ' with no strong links, clumped to the side)' : '');
        }

        function pointerTooltip(event, d) {
            const [x, y] = d3.pointer(event, container);
            renderTooltip(x, y, d);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('usernameHighlight').addEventListener('input', () => {
            clearTimeout(highlightTimer);
            highlightTimer = setTimeout(clearHighlight, 150);
        });
        loadUserMap();
    });
</script>

<?php
require '../footer.php';
?>