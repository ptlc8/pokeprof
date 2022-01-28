<?php

//fonction pour faire des tableaux corrects, Léo
function prettyTable4Tournament ($textFighters) {
	if (($textFighters==null)||(!isset($textFighters))) {
		return(null);	
	}
	$trees=array();
	$trees2=array();
	$trees3=array();
	$trees=explode(';',$textFighters);
	print_r($trees);
	for ($i=0; $i<count($trees); $i++) {
		$trees2[$i]=explode(',',$trees);
		print_r($trees2);
		for ($j=0; $j<count($trees2[$i]); $j++) {
			$trees3[$i][$j]=explode('.',$trees2[$i]);
		}
	}
	return($trees3);
}

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

print_r(prettyTable4Tournament($tournament['fighters']));

echo json_encode($tournament);

?>
