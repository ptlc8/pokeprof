<?php

include("init.php");
include("functiontournament.php");
$user=login(false,false);

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

$tournament['fighters']=prettyTable4Tournament(parseFighters($tournament['fighters']));

$tournament['names']=namesFighters(recupFighters(parseFighters($tournament['fighters'])[0]));

if ((isset($user['name']))&&(in_array($user['name'], $tournament['names']))) {
    $tournament['include']=true;
} else {
    $tournament['include']=false;
}

echo json_encode($tournament);

?>
