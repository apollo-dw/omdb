<?php
    require '../base.php';
    $PageTitle = "Settings";

    require '../header.php';

    if(!$loggedIn){
        die("You need to be logged in to view this page.");
    }

    ?>

<h1>Settings</h1>
<hr>
<form>
    <table>
        <tr>
            <td>
                <label>Random behaviour:</label><br>
            </td>
            <td>
                <select name="RandomBehaviour" id="RandomBehaviour" autocomplete="off">
                    <option value="0">Prioritise Played</option>
                    <option value="1" <?php if ($user["DoTrueRandom"]==1) { echo 'selected="selected"'; }?>>True Random</option>
                </select><br>
                <span class="subText">"Prioritise Played" only works if you have osu! supporter.</span>
            </td>
        </tr>
        <tr>
            <td>
                <label>Custom rating names:</label>
            </td>
            <td>
                <input autocomplete="off" id="50Name" placeholder="5.0" maxlength="40" value="<?php echo htmlspecialchars($user["Custom50Rating"]); ?>"/> 5.0<br>
                <input autocomplete="off" id="45Name" placeholder="4.5" maxlength="40" value="<?php echo htmlspecialchars($user["Custom45Rating"]); ?>"/> 4.5<br>
                <input autocomplete="off" id="40Name" placeholder="4.0" maxlength="40" value="<?php echo htmlspecialchars($user["Custom40Rating"]); ?>"/> 4.0<br>
                <input autocomplete="off" id="35Name" placeholder="3.5" maxlength="40" value="<?php echo htmlspecialchars($user["Custom35Rating"]); ?>"/> 3.5<br>
                <input autocomplete="off" id="30Name" placeholder="3.0" maxlength="40" value="<?php echo htmlspecialchars($user["Custom30Rating"]); ?>"/> 3.0<br>
                <input autocomplete="off" id="25Name" placeholder="2.5" maxlength="40" value="<?php echo htmlspecialchars($user["Custom25Rating"]); ?>"/> 2.5<br>
                <input autocomplete="off" id="20Name" placeholder="2.0" maxlength="40" value="<?php echo htmlspecialchars($user["Custom20Rating"]); ?>"/> 2.0<br>
                <input autocomplete="off" id="15Name" placeholder="1.5" maxlength="40" value="<?php echo htmlspecialchars($user["Custom15Rating"]); ?>"/> 1.5<br>
                <input autocomplete="off" id="10Name" placeholder="1.0" maxlength="40" value="<?php echo htmlspecialchars($user["Custom10Rating"]); ?>"/> 1.0<br>
                <input autocomplete="off" id="05Name" placeholder="0.5" maxlength="40" value="<?php echo htmlspecialchars($user["Custom05Rating"]); ?>"/> 0.5<br>
                <input autocomplete="off" id="00Name" placeholder="0.0" maxlength="40" value="<?php echo htmlspecialchars($user["Custom00Rating"]); ?>"/> 0.0<br>
            </td>
        </tr>
        <tr>
            <td>
                <label>Hide ratings:</label><br>
            </td>
            <td>
                <select name="HideRatings" id="HideRatings" autocomplete="off">
                    <option value="0">No</option>
                    <option value="1" <?php if ($user["HideRatings"]==1) { echo 'selected="selected"'; }?>>Yes</option>
                </select><br>
                <span class="subText">Disallows your ratings from appearing on the front page feed.</span>
            </td>
        </tr>
        <tr>
            <td>
            </td>
            <td>
                <button type='button' onclick="saveChanges()">Save changes</button> <span id="statusText"></span>
            </td>
        </tr>
    </table>
</form>
<hr>
<h2>API</h2>
    <a href="https://github.com/apollo-dw/omdb/wiki/API" target="_blank" rel="noopener noreferrer">Click to view the (bare bones) documentations.</a><br>
    <span class="subText">Please keep your API key secure - if it leaks then it's as bad as having your PASSWORD leaked.<br> Click your application name to REVEAL your API key.</span><br><br>

<?php
    $stmt = $conn->prepare("SELECT * FROM `apikeys` WHERE UserID=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows != 0) {
        while ($row = $result->fetch_assoc()) {
			$name = htmlspecialchars($row["Name"], ENT_QUOTES, 'UTF-8');
            echo "<details><summary>{$name} <a href='RemoveApiApp.php?id={$row["ApiID"]}'><i class='icon-remove'></i></a></summary><span class='subText'>{$row["ApiKey"]}</span></details>";
        }
    }
?>

<form action="CreateNewApiApp.php" method="get">
    <table>
        <tr>
            <td>
                <label>New Application Name:</label><br>
            </td>
            <td>
                <input type="text" autocomplete="off" name="apiname" id="apiname" placeholder="omdb application" maxlength="255" minlength="1" value="" required><br>
            </td>
        </tr>
        <tr>
            <td>
            </td>
            <td>
                <button>Create new application</button>
            </td>
        </tr>
    </table>
</form>

<script>
    function saveChanges(){
        const ratingNames = [
            document.getElementById("50Name").value,
            document.getElementById("45Name").value,
            document.getElementById("40Name").value,
            document.getElementById("35Name").value,
            document.getElementById("30Name").value,
            document.getElementById("25Name").value,
            document.getElementById("20Name").value,
            document.getElementById("15Name").value,
            document.getElementById("10Name").value,
            document.getElementById("05Name").value,
            document.getElementById("00Name").value
        ];

        const randomBehaviour = document.getElementById("RandomBehaviour").value;
        const hideRatings = document.getElementById("HideRatings").value;

        fetch("save.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ randomBehaviour, ratingNames, hideRatings }),
        }).then(response => {
            if (response.status == 200) {
                document.getElementById("statusText").textContent = "Saved!";
                document.getElementById("statusText").style = "display:inline;"
                $("#statusText").fadeOut( 3000, "linear", function() {});
            }
        });
    }
</script>

<?php
require '../footer.php';
?>
