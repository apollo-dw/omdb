<?php
    $proposal_id = $_GET['id'] ?? -1;
    $PageTitle = "Descriptor Proposal";
    require "../../base.php";
    require '../../header.php';

    $stmt = $conn->prepare("SELECT * FROM `descriptor_proposals` WHERE `ProposalID` = ?;");
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $proposal = $stmt->get_result()->fetch_assoc();

    if (is_null($proposal))
        die("Proposal not found");

    $stmt = $conn->prepare("SELECT * FROM descriptors WHERE DescriptorID = ?;");
    $stmt->bind_param("i", $proposal["ParentID"]);
    $stmt->execute();
    $parentDescriptor = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT Vote FROM descriptor_proposal_votes WHERE UserID = ? AND ProposalID = ?");
    $stmt->bind_param('ii', $userId, $proposal_id);
    $stmt->execute();
    $voteResult = $stmt->get_result();

    $userVote = "";
    if ($voteResult->num_rows > 0)
        $userVote = $voteResult->fetch_assoc()["Vote"];

    $stmt = $conn->prepare("SELECT Count(*) FROM descriptor_proposal_comments WHERE ProposalID = ?;");
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $commentCount = $stmt->get_result()->fetch_row()[0];
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

    .bordered-container > table {
        margin: auto;
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
</style>

<div class="header">
    <h1 style="margin:0;"><?php echo $proposal["Name"]; ?></h1>
    <span class="subText"><?php echo $proposal["Status"]; ?></span>
</div>

<br>

<div class="bordered-container column-when-mobile-container flex-container">
    <div class="column-when-mobile" style="width:60%;">
        <table>
            <tr>
                <td class="right">Name</td>
                <td><?php echo $proposal["Name"]; ?></td>
            </tr>
            <tr>
                <td class="right">Description</td>
                <td><?php echo $proposal["ShortDescription"]; ?></td>
            </tr>
            <tr>
                <td class="right">Parent descriptor</td>
                <td><?php echo $parentDescriptor["Name"]; ?></td>
            </tr>
            <tr>
                <td class="right">Proposer</td>
                <td><a href="/profile/<?php echo $proposal["ProposerID"]; ?>"><?php echo GetUserNameFromId($proposal["ProposerID"], $conn); ?></a></td>
            </tr>
            <tr>
                <td class="right">Type</td>
                <td><?php echo $proposal["Type"]; ?></td>
            </tr>
            <tr>
                <td class="right">Creation date</td>
                <td><?php echo $proposal["Timestamp"]; ?></td>
            </tr>
            <?php if(!is_null($proposal["EditorID"]) && $proposal["Status"] !== "pending") {
                $editorName = GetUserNameFromId($proposal["EditorID"], $conn);
                ?>
                <tr>
                    <td class="right">
                        Status
                    </td>
                    <td>
                        <?php echo "{$proposal["Status"]} by {$editorName}"; ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <?php if ($loggedIn && $userName === "moonpoint") { ?>
            <label for="changeStatus">Status:</label>
            <select id="changeStatus">
                <option value="pending" <?php if ($proposal["Status"] === "pending") echo 'selected="selected"'; ?>>Pending</option>
                <option value="approved" <?php if ($proposal["Status"] === "approved") echo 'selected="selected"'; ?>>Approved</option>
                <option value="denied" <?php if ($proposal["Status"] === "denied") echo 'selected="selected"'; ?>>Denied</option>
            </select>
        <?php } ?>
    </div>
    <div class="column-when-mobile" style="width:40%;">
        <div id="proposal-box-container">
            <?php
            $stmt = $conn->prepare("SELECT COALESCE(SUM(CASE WHEN Vote = 'yes' THEN 1 ELSE 0 END), 0) AS upvotes, 
                                                 COALESCE(SUM(CASE WHEN Vote = 'no' THEN 1 ELSE 0 END), 0) AS downvotes,
                                                 COALESCE(SUM(CASE WHEN Vote = 'hold' THEN 1 ELSE 0 END), 0) AS holds
                                                 FROM descriptor_proposal_votes pv
                                                 WHERE pv.ProposalID = ?;");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $stmt = $conn->prepare("SELECT
                                    GROUP_CONCAT(CASE WHEN dpv.Vote = 'yes' THEN u1.Username ELSE NULL END SEPARATOR ', ') AS upvoteUsernames,
                                    GROUP_CONCAT(CASE WHEN dpv.Vote = 'no' THEN u1.Username ELSE NULL END SEPARATOR ', ') AS downvoteUsernames,
                                    GROUP_CONCAT(CASE WHEN dpv.Vote = 'hold' THEN u1.Username ELSE NULL END SEPARATOR ', ') AS holdUsernames
                                    FROM descriptor_proposal_votes dpv
                                    LEFT JOIN users u1 ON dpv.UserID = u1.UserID
                                    WHERE dpv.ProposalID = ?;");
            $stmt->bind_param('i', $proposal_id);
            $stmt->execute();
            $voteResult = $stmt->get_result();
            $voteRow = $voteResult->fetch_assoc();

            ?>
            <div class="proposal-box">
                <h2>Vote</h2>
                <?php if ($loggedIn) { ?>
                    <select id="vote">
                        <option value="unvoted" <?php if ($userVote === "unvoted") echo 'selected="selected"'; ?>>Select a vote</option>
                        <option value="yes" <?php if ($userVote === "yes") echo 'selected="selected"'; ?>>Yes</option>
                        <option value="no" <?php if ($userVote === "no") echo 'selected="selected"'; ?>>No</option>
                        <option value="hold" <?php if ($userVote === "hold") echo 'selected="selected"'; ?>>Hold</option>
                    </select>
                <?php } ?>

                <hr>
                <b class="upvotes">yes (<?php echo $row["upvotes"]?>): </b> <span class="user"><?php echo $voteRow['upvoteUsernames']; ?></span>
                <hr>
                <b class="downvotes">no (<?php echo $row["downvotes"]?>): </b> <span class="user"><?php echo $voteRow['downvoteUsernames']; ?></span>
                <hr>
                <b class="downvotes">hold (<?php echo $row["holds"]?>): </b> <span class="user"><?php echo $voteRow['holdUsernames']; ?></span>
            </div>
        </div>
    </div>
</div>
<br>
<hr>
<br>
<div class="flex-child column-when-mobile">
    Comments (<?php echo $commentCount; ?>)<br>
    <?php if ($proposal["Status"] !== "pending") echo "<span class='subText'>Comments are disabled for proposals with an outcome.</span><br>"; ?><br>
    <div class="flex-container commentContainer" style="width:100%;">

        <?php if($loggedIn && ($proposal["Status"] === "pending" || $userName === "moonpoint")) { ?>
            <div class="flex-child commentComposer">
                <form>
                    <textarea id="commentForm" name="commentForm" placeholder="Write your comment here!" value="" autocomplete='off'></textarea>
                    <a href="/rules/" target="_blank" rel="noopener noreferrer"><i class="icon-book"></i> Rules</a>
                    <input type='button' name="commentSubmit" id="commentSubmit" value="Post" onclick="submitComment()" />
                </form>
            </div>
        <?php } ?>

        <?php
        $stmt = $conn->prepare("SELECT CommentID, dpc.UserID, Comment, Vote, dpc.Timestamp FROM descriptor_proposal_comments dpc LEFT JOIN descriptor_proposal_votes dpv on dpc.UserID = dpv.UserID WHERE dpc.ProposalID = ? ORDER BY dpc.Timestamp DESC");
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows != 0) {
            while ($row = $result->fetch_assoc()) {
                $is_blocked = 0;

                if ($loggedIn) {
                    $stmt_relation_to_profile_user = $conn->prepare("SELECT * FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 2");
                    $stmt_relation_to_profile_user->bind_param("ii", $userId, $row["UserID"]);
                    $stmt_relation_to_profile_user->execute();
                    $is_blocked = $stmt_relation_to_profile_user->get_result()->num_rows > 0;
                }

                ?>
                <div class="flex-container flex-child commentHeader">
                    <div class="flex-child <?php if ($is_blocked) echo "faded"; ?>" style="height:24px;width:24px;">
                        <a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
                    </div>
                    <div class="flex-child <?php if ($is_blocked) echo "faded"; ?>">
                        <a href="/profile/<?php echo $row["UserID"]; ?>"><?php echo GetUserNameFromId($row["UserID"], $conn); ?></a>
                        <?php if (isset($row["Vote"])) {
                            $vote = $row["Vote"];
                            $colors = [
                                "yes" => "#51DF60",
                                "no" => "#FF9090",
                                "hold" => "#9EAEEA"
                            ];
                            $color = $colors[$vote] ?? "#000000";
                            ?>

                            voted <span style="color: <?php echo $color; ?>;"><b><?php echo $vote; ?></b></span>
                        <?php } ?>
                    </div>
                    <div class="flex-child" style="margin-left:auto;">
                        <?php
                        if ($loggedIn && $userName == "moonpoint") { ?>
                            <i class="icon-magic scrubComment" style="color:#f94141;cursor: pointer;" value="<?php echo $row["CommentID"]; ?>"></i>
                        <?php }
                        if ($row["UserID"] == $userId) { ?>
                            <i class="icon-remove removeComment" style="color:#f94141;" value="<?php echo $row["CommentID"]; ?>"></i>
                        <?php }
                        echo GetHumanTime($row["Timestamp"]); ?>
                    </div>
                </div>
                <div class="flex-child comment" style="min-width:0;overflow: hidden;">
                    <?php
                    if (!$is_blocked)
                        echo "<p>" . ParseCommentLinks($conn, nl2br(htmlspecialchars($row["Comment"], ENT_COMPAT, "ISO-8859-1"))) . "</p>";
                    else
                        echo "<p>[blocked comment]</p>";
                    ?>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>

<script>
    function submitComment(){
        var text = $('#commentForm').val();

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                location.reload();
            }
        };

        if (text.length > 3 && text.length < 8000){
            $('#commentSubmit').prop('disabled', true);
            xhttp.open("POST", "SubmitComment.php", true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send("pID=" + <?php echo $proposal_id; ?> + "&comment=" + encodeURIComponent(text));
        }
    }

    $("#vote").change(function() {
        var selectedValue = $(this).val();

        $.ajax({
            type: "POST",
            url: "SubmitVote.php",
            data: {
                proposalID: <?php echo $proposal_id; ?>,
                vote: selectedValue
            },
            dataType: "json",
            success: function() {
                location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Error submitting vote:', error);
            }
        });
    });

    $("#changeStatus").change(function() {
        var status = $(this).val();

        $.ajax({
            type: "POST",
            url: "ChangeStatus.php",
            data: {
                proposalID: <?php echo $proposal_id; ?>,
                newStatus: status
            },
            dataType: "json",
            success: function() {
                location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Error submitting status:', error);
            }
        });
    });

    $('#commentForm').keydown(function (event) {
        if ((event.keyCode == 10 || event.keyCode == 13) && event.ctrlKey)
            submitComment();
    });

    $(".removeComment").click(function(event){
        var $this = $(this);

        if (!confirm("Are you sure you want to remove this comment?")) {
            return;
        }

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText);
                location.reload();
            }
        };

        xhttp.open("POST", "RemoveComment.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("pID=" + <?php echo $proposal_id; ?> + "&cID=" + $this.attr('value'));
    });

    $(".scrubComment").click(function(event){
        var $this = $(this);

        if (!confirm("Are you sure you want to scrub this comment?")) {
            return;
        }

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText);
                location.reload();
            }
        };

        xhttp.open("POST", "ScrubComment.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("pID=" + <?php echo $proposal_id; ?> + "&cID=" + $this.attr('value'));
    });
</script>


<?php
    require '../../footer.php';
?>