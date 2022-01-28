<?php

include('init.php');
// connexion à un compte
$user = login(false, true);

// récupération du joueur
$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0)
	exit('invalid account');
$cardsUser = $result->fetch_assoc();

// vérification du paramètre id, id du tournoi
if (!isset($_REQUEST['id']))
	exit('no id');
$tournamentId = $_REQUEST['id'];

$result = sendRequest("SELECT * FROM TOURNAMENT WHERE id = '", $tournamentId, "'");
if ($result->num_rows == 0)
	exit('invalid tournament');
$tournament = $result->fetch_assoc();

if ($tournament->nbPlaces < 0)
	exit('ended tournament');

$fighters = parseFighters($fighters);

if (tournamentIncludesPlayer($fighters, $user['id']))
	exit('already in tournament');

tournamentAddPlayer($fighters, $user['id']);

echo 'success';


function tournamentIncludesPlayer($fighters, $playerId) {
	return in_array($fighters[0][0], $playerId);
}

function tournamentAddPlayer($fighters, $playerId) {
	// TODO
}

function parseFighters($fighters) {
	$fightersArray = explode(';',$fighters);
	foreach($fightersArray as &$tree) {
		$tree = explode(',',$tree);
		foreach ($tree as &$branch)
			$branch = explode('.', $branch);
	}
	return fightersArray;
}

?>
