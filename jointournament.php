<?php

include('init.php');
include("functiontournament.php");

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

if ($tournament['nbPlaces'] < 0)
	exit('ended tournament');

$fighters = parseFighters($tournament['fighters']);

if (isset($_REQUEST['player'])) {
    if (tournamentIncludesPlayer($fighters, $_REQUEST['player'])) {
        $tournament['nbPlaces']=tournamentDelPlayer($fighters, $_REQUEST['player'], $tournament['id'], $tournament['nbPlaces']);
    } else {
        exit("this player isn't in the tournament");
    }
} else if (tournamentIncludesPlayer($fighters, $user['id'])) {
	if (isset($_REQUEST['del'])) {
        $tournament['nbPlaces']=tournamentDelPlayer($fighters, $user['id'], $tournament['id'], $tournament['nbPlaces']);
	} else {
		exit('already in tournament');
	}
} else {
	if (isset($_REQUEST['add'])) {
		$tournament['nbPlaces']=tournamentAddPlayer($fighters, $user['id'], $tournament['id'], $tournament['nbPlaces']);
	}
	if (isset($_REQUEST['del'])) {
		exit('not in tournament');
	}
}

$tournament['fighters']=prettyTable4Tournament($fighters);

$tournament['names']=namesFighters(recupFighters(parseFighters($tournament['fighters'])[0]));

echo json_encode($tournament);

//echo 'success';

?>
