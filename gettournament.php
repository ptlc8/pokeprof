<?php
include("init.php");

if (isset($_REQUEST['id'])) {
	$tournamentId=$_REQUEST['id'];
} else {
	exit("L iditenfiant du tournoi n est pas donne.");
}
	
// récupération du tournoi
$result = sendRequest("SELECT * FROM TOURNAMENT WHERE id = '", $tournamentId, "'");
if ($result->num_rows === 0) {
	exit("Ce tournoi n existe pas.");
}
$tournament = $result->fetch_assoc();

echo json_encode($tournament);

?>
