<?php
    $PageTitle = "Tournament series edit";
    require "../../../header.php";

    $EditId = GetIntParam('id', -1, "Y U POST CRINGE");

    $stmt = $conn->prepare("SELECT * FROM `tournament_series_edit_requests` WHERE `EditID` = ?;");
    $stmt->bind_param("i", $EditId);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();

    if (is_null($edit)) {
        http_response_code(404);
        exit();
    }

    $editData = json_decode($edit['EditData'] ?? '{}', true);
    $seriesName = $editData['SeriesName'] ?? 'Unknown Series';
    $seriesAcronym = $editData['SeriesAcronym'] ?? '';
    $requestType = empty($edit['SeriesID']) ? 'New' : 'Edit';

    $originalSeries = null;

    if (!is_null($edit["SeriesID"])) {
        $stmt = $conn->prepare("SELECT * FROM `tournament_series` WHERE `SeriesID` = ?;");
        $stmt->bind_param("i", $edit["SeriesID"]);
        $stmt->execute();
        $originalSeries = $stmt->get_result()->fetch_assoc();
    }
?>

<style>
    .header {
        background-color: DarkSlateGrey;
        text-align: center;
        width: 100%;
        padding: 2em;
        box-sizing: border-box;
    }

    .bordered-container {
        width:100%;
        border:1px solid DarkSlateGrey;
        padding: 0.5em;
        box-sizing: border-box;
    }

    .bordered-container td {
        padding: 0.5em;
    }

    .right {
        text-align: right;
        font-weight: bold;
    }

    .proposal-box .actions {
        font-size: 1.5em;
    }

    .proposal-box .actions i {
        margin-left: 0.25em;
        cursor:pointer;
        color: grey;
    }

    .diff-old-value {
        color: #ff9090;
        font-size: 0.85em;
    }
</style>

<div class="header">
    <h1 style="margin:0;"><?php echo safe_htmlspecialchars($seriesName, ENT_QUOTES); ?></h1>
    <span class="subText"><?php echo $edit["Status"]; ?></span>
</div>

<br>

<div class="bordered-container">
    <table>
        <tr>
            <td class="right">Name</td>
            <td>
                <?php echo safe_htmlspecialchars($seriesName, ENT_QUOTES); ?>
                <?php if ($originalSeries && $originalSeries["Name"] !== $seriesName) { ?>
                    <br>
                    <span class="diff-old-value">(was: <?php echo safe_htmlspecialchars($originalSeries["Name"], ENT_QUOTES); ?>)</span>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td class="right">Acronym</td>
            <td>
                <?php echo safe_htmlspecialchars($seriesAcronym, ENT_QUOTES); ?>
                <?php if ($originalSeries && $originalSeries["Acronym"] !== $seriesAcronym) { ?>
                    <br>
                    <span class="diff-old-value">(was: <?php echo safe_htmlspecialchars($originalSeries["Acronym"], ENT_QUOTES); ?>)</span>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td class="right">Submitted by</td>
            <td><a href="/profile/<?php echo $edit["EditorID"]; ?>"><?php echo safe_htmlspecialchars(GetUserNameFromId($edit["EditorID"], $conn), ENT_QUOTES); ?></a></td>
        </tr>
        <tr>
            <td class="right">Creation date</td>
            <td><?php echo $edit["CreatedAt"]; ?></td>
        </tr>
        <?php if ($edit["CreatedAt"] !== $edit["UpdatedAt"]) { ?>
        <tr>
            <td class="right">Last updated</td>
            <td><?php echo $edit["UpdatedAt"]; ?></td>
        </tr>
        <?php } ?>
        <?php if (!is_null($edit["AdminID"]) && $edit["Status"] !== "Pending") {
            $adminName = GetUserNameFromId($edit["AdminID"], $conn);
            ?>
            <tr>
                <td class="right">
                    Status
                </td>
                <td>
                    <?php echo safe_htmlspecialchars("{$edit["Status"]} by {$adminName}", ENT_QUOTES); ?>
                </td>
            </tr>
        <?php } ?>
    </table>
    <?php if ($loggedIn && $userName === "moonpoint") { ?>
        <label for="changeStatus">Status:</label>
        <select id="changeStatus">
            <option value="Pending" <?php if ($edit["Status"] === "Pending") {
                echo 'selected="selected"';
            } ?>>Pending</option>
            <option value="Approved" <?php if ($edit["Status"] === "Approved") {
                echo 'selected="selected"';
            } ?>>Approved</option>
            <option value="Denied" <?php if ($edit["Status"] === "Denied") {
                echo 'selected="selected"';
            } ?>>Denied</option>
        </select>
    <?php } ?>
</div>

<script>
    $("#changeStatus").change(function() {
            var status = $(this).val();

            $.ajax({
                type: "POST",
                url: "ChangeStatus.php",
                data: {
                    EditID: <?php echo $EditId; ?>,
                    Status: status
                },
                success: function() {
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error('Error submitting status:', error);
                }
            });
        });
</script>

<?php
     require "../../../footer.php";
?>
