<?php

include('../../init.php');
// connexion à un compte
$user = login(false, true);

// récupération du joueur
$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0) {
	header("Location: .");
	exit("<a href=".">Veuillez d'abord aller à la page suivante</a>");
}
$cardsUser = $result->fetch_assoc();

// Vérification du mode test
$test = isset($_REQUEST['test']);

// vérification du paramètre action
if (!isset($_REQUEST['action']))
	exit("no action");
$action = $_REQUEST['action'];

// vérification de la présence dans un match et récupération
$result = sendRequest("SELECT MATCHES.*, USERS.name AS opponentName, CARDSUSERS.trophies as opponentTrophies, USERS.id AS opponentId FROM `MATCHES` JOIN USERS ON (USERS.id = opponent1 OR USERS.id = opponent2) JOIN CARDSUSERS ON (CARDSUSERS.id = USERS.id) AND USERS.id != '".$cardsUser['id']."' WHERE opponent1 = '".$cardsUser['id']."' OR opponent2 = '".$cardsUser['id']."' ORDER BY MATCHES.id DESC");
if ($result->num_rows === 0)
	exit("not");
$resultMatch = $result->fetch_assoc();
if ($resultMatch['opponent1'] == $cardsUser['id']) {
	$playerIndex = 0;
	$opponentIndex = 1;
} else {
	$playerIndex = 1;
	$opponentIndex = 0;
};
$matchId = $resultMatch['id'];
$opponentName = $resultMatch['opponentName'];
$opponentTrophies = $resultMatch['opponentTrophies'];
$end = $resultMatch['end']==1;
include('../../match.php');
$match = Match::fromStd(json_decode($resultMatch['infos']));
if ($resultMatch['opponentId'] == -807) $opponentName .= ' ('.BOT_LEVELS[$match->botDifficult].')'; // BOT_LEVELS est défini dans match.php

// récupération du contexte (cibles+id+trophées)
$playerId = intval($user['id']);
$context = array('targetsofhim'=>[],'targetsofyou'=>[],'trophies'=>[],'playerIds'=>[]);
for($i = 0; isset($_REQUEST['target'.$i.'ofhim']); $i++)
	array_push($context['targetsofhim'], $_REQUEST['target'.$i.'ofhim']=='him' ? $match->opponents[$opponentIndex] : $match->opponents[$opponentIndex]->fighters[intval($_REQUEST['target'.$i.'ofhim'])]);
for($i = 0; isset($_REQUEST['target'.$i.'ofyou']); $i++)
	array_push($context['targetsofyou'], $match->opponents[$playerIndex]->fighters[intval($_REQUEST['target'.$i.'ofyou'])]);
for ($i = 0; $i < 2; $i++) {
	array_push($context['playerIds'], $resultMatch['opponent'.($i+1)]);
	array_push($context['trophies'], $i==$playerIndex?$cardsUser['trophies']:$resultMatch['opponentTrophies']);
}

// réponse
$response = new stdClass();
$response->action = $action;
$response->time = time();
try {
	switch ($action) {
		case 'get':
			$response->match = $match->toStdClient($playerIndex);
			$response->match->names = array($playerIndex==0?$user['name']:$opponentName, $playerIndex==1?$user['name']:$opponentName);
			$response->match->trophies = array($playerIndex==0?$cardsUser['trophies']:$opponentTrophies, $playerIndex==1?$cardsUser['trophies']:$opponentTrophies);
			$response->match->ended = $resultMatch['end']=='1';
			if ($match->opponents[$playerIndex]->historyIndex != count($match->history)) {
				$match->opponents[$playerIndex]->historyIndex = count($match->history);
				save($match, $matchId);
			}
			break;
		case 'update':
			$response->actions = array_slice($match->toStdClient($playerIndex)->history, $match->opponents[$playerIndex]->historyIndex);
			if ($match->opponents[$playerIndex]->historyIndex != count($match->history)) {
				$match->opponents[$playerIndex]->historyIndex = count($match->history);
				save($match, $matchId);
			}
			break;
		case 'playcard':
			if (!isset($_REQUEST['index']))
				exit('need index');
			$match->playCard($playerIndex, intval($_REQUEST['index']), $context);
			$response->actions = array_slice($match->toStdClient($playerIndex)->history, $match->opponents[$playerIndex]->historyIndex);//[];
			$match->opponents[$playerIndex]->historyIndex = count($match->history);
			save($match, $matchId);
			break;
		case 'attack':
			if (!isset($_REQUEST['cardindex'], $_REQUEST['scriptindex']))
				exit('need indices');
			$match->attack($playerIndex, intval($_REQUEST['cardindex']), intval($_REQUEST['scriptindex']), $context);
			$response->actions = array_slice($match->toStdClient($playerIndex)->history, $match->opponents[$playerIndex]->historyIndex);//[];
			$match->opponents[$playerIndex]->historyIndex = count($match->history);
			save($match, $matchId);
			break;
		case 'endturn':
			$match->endTurn($playerIndex, $context);
			$response->actions = array_slice($match->toStdClient($playerIndex)->history, $match->opponents[$playerIndex]->historyIndex);//[];
			$match->opponents[$playerIndex]->historyIndex = count($match->history);
			save($match, $matchId);
			break;
		case 'giveup':
			$match->giveUp($playerIndex, $context);
			$response->actions = array_slice($match->toStdClient($playerIndex)->history, $match->opponents[$playerIndex]->historyIndex);//[];
			$match->opponents[$playerIndex]->historyIndex = count($match->history);
			save($match, $matchId);
			break;
		default:
			$response->error = 'unknow action';
	}
} catch (Exception $e) {
	$response->error = $e->getMessage();
}

