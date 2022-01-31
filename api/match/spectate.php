<?php

include('../../init.php');

// Vérification du mode test
$test = isset($_REQUEST['test']);

// vérification du paramètre match et action
if (!isset($_REQUEST['action']))
	exit("no action");
if (!isset($_REQUEST['match']))
	exit("no match");
$action = $_REQUEST['action'];
$matchId = $_REQUEST['match'];

// vérification de l'existence du match et récupération
$result = sendRequest("SELECT MATCHES.*, U1.name AS opponent1Name, CU1.trophies as opponent1Trophies, U2.name AS opponent2Name, CU2.trophies as opponent2Trophies FROM `MATCHES` JOIN USERS U1 ON U1.id=opponent1 JOIN CARDSUSERS CU1 ON CU1.id=U1.id JOIN USERS U2 ON U2.id=opponent2 JOIN CARDSUSERS CU2 ON CU2.id=U2.id WHERE MATCHES.id = '".$matchId."'");
if ($result->num_rows === 0)
	exit("not");
$resultMatch = $result->fetch_assoc();

$end = $resultMatch['end']==1;
include('../../match.php');
$match = Match::fromStd(json_decode($resultMatch['infos']));
if ($resultMatch['opponent1'] == -807)
    $resultMatch['opponent1Name'] .= ' ('.BOT_LEVELS[$match->botDifficult].')';
if ($resultMatch['opponent2'] == -807)
    $resultMatch['opponent2Name'] .= ' ('.BOT_LEVELS[$match->botDifficult].')';

// réponse
$response = new stdClass();
$response->action = 'spectate';
$response->time = time();
try {
    switch ($action) {
        case 'get':
        	$response->match = $match->toStdClient(NULL);
        	$response->match->names = array($resultMatch['opponent1Name'], $resultMatch['opponent2Name']);
        	$response->match->trophies = array($resultMatch['opponent1Trophies'], $resultMatch['opponent2Trophies']);
        	$response->match->ended = $resultMatch['end']=='1';
        	break;
    	case 'update':
    	    if (!isset($_REQUEST['from']))
    	        exit('no from');
			$response->actions = array_slice($match->toStdClient(NULL)->history, intval($_REQUEST['from']));
			$response->from = intval($_REQUEST['from']);
			break;
		default:
			$response->error = 'unknow action';
	}
} catch (Exception $e) {
	$response->error = $e->getMessage();
}

// envoi de la réponse
echo(json_encode($response, isset($_REQUEST['pretty'])?JSON_PRETTY_PRINT:0));

?>