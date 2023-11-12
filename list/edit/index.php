<?php
    $PageTitle = "List creation";
    require "../../header.php";

    $id = $_GET["id"] ?? "";
    $stmt = $conn->prepare("SELECT * FROM `lists` WHERE `ListID` = ?;");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $list = $stmt->get_result()->fetch_assoc();

    if (!$loggedIn) {
        die("You have to be logged in to do list stuff");
    }

    $isNewList = is_null($list);
    if (!$isNewList) {
        if ($list["UserID"] != $userId) {
            die("Not your list");
        }
        $stmt = $conn->prepare("SELECT * FROM `list_items` WHERE `ListID` = ?;");
        $stmt->bind_param("i", $list["ListID"]);
        $stmt->execute();
        $listItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
?>

<style>
    .container {
        width: 100%;
        background-color: darkslategray;
        padding: 1.5em;
        box-sizing: border-box;
    }

    .container td {
        padding: 0.5em;
    }

    textarea {
        border: 1px solid white;
        background-color: #203838;
        color: white;
        width: 100%;
    }

    .container select{
        border: 1px solid white;
        background-color: #203838;
    }

    .draggable-container {
        display: flex;
        flex-direction: column;
        width: 100%;
        box-sizing: border-box;
    }

    .draggable {
        margin: 0.5em 0 0;
        border: 1px solid #999;
        cursor: grab;
        box-sizing: border-box;
        min-height: 12em;
        display:flex;
        align-items: center;
        padding-right: 1em;
        padding-top: 1em;
        padding-bottom: 1em;
    }

    .draggable>div {
        margin-left:1em;
    }

    .draggable .icon-reorder, .draggable .icon-remove {
        font-size: 2em;
    }

    .draggable .icon-remove {
        color: red;
    }

    .dragging {
        opacity: 0.5;
    }
</style>

<?php if($isNewList) { ?>
    <h1>New list</h1>
<?php } else { ?>
    <h1>Edit list</h1>
<?php } ?>

<form>
    <div class="container">
        <label>List title:</label> <br>
        <input autocomplete="off" name="ListTitle" id="ListTitle" value="<?php echo $list["Title"] ?? ""; ?>" required/><br><br>
        <label>Description:</label>
        <textarea name="ListDescription" id="ListDescription"><?php echo $list["Description"] ?? ""; ?></textarea> <br><br>
    </div>

    <div class="flex-container">
        <div id="container" class="draggable-container" style="width:80%;">
                <?php
                if (!$isNewList) {
                    foreach ($listItems as $index => $listItem) {
                        list($imageUrl, $title) = getListItemDisplayInformation($listItem, $conn);
                        ?>
                        <div class="draggable alternating-bg" draggable="true" data-type="<?php echo $listItem["Type"]; ?>" data-id="<?php echo $listItem["SubjectID"]; ?>" >
                            <div>
                                <i class="icon-reorder"></i>
                            </div>
                            <div>
                                #<?php echo $index + 1; ?>
                            </div>
                            <div style="text-align: center; width: 8em;">
                                <a href="/mapset"><img src="<?php echo $imageUrl; ?>" class="diffThumb" style="height: 8em; width: 8em;" onerror="this.onerror=null; this.src='../../../charts/INF.png';" /></a><br>
                                <span class="subText"><?php echo $title; ?></span>
                            </div>
                            <div style="flex-grow: 1; box-sizing: border-box;">
                                <textarea class="description"><?php echo $listItem['Description']; ?></textarea>
                            </div>
                            <div>
                                <span class="icon-remove"></span>
                            </div>
                        </div>
                    <?php }
                }
                ?>
        </div>
        <div style="width:20%;margin: 0.5em 0 0.5em 0.5em;padding: 1em;min-height:24em;">
            Add new item <hr>
            <label>Type:</label>
            <select id="newType" name="newType">
                <option value="person">Person</option>
                <option value="beatmap">Beatmap</option>
                <option value="beatmapset">Beatmapset</option>
            </select> <br><br>
            <label>ID:</label> <br>
            <input id="newId" name="newId"/> <br><br>
            <input type="button" id="addNewButton" value="Add new item" /> <br><br>
            <span class="subText">Only ranked and loved difficulties work on lists for the time being.</span>
        </div>
    </div>

    <div class="container" style="margin-top: 0.5em;">
        <div>
            Lists are subject to the <a href="/rules/">OMDB rules and code of conduct.</a> Do not misuse this feature in bad faith.
        </div> <br>
        <input type="submit" id="submitButton" value="Submit" />
    </div>
</form>

<script>
    const container = document.getElementById("container");
    const addNewButton = document.getElementById("addNewButton");
    const newTypeSelect = document.getElementById("newType");
    const newIdInput = document.getElementById("newId");
    const submitButton = document.getElementById("submitButton");
    let draggingElement = null;

    addNewButton.addEventListener("click", () => {
        const type = newTypeSelect.value;
        const id = newIdInput.value;

        fetch(`GetListItemData.php?type=${type}&id=${id}`)
            .then((response) => response.json())
            .then((data) => {
                console.log(data);
                if (data.error) {
                    return;
                }

                const newContainer = document.createElement("div");
                newContainer.classList.add("draggable", "alternating-bg");
                newContainer.dataset.type = type;
                newContainer.dataset.id = id;
                newContainer.draggable = true;

                newContainer.innerHTML = `
                <div>
                    <i class="icon-reorder"></i>
                </div>
                <div>
                    #${container.childElementCount + 1}
                </div>
                <div style="text-align:center;width:8em;">
                    <img src="${data.imageUrl}" class="diffThumb" style="height: 8em; width: 8em;" onerror="this.onerror=null; this.src='../../../charts/INF.png';" /><br>
                    <span class="subText">${data.itemTitle}</span>
                </div>
                <div style="flex-grow: 1; box-sizing: border-box;">
                    <textarea class="description"></textarea>
                </div>
                <div>
                    <span><i class="icon-remove"></i></span>
                </div>`;

                container.appendChild(newContainer);

                $('.description')
                    .on('focus', function(e) {
                        $(this).closest('.draggable').attr("draggable", false);
                    })
                    .on('blur', function(e) {
                        $(this).closest('.draggable').attr("draggable", true);
                    });

                newContainer.querySelector(".icon-remove").addEventListener("click", () => {
                    if(confirm("Are you want to delete this list item?")){
                        container.removeChild(newContainer);
                        updateIndices();
                    }
                });

                updateIndices();
            })
            .catch((error) => {
                console.error("Error fetching data:", error);
            });
    });

    $('.description')
        .on('focus', function(e) {
            $(this).closest('.draggable').attr("draggable", false);
        })
        .on('blur', function(e) {
            $(this).closest('.draggable').attr("draggable", true);
        });

    container.addEventListener("dragstart", (e) => {
        if (e.target.classList.contains("draggable")) {
            draggingElement = e.target;
            e.target.classList.add("dragging");
        }
    });

    container.addEventListener("dragover", (e) => {
        const afterElement = getDragAfterElement(container, e.clientY);
        const currentElement = draggingElement;
        if (afterElement == null) {
            container.appendChild(currentElement);
        } else {
            container.insertBefore(currentElement, afterElement);
        }
    });

    container.addEventListener("dragend", () => {
        draggingElement.classList.remove("dragging");
        draggingElement = null;
        updateIndices();
    });

    document.querySelectorAll('.draggable').forEach((existingContainer) => {
        const removeButton = existingContainer.querySelector('.icon-remove');

        removeButton.addEventListener('click', () => {
            if (confirm('Are you sure you want to delete this list item?')) {
                existingContainer.remove();
                updateIndices();
            }
        });
    });

    $('form').submit(function(e) {
        e.preventDefault();
        const draggableElements = container.querySelectorAll(".draggable");
        const data = [];

        draggableElements.forEach((element, index) => {
            const type = element.dataset.type;
            const id = element.dataset.id;
            const description = element.querySelector("textarea").value;
            const order = index + 1;

            data.push({
                type: type,
                id: id,
                description: description,
                order: order
            });
        });

        if (data.length === 0) {
            return;
        }

        window.onbeforeunload = null;

        const listTitle = document.getElementById("ListTitle").value;
        const listDescription = document.getElementById("ListDescription").value;
        const postData = {
            <?php if (!$isNewList) {
                echo "listId: {$id},";
            } ?>
            listTitle: listTitle,
            listDescription: listDescription,
            items: data
        };

        fetch("Submit.php", {
            method: "POST",
            body: JSON.stringify(postData),
            headers: {
                "Content-Type": "application/json"
            }
        })
            .then((response) => response.json())
            .then((response) => {
                console.log(response);
                window.location.href = "../?id=" + response.id;
            })
            .catch((error) => {
                console.error("Error:", error);
            });
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll(".draggable:not(.dragging)")];

        return draggableElements.reduce(
            (closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset, element: child };
                } else {
                    return closest;
                }
            },
            { offset: Number.NEGATIVE_INFINITY }
        ).element;
    }

    function updateIndices() {
        const draggableElements = container.querySelectorAll(".draggable");
        draggableElements.forEach((element, index) => {
            const indexElement = element.querySelector("div:nth-child(2)");
            indexElement.textContent = `#${index + 1}`;
        });

        // Just because this function happens to be run quite often, lets also use it to
        // determine whether to show the "Are you sure you want to navigate away" thing
        if (draggableElements.length > 0) {
            window.onbeforeunload = function() {
                return true;
            };
        } else {
            window.onbeforeunload = null;
        }
    }
</script>


<?php
require '../../footer.php';
?>

