<?php
    $PageTitle = "Labs | Tournament formatter";
    require '../header.php';
?>

<h1 style="margin:0;">Tournament spreadsheet formatter</h1>
<span class="subText">Format copied spreadsheet cells into a copy-pastable format</span>
<hr>

<h2>Input</h2>

<textarea id="input" rows="20" cols="40" placeholder="Paste input here"></textarea>
<br><br>
<button onclick="formatPairs()">Format</button>
<br><br>

<h2>Output</h2>
<textarea id="output" rows="20" cols="40" readonly placeholder="NM1 | 105"></textarea>

<script>
    function formatPairs() {
        const input = document.getElementById("input").value;

        const lines = input
            .trim()
            .split(/\r?\n/)
            .map(line => line.trim())
            .filter(Boolean);

        if (lines.length % 2 !== 0) {
            alert("Input must contain an even number of lines.");
            return;
        }

        const half = lines.length / 2;
        const labels = lines.slice(0, half);
        const values = lines.slice(half);

        const result = labels
            .map((label, i) => `${label} | ${values[i]}`)
            .join("\n");

        document.getElementById("output").value = result;
    }
</script>

<?php
    require "../footer.php";
?>
