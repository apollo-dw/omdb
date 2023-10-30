<?php
    $proposal_id = $_GET['id'] ?? -1;
    $PageTitle = "New Descriptor Proposal";
    require '../../../header.php';

    $MAX_PROPOSAL_COUNT = 3;
    $activeProposalCount = $conn->query("SELECT * FROM `descriptor_proposals` WHERE Status = 'pending';")->num_rows;
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

<h1>Propose new descriptor</h1>
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
        <table>
            <tr>
                <td>
                    <label>Descriptor name:</label><br>
                </td>
                <td style="width:80%;">
                    <input class="force-lowercase" autocomplete="off" name="DescriptorName" id="DescriptorName" placeholder="symmetrical" maxlength="40" required/>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Description:</label>
                </td>
                <td>
                    <textarea name="ShortDescription" placeholder="Employs symmetry within the map design, often mirroring elements along the horizontal centreline." required></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Parent descriptor ID:</label><br>
                </td>
                <td>
                    <input class="force-lowercase" autocomplete="off" name="ParentDescriptorID" id="ParentDescriptorID" placeholder="46" maxlength="40" /> <br>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Usable descriptor:</label><br>
                </td>
                <td>
                    <select name="Usable">
                        <option value="1">True</option>
                        <option value="0">False</option>
                    </select><br>
                    <span class="subText">Can this descriptor be used on beatmaps? <br> Some descriptors exist as a way to group other descriptors (such as "style").</span>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Entry comment:</label>
                </td>
                <td>
                    <textarea name="EntryComment" required placeholder="I think this descriptor would be good, because..."></textarea>
                    <span class="subText">Explain why this would be good as a descriptor. <br>Cite sources for naming and existing usage if applicable.</span>
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

<script>
</script>

<?php
require '../../../footer.php';
?>
