<?php
include("init.php");

$tournamentId=$_REQUEST['id'];

// récupération du tournoi
$result = sendRequest("SELECT * FROM TOURNAMENT WHERE id = '", $tournamentId, "'");
if ($result->num_rows === 0) {
	exit("<a href=".">Ce tournoi n'existe pas.</a>");
}
$tournament = $result->fetch_assoc();

echo json_encode($tournament);

?>
