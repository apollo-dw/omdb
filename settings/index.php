<?php
    require '../base.php';
    $PageTitle = "Settings";

    require '../header.php';

    if(!$loggedIn){
        die("You need to be logged in to view this page.");
    }

    $profileTheme = [];
    if (!empty($user['ProfileTheme'])) {
        $profileTheme = json_decode($user['ProfileTheme'], true) ?? [];
    }
?>

<h1>Settings</h1>
<hr>
<form id="settingsForm">
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
                <input autocomplete="off" id="50Name" name="ratingNames[]" placeholder="5.0" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom50Rating"], ENT_QUOTES); ?>"/> 5.0<br>
                <input autocomplete="off" id="45Name" name="ratingNames[]" placeholder="4.5" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom45Rating"], ENT_QUOTES); ?>"/> 4.5<br>
                <input autocomplete="off" id="40Name" name="ratingNames[]" placeholder="4.0" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom40Rating"], ENT_QUOTES); ?>"/> 4.0<br>
                <input autocomplete="off" id="35Name" name="ratingNames[]" placeholder="3.5" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom35Rating"], ENT_QUOTES); ?>"/> 3.5<br>
                <input autocomplete="off" id="30Name" name="ratingNames[]" placeholder="3.0" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom30Rating"], ENT_QUOTES); ?>"/> 3.0<br>
                <input autocomplete="off" id="25Name" name="ratingNames[]" placeholder="2.5" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom25Rating"], ENT_QUOTES); ?>"/> 2.5<br>
                <input autocomplete="off" id="20Name" name="ratingNames[]" placeholder="2.0" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom20Rating"], ENT_QUOTES); ?>"/> 2.0<br>
                <input autocomplete="off" id="15Name" name="ratingNames[]" placeholder="1.5" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom15Rating"], ENT_QUOTES); ?>"/> 1.5<br>
                <input autocomplete="off" id="10Name" name="ratingNames[]" placeholder="1.0" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom10Rating"], ENT_QUOTES); ?>"/> 1.0<br>
                <input autocomplete="off" id="05Name" name="ratingNames[]" placeholder="0.5" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom05Rating"], ENT_QUOTES); ?>"/> 0.5<br>
                <input autocomplete="off" id="00Name" name="ratingNames[]" placeholder="0.0" maxlength="40" value="<?php echo safe_htmlspecialchars($user["Custom00Rating"], ENT_QUOTES); ?>"/> 0.0<br>
            </td>
        </tr>
        <tr>
            <td>
                <label>Description:</label><br>
            </td>
            <td>
                <textarea id="CustomDescription" name="CustomDescription" rows="5" cols="70"><?php echo safe_htmlspecialchars($user["CustomDescription"], ENT_QUOTES); ?></textarea> <br><br>
                <button type="button" onclick="insertTag('img')" class="small-button">img</button>
                <button type="button" onclick="insertTag('code')" class="small-button">code</button>
                <button type="button" onclick="insertTag('font', 'color=')" class="small-button">color</button>
                <button type="button" onclick="insertTag('b')" class="small-button">bold</button>
                <button type="button" onclick="insertTag('i')" class="small-button">italics</button>
                <button type="button" onclick="insertTag('u')" class="small-button">underline</button> <br>
                <span class="subText">Write a custom description that appears on your profile.</span>
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
                <label>Only show friends on front feeds:</label><br>
            </td>
            <td>
                <select name="OnlyFriendsOnFrontPage" id="OnlyFriendsOnFrontPage" autocomplete="off">
                    <option value="0">No</option>
                    <option value="1" <?php if ($user["OnlyFriendsOnFrontPage"]==1) { echo 'selected="selected"'; }?>>Yes</option>
                </select><br>
                <span class="subText">Only shows users that you have friended.</span>
            </td>
        </tr>
        <?php if ($user["IsPatron"] === 1) { ?>
        <tr>
            <td><hr></td>
            <td>
            <hr>
            <b>OMDB Patron settings</b><br>
            <span class="subText">thank you for supporting the site!</span>
            </td>
        </tr>
        <tr>
            <td>
                <label>Custom profile theme:</label>
            </td>
            <td>
                <input type="color" id="theme-color" value="<?php echo safe_htmlspecialchars($profileTheme['main-theme-color'] ?? '#2F4F4F', ENT_QUOTES); ?>"> Main Color<br>
                <input type="color" id="theme-color-darker" value="<?php echo safe_htmlspecialchars($profileTheme['main-theme-color-darker'] ?? '#203838', ENT_QUOTES); ?>"> Darker Color<br>
                <input type="color" id="theme-color-even-darker" value="<?php echo safe_htmlspecialchars($profileTheme['main-theme-color-even-darker'] ?? '#0c1515', ENT_QUOTES); ?>"> Even Darker Color<br>
                <input type="color" id="theme-text-color" value="<?php echo safe_htmlspecialchars($profileTheme['main-theme-text-color'] ?? '#ffffff', ENT_QUOTES); ?>"> Text Color<br>
                <input type="color" id="theme-background-color" value="<?php echo safe_htmlspecialchars($profileTheme['main-theme-background-color'] ?? '#000000', ENT_QUOTES); ?>"> Background Color<br>
                <input type="color" id="theme-subtext-color" value="<?php echo safe_htmlspecialchars($profileTheme['main-theme-subtext-color'] ?? '#a8a8a8', ENT_QUOTES); ?>"> Subtext Color<br>
                <input type="color" id="theme-link-color" value="<?php echo safe_htmlspecialchars($profileTheme['main-theme-link-color'] ?? '#6fffea', ENT_QUOTES); ?>"> Link Color<br>
                <input type="color" id="theme-star-color" value="<?php echo safe_htmlspecialchars($profileTheme['main-theme-star-color'] ?? '#ffffff', ENT_QUOTES); ?>"> Star Color<br>
                <input type="color" id="theme-patron-pink" value="<?php echo safe_htmlspecialchars($profileTheme['main-theme-patron-pink'] ?? '#ecb4f5', ENT_QUOTES); ?>"> Patron Badge Pink<br><br>
                <select id="theme-text-font-family" name="theme-text-font-family">
                <?php
                $currentFont = $profileTheme['main-theme-text-font-family'] ?? 'Verdana, sans-serif';
                ?>
                    <?php foreach ($CUSTOM_THEME_FONTS as $label => $stack): ?>
                        <option
                            value="<?php echo safe_htmlspecialchars($stack, ENT_QUOTES); ?>"
                            <?php echo ($currentFont === $stack) ? 'selected' : ''; ?>
                        >
                            <?php echo safe_htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                Text Font Family<br>
                <br>
                <button type='button' onclick="resetTheme()" class="small-button">Reset theme to default</button>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <td></td>
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
			$name = safe_htmlspecialchars($row["Name"], ENT_QUOTES, 'UTF-8');
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

<hr>
<h2>Active Sessions</h2>
<span class="subText">Manage your active logins across different devices. Revoking a session will log you out on that device.</span><br><br>

<table style="width: 100%; text-align: left; border-collapse: collapse;">
    <tr style="border-bottom: 1px solid #ccc;">
        <th style="padding-bottom: 5px;">Device / IP</th>
        <th style="padding-bottom: 5px;">Last Accessed</th>
        <th style="padding-bottom: 5px;">Action</th>
    </tr>
<?php
    $stmt = $conn->prepare("
        SELECT `SessionToken`, `DeviceInfo`, `IpAddress`, `LastAccessedAt` 
        FROM `sessions` 
        WHERE `UserID` = ? AND `ExpiresAt` > NOW() 
        ORDER BY `LastAccessedAt` DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows != 0) {
        while ($row = $result->fetch_assoc()) {
            $isCurrentSession = ($row["SessionToken"] === $sessionToken);
            $deviceInfo = safe_htmlspecialchars($row["DeviceInfo"] ?? "Unknown", ENT_QUOTES, 'UTF-8');
            $ip = safe_htmlspecialchars($row["IpAddress"] ?? "Unknown", ENT_QUOTES, 'UTF-8');
            $lastAccessed = safe_htmlspecialchars($row["LastAccessedAt"], ENT_QUOTES, 'UTF-8');
            
            echo "<tr>";
            echo "<td style='padding: 5px 0;'>{$deviceInfo}<br>{$ip}" . ($isCurrentSession ? "<br><strong>(Current)</strong>" : "") . "</td>";
            echo "<td>{$lastAccessed}</td>";
            
            if ($isCurrentSession) {
                echo "<td><em>Current Session</em></td>";
            } else {
                echo "<td><a href='KillSession.php?token={$row["SessionToken"]}' style='color: red;'>Revoke</a></td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='3'>No active sessions found.</td></tr>";
    }
?>
</table>

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
        const customDescription = document.getElementById("CustomDescription").value;
        const onlyFriendsOnFrontPage = document.getElementById("OnlyFriendsOnFrontPage").value;

        const profileTheme = {
            'main-theme-color': document.getElementById('theme-color')?.value,
            'main-theme-color-darker': document.getElementById('theme-color-darker')?.value,
            'main-theme-color-even-darker': document.getElementById('theme-color-even-darker')?.value,
            'main-theme-text-color': document.getElementById('theme-text-color')?.value,
            'main-theme-background-color': document.getElementById('theme-background-color')?.value,
            'main-theme-subtext-color': document.getElementById('theme-subtext-color')?.value,
            'main-theme-link-color': document.getElementById('theme-link-color')?.value,
            'main-theme-star-color': document.getElementById('theme-star-color')?.value,
            'main-theme-patron-pink': document.getElementById('theme-patron-pink')?.value,
            'main-theme-text-font-family': document.getElementById('theme-text-font-family')?.value
        };

        fetch("save.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ randomBehaviour, ratingNames, hideRatings, customDescription, onlyFriendsOnFrontPage, profileTheme }),
        }).then(response => {
            if (response.status == 200) {
                document.getElementById("statusText").textContent = "Saved!";
                document.getElementById("statusText").style = "display:inline;"
                $("#statusText").fadeOut( 3000, "linear", function() {});
            }
        });
    }
	
	function insertTag(tag, param = '') {
        var textarea = document.getElementById("CustomDescription");
        var cursorPos = textarea.selectionStart;
        var cursorEnd = textarea.selectionEnd;
        var tagText = '';

        switch (tag) {
            case 'font':
                tagText = '[' + tag + ' ' + param + ']' + textarea.value.substring(cursorPos, cursorEnd) + '[/' + tag + ']';
                break;
            default:
                tagText = '[' + tag + ']' + textarea.value.substring(cursorPos, cursorEnd) + '[/' + tag + ']';
        }

        var textBefore = textarea.value.substring(0, cursorPos);
        var textAfter = textarea.value.substring(cursorEnd);

        textarea.value = textBefore + tagText + textAfter;
        textarea.setSelectionRange(cursorPos, cursorPos + tagText.length);
        textarea.focus();
    }

    function resetTheme() {
        document.getElementById('theme-color').value = '#2F4F4F';
        document.getElementById('theme-color-darker').value = '#203838';     
        document.getElementById('theme-color-even-darker').value = '#0c1515';     
        document.getElementById('theme-text-color').value = '#ffffff';       
        document.getElementById('theme-background-color').value = '#000000'; 
        document.getElementById('theme-subtext-color').value = '#a8a8a8';    
        document.getElementById('theme-link-color').value = '#6fffea';       
        document.getElementById('theme-star-color').value = '#ffffff';       
        document.getElementById('theme-patron-pink').value = '#ecb4f5';
        document.getElementById('theme-text-font-family').value = 'Verdana, sans-serif';
    }
</script>

<?php
require '../footer.php';
?>
