<?php
	$PageTitle = "Project Legacy Doublechecks";
	require("../header.php");
?>

	This page just shows some lists that might be helpful for doublechecking.
	
	<hr>
	
	<style>
		td {
			padding: initial;
		}
	</style>
<?php
	$sql = "SELECT bn.NominatorID, mn.username, COUNT(*) as NomCount 
        FROM beatmapset_nominators bn 
        LEFT JOIN mappernames mn ON bn.nominatorid = mn.userid 
        GROUP BY bn.NominatorID 
        ORDER BY NomCount 
        LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Display results in a nice HTML table
echo "<table>
<tr>
<th>Username</th>
<th>Nomination Count</th>
</tr>";

// Fetch and display each row of the result set
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><a href='../profile/" . htmlspecialchars($row['NominatorID']) . "'>" . htmlspecialchars($row['username']) . "</a></td>";
    echo "<td>" . htmlspecialchars($row['NomCount']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Close the statement and connection
$stmt->close();

?>

<?php
	require("../footer.php");
?>