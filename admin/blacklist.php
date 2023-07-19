<?php
include 'header.php';
?>

<style>
    table, tr, td{
        text-align: center;
        vertical-align: middle;
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        border-spacing: 0;
    }

    tr, td {
        padding: 0.5em;
    }
</style>

<h1>blacklist</h1>

<label for="blacklistID">Blacklist ID:</label>
<input name="blacklistID" type="number" oninput="GetUsername()" />
<input type="button" value="Add ID" onclick="SubmitBlacklist()"/>
<div id="username"></div>

<br><br><br>

<div>
    <table>
        <tr>
            <th>User ID</th>
            <th>Username</th>
        </tr>
        <?php
        $stmt = $conn->prepare("SELECT m.* FROM blacklist JOIN mappernames m on blacklist.UserID = m.UserID;");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["UserID"] . "</td>";
            echo "<td>" . $row["Username"] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>

<script>
    let timeoutId;

    function debounce(func, delay) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(func, delay);
    }

    function GetUsername() {
        const blacklistID = document.querySelector('input[name="blacklistID"]').value;

        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    const username = xhr.responseText;
                    document.getElementById('username').textContent = username;
                } else {
                    console.error('Error: ' + xhr.status);
                }
            }
        };

        xhr.open('GET', 'actions/GetUsernameFromID.php?id=' + encodeURIComponent(blacklistID), true);
        xhr.send();
    }

    function SubmitBlacklist() {
        const blacklistID = document.querySelector('input[name="blacklistID"]').value;

        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    console.log('Blacklist request submitted successfully');
                } else {
                    console.error('Error: ' + xhr.status);
                }
            }
        };

        xhr.open('POST', 'actions/BlacklistUser.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('blacklistID=' + encodeURIComponent(blacklistID));
    }

</script>