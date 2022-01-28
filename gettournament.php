<?php

//fonction pour faire des tableaux corrects, Léo
/*
function prettyTable4Tournament ($textFighters) {
	if (($textFighters==null)||(!isset($textFighters))) {
		return(null);	
	}
	$tree=array();
	$j=0;
	$i=0;
	$tree[0][0]=$textFighters[0];
	while (count($textFighters)>$i) {
		$k=0;
		$l=0;
		while (
		for ($l=0; $l<=$j; $l++) {
			if (
		}
		$i++;
	}
	if ($textFighters[$i]==',') {
		return($textFighters);	
	}
}
*/

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