// test de fin de match
foreach ($match->history as $action) {
	if ($action->name=='endgame') {
		if (!$resultMatch['end']) {
			sendRequest("UPDATE `MATCHES` SET `end` = TRUE WHERE id = '".$matchId."'");
			$end = true;
			$winnerId = $resultMatch['opponent'.($action->winner+1)];
			$loserId = $resultMatch['opponent'.(($action->winner==0?1:0)+1)];
			$winner = sendRequest("SELECT CARDSUSERS.*, USERS.name FROM CARDSUSERS JOIN USERS ON USERS.id = CARDSUSERS.id WHERE CARDSUSERS.id = '", $winnerId, "'")->fetch_assoc();
			$loser = sendRequest("SELECT CARDSUSERS.*, USERS.name FROM CARDSUSERS JOIN USERS ON USERS.id = CARDSUSERS.id WHERE CARDSUSERS.id = '", $loserId, "'")->fetch_assoc();
			sendRequest("UPDATE `CARDSUSERS` SET `rewardLevel` = '", intval($winner['rewardLevel'])+$action->winnerReward, "', `trophies` = '", intval($winner['trophies'])+$action->gain, "' WHERE `CARDSUSERS`.`id` = '", $winnerId, "'");
			sendRequest("UPDATE `CARDSUSERS` SET `rewardLevel` = '", intval($loser['rewardLevel'])+$action->loserReward, "', `trophies` = '", max(0, intval($loser['trophies'])-$action->lost), "' WHERE `CARDSUSERS`.`id` = '", $loserId, "'");
			if ($winnerId>=-1 && $loserId>=-1) {
				sendRequest("UPDATE CARDS SET uses = uses+1 WHERE id IN (".implode(',', array_merge(...array_map(function($opponent){
					return array_map('intval', $opponent->baseDeck);
				}, $match->opponents))).")");
				sendRequest("UPDATE CARDS SET wins = wins+1 WHERE id IN (".implode(',', array_map('intval', $match->opponents[$action->winner]->baseDeck)).")");
			}
			sendRequest("INSERT INTO CARDSMATCHESHISTORY (`opponentId1`, `opponentId2`, `winner`, `deck1`, `deck2`, `trophies1`, `trophies2`) VALUES ('",$resultMatch['opponent1'],"', '",$resultMatch['opponent2'],"', '",$action->winner,"', '",json_encode($match->opponents[0]->baseDeck),"', '",json_encode($match->opponents[1]->baseDeck),"', '",($action->winner==0?$action->gain:-$action->lost),"', '",($action->winner==1?$action->gain:-$action->lost),"')");
		}
		break;
	}
}

// test de suppression
if ($end) {
	$delete = true;
	foreach ($match->opponents as $i=>$opponent) {
		if ($opponent->historyIndex < count($match->history) && $context['playerIds'][$i]!=-807) {
			$delete = false;
			//echo "do not delete cause ".($opponent->historyIndex." < ".count($match->history)." && ".$context['playerIds'][$i]."!=".(-807));
		}
	}
	if ($delete) {
		sendRequest("DELETE FROM MATCHES WHERE id = '", $matchId, "'");
	}
} else { // test de fin de tour
    try {
        if ($match->end <= time()) {
        	$match->endTurn($match->playing, $context);
        	save($match, $matchId);
        }
    } catch(Throwable $e) {
        $content = '';
        do {
            $content .= '__**'.$e->getMessage().'**__ dans '.$e->getFile().' (ligne '.$e->getLine().')'.PHP_EOL.$e->getTraceAsString();
            if ($e->getPrevious() != null)
                $content .= PHP_EOL.'Caused by ';
        } while(($e=$e->getPrevious()) != null);
        sendToDiscord('https://discord.com/api/webhooks/909609582077280366/ZZP1FqsWYYlGjGUPdb12TT5nSke2qb5iDohaVJDhbR5EvsQnz44IbZ_8ilfEWc-K_K8I', $content);
    }
}

// envoi de la réponse
echo(json_encode($response, isset($_REQUEST['pretty'])?JSON_PRETTY_PRINT:0));

// sauvegarde dans la BDD
function save($match, $matchId) {
	sendRequest("UPDATE `MATCHES` SET `infos` = '", json_encode($match->toStd()), "' WHERE id = '".$matchId."'");
}

?>