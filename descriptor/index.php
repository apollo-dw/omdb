<?php
    require "../base.php";
    $descriptor_id = $_GET['id'] ?? -1;

    $stmt = $conn->prepare("SELECT * FROM `descriptors` WHERE `DescriptorID` = ?;");
    $stmt->bind_param("i", $descriptor_id);
    $stmt->execute();
    $descriptor = $stmt->get_result()->fetch_assoc();

    $PageTitle = "Descriptor - " . $descriptor["Name"];
    require '../header.php';

    if (is_null($descriptor))
        die("Descriptor not found.");

    function getParentTree($descriptor, $conn) {
        if ($descriptor['ParentID'] === null) {
            return $descriptor['Name'];
        } else {
            $parentStmt = $conn->prepare("SELECT `Name`, `ParentID` FROM `descriptors` WHERE `DescriptorID` = ?;");
            $parentStmt->bind_param("i", $descriptor['ParentID']);
            $parentStmt->execute();
            $parentDescriptor = $parentStmt->get_result()->fetch_assoc();

            $parentTree = getParentTree($parentDescriptor, $conn);
            return $parentTree . ' >> ' . $descriptor['Name'];
        }
    }

    $parentTree = getParentTree($descriptor, $conn);

    echo "<h1>Descriptor - {$descriptor["Name"]}</h1>";
    echo $descriptor["ShortDescription"] . "<br>";
    echo "<span class='subText'>$parentTree</span>";
?>

<?php
    require '../footer.php';
?>
