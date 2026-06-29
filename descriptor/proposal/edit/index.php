<?php
    $PageTitle = "Edit Descriptor Proposal";
    require '../../../header.php';

    $proposal_id = GetIntParam('id', -1, "Y U POST CRINGE");

    $stmt = $conn->prepare("SELECT * FROM `descriptor_proposals` WHERE `ProposalID` = ?;");
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $proposal = $stmt->get_result()->fetch_assoc();

    if (is_null($proposal))
        die("Proposal not found");

    if (!$loggedIn)
        die("You need to be logged in");

    if ($proposal["ProposerID"] != $userId)
        die("This is not your proposal!");

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
</style>

<h1>Edit descriptor proposal</h1>
<div class="container">
    <form action="SubmitEdit.php" method="POST">
        <input type="hidden" name="ProposalID" value="<?php echo $proposal_id; ?>" />
        <table>
            <tr>
                <td>
                    <label>Descriptor name:</label><br>
                </td>
                <td style="width:80%;">
                    <input autocomplete="off" name="DescriptorName" id="DescriptorName" placeholder="symmetrical" maxlength="40" value="<?php echo safe_htmlspecialchars($proposal["Name"], ENT_QUOTES); ?>" required/>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Description:</label>
                </td>
                <td>
                    <textarea name="ShortDescription" placeholder="Employs symmetry within the map design, often mirroring elements along the horizontal centreline." required><?php echo safe_htmlspecialchars($proposal["ShortDescription"], ENT_QUOTES); ?></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Parent descriptor ID:</label><br>
                </td>
                <td>
                    <input autocomplete="off" name="ParentDescriptorID" id="ParentDescriptorID" placeholder="46" maxlength="40" value="<?php echo $proposal["ParentID"]; ?>"/> <br>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="Usable">Usable descriptor:</label><br>
                </td>
                <td>
                    <select name="Usable">
                        <option value="1" <?php if ($proposal["Usable"] === 1) echo "selected"; ?> >True</option>
                        <option value="0" <?php if ($proposal["Usable"] === 0) echo "selected"; ?> >False</option>
                    </select><br>
                    <span class="subText">Can this descriptor be used on beatmaps? <br> Some descriptors exist as a way to group other descriptors (such as "style").</span>
                </td>
            </tr>
            <tr>
                <td>
                </td>
                <td>

                    <button>Save changes</button><br>
                    <span class="subText">It is recommended to leave a comment detailing your edits after you've made some changes.</span>
                </td>
            </tr>
        </table>
    </form>
</div>

<?php
require '../../../footer.php';
?>
