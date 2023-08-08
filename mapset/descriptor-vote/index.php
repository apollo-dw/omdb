<?php
    $map_id = $_GET['id'] ?? -1;
    $PageTitle = "Vote Descriptors";
    require "../../base.php";
    require '../../header.php';

    $stmt = $conn->prepare("SELECT * FROM `beatmaps` WHERE `BeatmapID` = ?;");
    $stmt->bind_param("i", $map_id);
    $stmt->execute();
    $beatmap = $stmt->get_result()->fetch_assoc();

    $title = htmlspecialchars($beatmap['Title']);
    $difficultyName = htmlspecialchars($beatmap['DifficultyName']);

    if (is_null($beatmap))
        die("Beatmap not found");

    function generateTreeHTML($tree) {
        $html = '<ul>';
        foreach ($tree as $node) {
            $descriptorID = $node['descriptorID'];
            $isUsable = $node['Usable']; // Assuming 'Usable' is the column name

            // Check if the descriptor is usable, and apply appropriate class
            $class = $isUsable ? 'class="descriptor"' : 'class="descriptor unusable"';

            $html .= '<li ' . $class . ' data-descriptor-id="' . $descriptorID . '">' . $node['name'];
            if (isset($node['children'])) {
                $html .= generateTreeHTML($node['children']);
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    function buildTree(array &$elements, $parentID = null) {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['parentID'] === $parentID) {
                $children = buildTree($elements, $element['descriptorID']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
    ?>

<style>
    .popover {
        display: none;
        position: absolute;
        background-color: darkslategray;
        border: 1px solid #ccc;
        padding: 10px;
        z-index: 1000;
        font-size: 10px;
        overflow-y: scroll;
        height: 30em;
        margin: 0.5em;
    }

    ul {
        padding: 0;
        margin: 0;
    }

    ul li {
        margin-left: 2em;
    }

    .descriptor {
        cursor: pointer;
        color: white;
    }

    .unusable {
        color: grey;
        cursor: revert;
    }

    .descriptor-box {
        border:1px solid white;
        margin: 0.5em;
        padding: 1em;
    }

    .descriptor-box h2{
        margin: 0;
    }

    .descriptor-box .actions {
        font-size: 1.5em;
    }

    .descriptor-box .actions i {
        margin-left: 0.25em;
        cursor:pointer;
    }

    .decsriptor-box .user {
        margin-left: 1em;
    }
</style>

<h1>Descriptor vote for <?php echo "{$title} [{$difficultyName}]"; ?></h1>
<a href="../<?php echo $beatmap["SetID"]; ?>">Return to mapset</a><br><br><br><br>

<div style="background-color:DarkSlateGrey; padding: 0.5em;">
    <p>
        You can propose and vote on descriptors for <b><?php echo "{$title} [{$difficultyName}]"; ?></b> on this page.<br>
        Click <i>Propose Descriptor</i> to select a new descriptor.
    </p>
    <p>
        Misuse of the descriptor feature will result in you being banned. Do not abuse this feature by assigning obviously incorrect descriptors.
    </p>
    <button id="proposeDescriptorButton">Propose Descriptor</button>
    <div id="descriptorTreePopover" class="popover">
        <?php
            $stmt = $conn->prepare("SELECT descriptorID, name, ShortDescription, parentID, Usable FROM descriptors");
            $stmt->execute();
            $result = $stmt->get_result();
            $descriptors = $result->fetch_all(MYSQLI_ASSOC);

            $tree = buildTree($descriptors);
            echo generateTreeHTML($tree);
        ?>
    </div>

    <div id="descriptor-box-container">
        <?php
            $stmt = $conn->prepare("SELECT d.DescriptorID, d.Name, d.ShortDescription, 
                                          SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) AS upvotes, SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END) AS downvotes
                                          FROM descriptor_votes 
                                          JOIN descriptors d on descriptor_votes.DescriptorID = d.DescriptorID
                                          WHERE BeatmapID = ?
                                          GROUP BY DescriptorID
                                          ORDER BY (SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) - SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END)) DESC;");
            $stmt->bind_param("i", $map_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while($row = $result->fetch_assoc()) {

                $stmt = $conn->prepare("
                        SELECT
                            IF(dv.Vote = 1, GROUP_CONCAT(u1.Username SEPARATOR ', '), '') AS upvoteUsernames,
                            IF(dv.Vote = 0, GROUP_CONCAT(u2.Username SEPARATOR ', '), '') AS downvoteUsernames
                        FROM descriptor_votes dv
                        INNER JOIN Users u1 ON dv.UserID = u1.UserID
                        LEFT JOIN Users u2 ON dv.UserID = u2.UserID
                        WHERE dv.BeatmapID = ? AND dv.DescriptorID = ?
                        GROUP BY dv.Vote");
                $stmt->bind_param('ii', $map_id, $row["DescriptorID"]);
                $stmt->execute();
                $voteResult = $stmt->get_result();

                $upvoteUsernames = '';
                $downvoteUsernames = '';

                while ($voteRow = $voteResult->fetch_assoc()) {
                    if ($voteRow['upvoteUsernames'] !== '') {
                        $upvoteUsernames = $voteRow['upvoteUsernames'];
                    }
                    if ($voteRow['downvoteUsernames'] !== '') {
                        $downvoteUsernames = $voteRow['downvoteUsernames'];
                    }
                }

                ?>
                <div class="descriptor-box" data-descriptor-id="<?php echo $row["DescriptorID"]?>">
                    <h2><?php echo $row["Name"]?></h2>
                    <span class="subText"><?php echo $row["ShortDescription"]?></span> <br>
                    <div class="actions">
                        <i class="icon-thumbs-up"></i><i class="icon-thumbs-down"></i>
                    </div>
                    <hr>
                    <b class="upvotes">upvotes (<?php echo $row["upvotes"]?>): </b> <span class="user"><?php echo $upvoteUsernames; ?></span>
                    <hr>
                    <b class="downvotes">downvotes (<?php echo $row["downvotes"]?>): </b> <span class="user"><?php echo $downvoteUsernames; ?></span>
                </div>
        <?php } ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#proposeDescriptorButton').click(function() {
            $('#descriptorTreePopover').toggle();
        });

        $(document).on('click', '.descriptor', function(event) {
            if ($(this).hasClass('unusable')) {
                event.stopPropagation();
                return;
            }
            var descriptorID = $(this).data('descriptor-id');
            handleDescriptorClick(descriptorID);
        });

        $('.descriptor-box').each(function() {
            const descriptorID = $(this).data('descriptor-id');
            const upvoteIcon = $(this).find('.icon-thumbs-up');
            const downvoteIcon = $(this).find('.icon-thumbs-down');

            upvoteIcon.click(function() {
                submitVote(descriptorID, 1);
            });

            downvoteIcon.click(function() {
                submitVote(descriptorID, 0);
            });
        });
    });

    function handleDescriptorClick(descriptorID) {
        $.ajax({
            type: "GET",
            url: "GetDescriptor.php",
            data: { descriptorID: descriptorID },
            dataType: "json",
            success: function(response) {
                if (response) {
                    if (!isDescriptorBoxExist(descriptorID)) {
                        createDescriptorBox(response);
                    }
                    submitVote(descriptorID, 1);
                    $('#descriptorTreePopover').toggle();
                }
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    }

    function isDescriptorBoxExist(descriptorID) {
        return $('.descriptor-box[data-descriptor-id="' + descriptorID + '"]').length > 0;
    }

    function createDescriptorBox(descriptorData) {
        const descriptorBoxContainer = $('#descriptor-box-container');
        const descriptorBox = $('<div>', {
            class: 'descriptor-box',
            'data-descriptor-id': descriptorData.DescriptorID
        });

        $('<h2>', { text: descriptorData.Name }).appendTo(descriptorBox);
        $('<span>', { class: 'subText', text: descriptorData.ShortDescription }).appendTo(descriptorBox);
        $('<div>', { class: 'actions' }).append(
            $('<i>', { class: 'icon-thumbs-up' }),
            $('<i>', { class: 'icon-thumbs-down' }),
            $('<i>', { class: 'icon-remove' })
        ).appendTo(descriptorBox);
        $('<hr>').appendTo(descriptorBox);
        $('<b>', { class: 'upvotes' }).text(`upvotes (0): `).appendTo(descriptorBox);
        $('<span>', { class: 'user' }).appendTo(descriptorBox);
        $('<hr>').appendTo(descriptorBox);
        $('<b>', { class: 'downvotes' }).text(`downvotes (0): `).appendTo(descriptorBox);
        $('<span>', { class: 'user' }).appendTo(descriptorBox);

        descriptorBoxContainer.append(descriptorBox);

        const upvoteIcon = descriptorBox.find('.icon-thumbs-up');
        const downvoteIcon = descriptorBox.find('.icon-thumbs-down');

        upvoteIcon.click(function() {
            submitVote(descriptorData.DescriptorID, 1);
        });

        downvoteIcon.click(function() {
            submitVote(descriptorData.DescriptorID, 0);
        });
    }

    function updateDescriptorBox(descriptorID, voteData) {
        const descriptorBox = $('.descriptor-box[data-descriptor-id="' + descriptorID + '"]');
        const upvotesElem = descriptorBox.find('.upvotes');
        const downvotesElem = descriptorBox.find('.downvotes');
        const upvoteUsernamesElem = upvotesElem.next('.user');
        const downvoteUsernamesElem = downvotesElem.next('.user');

        upvotesElem.html(`upvotes (${voteData.upvotes}):`);
        upvoteUsernamesElem.text(voteData.upvoteUsernames.join(', '));

        downvotesElem.html(`downvotes (${voteData.downvotes}):`);
        downvoteUsernamesElem.text(voteData.downvoteUsernames.join(', '));
    }

    function submitVote(descriptorID, vote) {
        $.ajax({
            type: "POST",
            url: "SubmitVote.php",
            data: {
                beatmapID: <?php echo $beatmap["BeatmapID"]; ?>,
                descriptorID: descriptorID,
                vote: vote
            },
            dataType: "json",
            success: function(response) {
                updateDescriptorBox(descriptorID, response);
            },
            error: function(xhr, status, error) {
                console.error('Error submitting vote:', error);
            }
        });
    }
</script>

<?php
require '../../footer.php';
?>