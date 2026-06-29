<?php
    $descriptorId = isset($_GET['descriptor_id']) ? intval($_GET['descriptor_id']) : null;
    $isEdit = !is_null($descriptorId);

    $PageTitle = $isEdit ? "Edit Descriptor Proposal" : "New Descriptor Proposal";
    require '../../../header.php';

    $MAX_PROPOSAL_COUNT = 9;
    $activeProposalCount = $conn->query("SELECT * FROM `descriptor_proposals` WHERE Status = 'pending';")->num_rows;

    $name = "";
    $shortDescription = "";
    $parentId = "";
    $usable = "1";

    if ($isEdit) {
        $stmt = $conn->prepare("SELECT * FROM `descriptors` WHERE DescriptorID = ?");
        $stmt->bind_param("i", $descriptorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($descriptor = $result->fetch_assoc()) {
            $name = htmlspecialchars($descriptor['Name']);
            $shortDescription = htmlspecialchars($descriptor['ShortDescription']);
            $parentId = htmlspecialchars($descriptor['ParentID']);
            $usable = $descriptor['Usable'];
        } else {
            echo "<h2>Descriptor not found.</h2>";
            require '../../../footer.php';
            exit;
        }
        $stmt->close();
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

    .force-lowercase {
        text-transform: lowercase;
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

<h1><?php echo $isEdit ? "Propose descriptor edit" : "Propose new descriptor"; ?></h1>
<div class="container">
    <?php if ($activeProposalCount >= $MAX_PROPOSAL_COUNT) { ?>
    <center>
        <h2>Sorry!</h2>
        There is a maximum on the amount of active proposals at any given time. (<?php echo $MAX_PROPOSAL_COUNT; ?>) <br>
        There are <?php echo $activeProposalCount; ?> proposals open currently. <br>
        Please wait!
    </center>
    <?php } else { ?>
    <form action="SubmitProposal.php" method="POST">
        <?php if ($isEdit) { ?>
            <input type="hidden" name="DescriptorID" value="<?php echo $descriptorId; ?>" />
        <?php } ?>

        <table>
            <tr>
                <td>
                    <label>Descriptor name:</label><br>
                </td>
                <td style="width:80%;">
                    <input class="force-lowercase" autocomplete="off" name="DescriptorName" id="DescriptorName" placeholder="symmetrical" value="<?php echo $name; ?>" maxlength="40" required/>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Description:</label>
                </td>
                <td>
                    <textarea name="ShortDescription" placeholder="Employs symmetry within the map design, often mirroring elements along the horizontal centreline." required><?php echo $shortDescription; ?></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Parent descriptor ID:</label><br>
                </td>
                <td>
                    <input class="force-lowercase" autocomplete="off" name="ParentDescriptorID" id="ParentDescriptorID" placeholder="46" value="<?php echo $parentId; ?>" maxlength="40" /> <br>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Usable descriptor:</label><br>
                </td>
                <td>
                    <select name="Usable">
                        <option value="1" <?php echo $usable == '1' ? 'selected' : ''; ?>>True</option>
                        <option value="0" <?php echo $usable == '0' ? 'selected' : ''; ?>>False</option>
                    </select><br>
                    <span class="subText">Can this descriptor be used on beatmaps? <br> Some descriptors exist as a way to group other descriptors (such as "style").</span>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Entry comment:</label>
                </td>
                <td>
                    <textarea name="EntryComment" required placeholder="<?php echo $isEdit ? 'I think this edit is necessary because...' : 'I think this descriptor would be good, because...'; ?>"></textarea>
                    <?php if ($isEdit) { ?>
                        <span class="subText">Give a reason for this edit. <br>Cite sources for any changes or additions.</span>
                    <?php } else { ?>
                        <span class="subText">Explain why this would be good as a descriptor. <br>Cite sources for naming and existing usage if applicable.</span>
                    <?php } ?>
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
    <?php } ?>
</div>

<?php
    require '../../../footer.php';
?>