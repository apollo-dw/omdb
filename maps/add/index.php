<?php
    $PageTitle = "Add graved set";
    require '../../header.php';
?>

<style>
    .container {
        width: 100%;
        background-color: darkslategray;
        padding: 1.5em;
        box-sizing: border-box;
        overflow: hidden;
    }
</style>

<h1>Add graved set</h1>

<div class="container">
#
    Add a new graved set to OMDB through this page! Some things to note:
    <ul>
        <li><b>The set you're trying to add has to be at least 6 months old.</b> This is to provide ample space for sets that may move back to pending.</li>
        <li><b>Sets that have their status changed will have their page and associated ratings & comments archived.</b> The only exception to this are sets getting loved, since it guarantees that map contents won't change. Exceptions may be given to this on a case-by-case basis.</li>
        <li><b>Ratings and comments are removed if their difficulty gets deleted.</b></li>
        <li><b>You cannot add blacklisted mappers' graveyarded sets.</b></li>
    </ul>
    <hr>
	<?php if ($loggedIn) { ?>
        <form action="AddGravedMapset.php">
            <label for="id">Set ID:</label><br>
            <input type="text" name="id" id="id" required />
            <input type="submit" value="Submit" />
        </form>
    <?php } else { ?>
        You need to be logged in to add maps!
    <?php } ?>
</div>

<?php
require '../../footer.php';
?>