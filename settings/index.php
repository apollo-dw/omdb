<?php
    require '../base.php';
    $PageTitle = "Settings";

    require '../header.php';

    if(!$loggedIn){
        die("You need to be logged in to view this page.");
    }

    ?>

<style>
    tr label{
        text-align:right;
        width:100%;
        display:inline-block;
    }
    td{
        vertical-align:top;
        padding: 1rem;
    }
    td input{
        margin: 0.25rem;
    }
</style>
<h1>Settings</h1>
<hr>
<form>
    <table>
        <tr>
            <td>
            </td>
            <td>
                Update username button here.<Br>
                <span class="subtext">Useful if you've had a namechange.</span>
            </td>
        </tr>
        <tr>
            <td>
                <label>Random behaviour:</label><br>
            </td>
            <td>
                <select name="RandomBehaviour" id="RandomBehaviour" autocomplete="off">
                    <option value="0">Prioritise Played</option>
                    <option value="1" <?php if ($user["DoTrueRandom"]==1) { echo 'selected="selected"'; }?>>True Random</option>
                </select><br>
                <span class="subtext">"Prioritise Played" only works if you have osu! supporter.</span>
            </td>
        </tr>
        <tr>
            <td>
                <label>Custom rating names:</label>
            </td>
            <td>
                <b>The functionality for this setting is not currently implemented!</b> (it will be at some point soon)<br>
                <input autocomplete="off" id="50Name" placeholder="5.0" maxlength="40" value="<?php echo $user["Custom50Rating"]; ?>"/> 5.0<br>
                <input autocomplete="off" id="45Name" placeholder="4.5" maxlength="40" value="<?php echo $user["Custom45Rating"]; ?>"/> 4.5<br>
                <input autocomplete="off" id="40Name" placeholder="4.0" maxlength="40" value="<?php echo $user["Custom40Rating"]; ?>"/> 4.0<br>
                <input autocomplete="off" id="35Name" placeholder="3.5" maxlength="40" value="<?php echo $user["Custom35Rating"]; ?>"/> 3.5<br>
                <input autocomplete="off" id="30Name" placeholder="3.0" maxlength="40" value="<?php echo $user["Custom30Rating"]; ?>"/> 3.0<br>
                <input autocomplete="off" id="25Name" placeholder="2.5" maxlength="40" value="<?php echo $user["Custom25Rating"]; ?>"/> 2.5<br>
                <input autocomplete="off" id="20Name" placeholder="2.0" maxlength="40" value="<?php echo $user["Custom20Rating"]; ?>"/> 2.0<br>
                <input autocomplete="off" id="15Name" placeholder="1.5" maxlength="40" value="<?php echo $user["Custom15Rating"]; ?>"/> 1.5<br>
                <input autocomplete="off" id="10Name" placeholder="1.0" maxlength="40" value="<?php echo $user["Custom10Rating"]; ?>"/> 1.0<br>
                <input autocomplete="off" id="05Name" placeholder="0.5" maxlength="40" value="<?php echo $user["Custom05Rating"]; ?>"/> 0.5<br>
                <input autocomplete="off" id="00Name" placeholder="0.0" maxlength="40" value="<?php echo $user["Custom00Rating"]; ?>"/> 0.0<br>
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
<h2>Api Stutff</h2>

coming soon

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

        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange=function() {
            if (this.readyState==4 && this.status==200) {
                document.getElementById("statusText").textContent = "Saved!";
                document.getElementById("statusText").style = "display:inline;"
                $("#statusText").fadeOut( 3000, "linear", function() {});
            }
        }
        xmlhttp.open("GET","save.php?random=" + randomBehaviour + "&ratings=" + ratingNames.toString(), true);
        xmlhttp.send();
    }
</script>

<?php
require '../footer.php';
?>