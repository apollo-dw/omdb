<?php
$map_id = $_GET['id'] ?? -1;
$PageTitle = "Vote Descriptors";
require "../../base.php";
require '../../header.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$stmt = $conn->prepare("SELECT * FROM `beatmaps` b JOIN beatmapsets s on b.SetID = s.SetID WHERE `BeatmapID` = ?;");
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
        $isUsable = $node['Usable'];

        $class = $isUsable ? '' : 'class="unusable"';

        $html .= '<li class="descriptor" data-descriptor-id="' . $descriptorID . '"><span ' . $class . ' >' . $node['name'] . '</span>';
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

$stmt = $conn->prepare("SELECT DescriptorID, Vote FROM descriptor_votes WHERE UserID = ? AND BeatmapID = ?");
$stmt->bind_param('ii', $userId, $map_id);
$stmt->execute();
$voteResult = $stmt->get_result();

$userVotes = array();
while ($voteRow = $voteResult->fetch_assoc())
    $userVotes[$voteRow['DescriptorID']] = $voteRow['Vote'];
?>

    <style>
        .popover {
            display: none;
            position: absolute;
            background-color: darkslategray;
            border: 1px solid #ccc;
            padding: 10px;
            z-index: 1000;
            font-size: 12px;
            overflow-y: auto;
            max-height: 30em;
            margin: 0.5em;
        }

        ul {
            padding: 0;
            margin: 0;
        }

        ul li {
            margin-left: 1em;
        }

        .descriptor span{
            cursor: pointer;
            color: white;
        }

        .descriptor span:hover{
            text-decoration: underline;
        }

        .unusable {
            color: grey !important;
            cursor: revert !important;
        }

        .unusable:hover {
            text-decoration: none !important;
        }

        .descriptor-box {
            border:1px solid white;
            margin: 0.5em;
            padding: 1em;
        }

        .descriptor-box h2 {
            margin: 0;
        }

        .descriptor-box .actions {
            font-size: 1.5em;
        }

        .descriptor-box .actions i {
            margin-left: 0.25em;
            cursor:pointer;
            color: grey;
        }

        i.voted {
            color: white !important;
        }

        .descriptor-box .user {
            margin-left: 0.1em;
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

		<input type="text" id="searchInput" placeholder="Search...">
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

        <a href="../../descriptors/">View all descriptors</a>

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
                $stmt = $conn->prepare("SELECT
										GROUP_CONCAT(CASE WHEN dv.Vote = 1 THEN u1.Username ELSE NULL END SEPARATOR ', ') AS upvoteUsernames,
										GROUP_CONCAT(CASE WHEN dv.Vote = 0 THEN u2.Username ELSE NULL END SEPARATOR ', ') AS downvoteUsernames
										FROM descriptor_votes dv
										LEFT JOIN users u1 ON dv.UserID = u1.UserID
										LEFT JOIN users u2 ON dv.UserID = u2.UserID
										WHERE dv.BeatmapID = ? AND dv.DescriptorID = ?;");
                $stmt->bind_param('ii', $map_id, $row["DescriptorID"]);
                $stmt->execute();
                $voteResult = $stmt->get_result();
                $voteRow = $voteResult->fetch_assoc();

                ?>
                <div class="descriptor-box" data-descriptor-id="<?php echo $row["DescriptorID"]; ?>">
                    <h2><?php echo $row["Name"]?></h2>
                    <span class="subText"><?php echo $row["ShortDescription"]?></span> <br>
                    <?php if ($loggedIn) { ?>
                        <div class="actions">
                            <i class="icon-thumbs-up<?php echo isset($userVotes[$row["DescriptorID"]]) && ($userVotes[$row["DescriptorID"]] === 1) ? ' voted' : ''; ?>"></i>
                            <i class="icon-thumbs-down<?php echo isset($userVotes[$row["DescriptorID"]]) && ($userVotes[$row["DescriptorID"]] === 0) ? ' voted' : ''; ?>"></i>
                        </div>
                    <?php } ?>
                    <hr>
                    <b class="upvotes">upvotes (<?php echo $row["upvotes"]?>): </b> <span class="user"><?php echo $voteRow['upvoteUsernames']; ?></span>
                    <hr>
                    <b class="downvotes">downvotes (<?php echo $row["downvotes"]?>): </b> <span class="user"><?php echo $voteRow['downvoteUsernames']; ?></span>
                </div>
            <?php } ?>
        </div>
    </div>

<script>
	console.log("Hello");
    const searchInput = document.getElementById('searchInput');
	console.log(searchInput);
    const descriptorTreePopover = document.getElementById('descriptorTreePopover');
    const descriptorBoxContainer = document.getElementById('descriptor-box-container');
	
document.addEventListener('DOMContentLoaded', function () {
    searchInput.addEventListener('focus', () => {
        descriptorTreePopover.style.display = 'block';
    });

    document.addEventListener('click', (event) => {
        if (!descriptorTreePopover.contains(event.target) && event.target !== searchInput) {
            descriptorTreePopover.style.display = 'none';
        }
    });

    searchInput.addEventListener('input', () => {
        const searchKeyword = searchInput.value.toLowerCase();
        console.log(searchKeyword);
        const listItems = descriptorTreePopover.querySelectorAll('li');
        listItems.forEach(li => {
            const text = li.textContent.toLowerCase();
            li.style.display = text.includes(searchKeyword) ? '' : 'none';
        });
    });

    // Delegate click event for '.descriptor' inside document
    document.addEventListener('click', function(event) {
        const target = event.target.closest('.descriptor');
        if (!target) return;

        if (target.querySelector('.unusable')) {
            event.stopPropagation();
            return;
        }

        const descriptorID = target.dataset.descriptorId;
        handleDescriptorClick(descriptorID);
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        event.stopPropagation();
    });

    // Initialize vote icon click handlers for existing descriptor boxes
    document.querySelectorAll('.descriptor-box').forEach(descriptorBox => {
        const descriptorID = descriptorBox.dataset.descriptorId;
        const upvoteIcon = descriptorBox.querySelector('.icon-thumbs-up');
        const downvoteIcon = descriptorBox.querySelector('.icon-thumbs-down');

        upvoteIcon.addEventListener('click', () => {
            if (upvoteIcon.classList.contains('voted')) {
                upvoteIcon.classList.remove('voted');
            } else {
                downvoteIcon.classList.remove('voted');
                upvoteIcon.classList.add('voted');
            }
            submitVote(descriptorID, 1);
        });

        downvoteIcon.addEventListener('click', () => {
            if (downvoteIcon.classList.contains('voted')) {
                downvoteIcon.classList.remove('voted');
            } else {
                upvoteIcon.classList.remove('voted');
                downvoteIcon.classList.add('voted');
            }
            submitVote(descriptorID, 0);
        });
    });

    // Functions

    function handleDescriptorClick(descriptorID) {
        fetch(`GetDescriptor.php?descriptorID=${encodeURIComponent(descriptorID)}`)
            .then(response => response.json())
            .then(response => {
                if (response) {
                    if (!isDescriptorBoxExist(descriptorID)) {
                        createDescriptorBox(response);
                    }
                    submitVote(descriptorID, 1);
                    descriptorTreePopover.style.display = descriptorTreePopover.style.display === 'none' || !descriptorTreePopover.style.display ? 'block' : 'none';
                }
            })
            .catch(error => console.error(error));
    }

    function isDescriptorBoxExist(descriptorID) {
        return !!document.querySelector(`.descriptor-box[data-descriptor-id="${descriptorID}"]`);
    }

    function createDescriptorBox(descriptorData) {
        const descriptorBox = document.createElement('div');
        descriptorBox.className = 'descriptor-box';
        descriptorBox.dataset.descriptorId = descriptorData.DescriptorID;

        const h2 = document.createElement('h2');
        h2.textContent = descriptorData.Name;
        descriptorBox.appendChild(h2);

        const subText = document.createElement('span');
        subText.className = 'subText';
        subText.textContent = descriptorData.ShortDescription;
        descriptorBox.appendChild(subText);

        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'actions';

        const upvoteIcon = document.createElement('i');
        upvoteIcon.className = 'icon-thumbs-up voted';

        const downvoteIcon = document.createElement('i');
        downvoteIcon.className = 'icon-thumbs-down';

        actionsDiv.appendChild(upvoteIcon);
        actionsDiv.appendChild(downvoteIcon);

        descriptorBox.appendChild(actionsDiv);

        descriptorBox.appendChild(document.createElement('hr'));

        const upvotesB = document.createElement('b');
        upvotesB.className = 'upvotes';
        upvotesB.textContent = `upvotes (0): `;
        descriptorBox.appendChild(upvotesB);

        const upvoteUsersSpan = document.createElement('span');
        upvoteUsersSpan.className = 'user';
        descriptorBox.appendChild(upvoteUsersSpan);

        descriptorBox.appendChild(document.createElement('hr'));

        const downvotesB = document.createElement('b');
        downvotesB.className = 'downvotes';
        downvotesB.textContent = `downvotes (0): `;
        descriptorBox.appendChild(downvotesB);

        const downvoteUsersSpan = document.createElement('span');
        downvoteUsersSpan.className = 'user';
        descriptorBox.appendChild(downvoteUsersSpan);

        descriptorBoxContainer.appendChild(descriptorBox);

        upvoteIcon.addEventListener('click', () => {
            if (upvoteIcon.classList.contains('voted')) {
                upvoteIcon.classList.remove('voted');
            } else {
                downvoteIcon.classList.remove('voted');
                upvoteIcon.classList.add('voted');
            }
            submitVote(descriptorData.DescriptorID, 1);
        });

        downvoteIcon.addEventListener('click', () => {
            if (downvoteIcon.classList.contains('voted')) {
                downvoteIcon.classList.remove('voted');
            } else {
                upvoteIcon.classList.remove('voted');
                downvoteIcon.classList.add('voted');
            }
            submitVote(descriptorData.DescriptorID, 0);
        });
    }

    function updateDescriptorBox(descriptorID, voteData) {
        const descriptorBox = document.querySelector(`.descriptor-box[data-descriptor-id="${descriptorID}"]`);
        if (!descriptorBox) return;

        const upvotesElem = descriptorBox.querySelector('.upvotes');
        const downvotesElem = descriptorBox.querySelector('.downvotes');
        const upvoteUsernamesElem = upvotesElem.nextElementSibling;
        const downvoteUsernamesElem = downvotesElem.nextElementSibling;

        if (voteData.upvotes == null && voteData.downvotes == null) {
            descriptorBox.remove();
            return;
        }

        upvotesElem.innerHTML = `upvotes (${voteData.upvotes}):`;
        upvoteUsernamesElem.textContent = voteData.upvoteUsernames.join(', ');

        downvotesElem.innerHTML = `downvotes (${voteData.downvotes}):`;
        downvoteUsernamesElem.textContent = voteData.downvoteUsernames.join(', ');
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
});
</script>

<?php
require '../../footer.php';
?>