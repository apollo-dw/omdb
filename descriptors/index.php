<?php
    $PageTitle = "Descriptors";
    require "../base.php";
    require '../header.php';

    function generateTreeHTML($tree) {
        $html = '<ul>';
        foreach ($tree as $node) {
            $html .= '<li><a href="../descriptor/?id=' . $node['descriptorID'] . '">' . $node['name'] . '</a>';
            if (!empty($node['ShortDescription'])) {
                $html .= ' <br><span class="subText">' . $node['ShortDescription'] . ' </span> ';
            }
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

<h1 id="heading">Descriptors</h1>

<?php
    $stmt = $conn->prepare("SELECT descriptorID, name, ShortDescription, parentID FROM descriptors");
    $stmt->execute();
    $result = $stmt->get_result();
    $descriptors = $result->fetch_all(MYSQLI_ASSOC);

    $tree = buildTree($descriptors);
    echo generateTreeHTML($tree);
?>

<?php
    require '../footer.php';
?>

