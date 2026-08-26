<?php
    $PageTitle = "Edit Tournament Series";
    require "../../../header.php";

    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    $seriesId = $_GET["id"] ?? null;

    $isNew = true;
    $seriesName = "";
    $seriesAcronym = "";

    if ($seriesId !== null) {
        $stmt = $conn->prepare("SELECT * FROM tournament_series WHERE SeriesID = ?;");
        $stmt->bind_param("i", $seriesId);
        $stmt->execute();
        $series = $stmt->get_result()->fetch_assoc();

        if (is_null($series)) {
            http_response_code(404);
            exit();
        }

        $isNew = false;
        $seriesName = $series["Name"];
        $seriesAcronym = $series["Acronym"];

        $editStmt = $conn->prepare("
            SELECT EditData
            FROM tournament_series_edit_requests
            WHERE SeriesID = ? AND Status = 'Pending'
            LIMIT 1;
        ");
        $editStmt->bind_param("i", $seriesId);
        $editStmt->execute();
        $editResult = $editStmt->get_result()->fetch_assoc();
        $editStmt->close();

        if ($editResult && !empty($editResult['EditData'])) {
            $pendingData = json_decode($editResult['EditData'], true);
            if (isset($pendingData['SeriesName'])) {
                $seriesName = $pendingData['SeriesName'];
            }

            if (isset($pendingData['SeriesAcronym'])) {
                $seriesAcronym = $pendingData['SeriesAcronym'];
            }
        }
    }
?>

<style>
    .container {
        width: 100%;
        background-color: darkslategray;
        padding: 1.5em;
    }

    .container td {
        padding: 0.5em;
    }

    textarea {
        border: 1px solid white;
        background-color: #203838;
        color: white;
        margin: 0.25rem;
        width: 100%;
    }

    .container select{
        border: 1px solid white;
        background-color: #203838;
    }

    h1 {
        margin: 0;
    }
</style>

<h1><?php echo $isNew ? "Create new tournament series" : "Edit " . safe_htmlspecialchars($series["Name"]); ?></h1>
<hr />
<div class="container">
    <form action="SubmitEdit.php" method="POST">
        <?php if (!$isNew) { ?>
            <input type="hidden" name="SeriesID" value="<?php echo $series["SeriesID"]; ?>" />
        <?php } ?>

        <table>
            <tr>
                <td>
                    <label>Series name:</label><br>
                </td>
                <td>
                    <input autocomplete="off" name="SeriesName" id="SeriesName" placeholder="osu! World Cup" value="<?php echo $seriesName; ?>" maxlength="50" required/>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Series acronym:</label>
                </td>
                <td>
                    <input autocomplete="off" name="SeriesAcronym" id="SeriesAcronym" placeholder="OWC" value="<?php echo $seriesAcronym; ?>" maxlength="10" required/>
                </td>
            </tr>
            <tr>
                <td>
                </td>
                <td>
                    <button>Submit</button> <span id="statusText"></span>
                </td>
            </tr>
        </table>
    </form>
</div>

<?php
    require "../../../footer.php";
?>
