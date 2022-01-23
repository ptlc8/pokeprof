<?php
session_start();

define('START_HP', 160);
define('MIN_MANA', 1);
define('TURN_TIME', 90); // en secondes (à sync avec yoplay)

include('init.php');

// connexion à un compte
if (!isset($_SESSION['username'], $_SESSION['password'])
	|| ($userRequest = sendRequest("SELECT id, name FROM USERS WHERE `name` = '", $_SESSION['username'], "' and `password` = '", $_SESSION['password'], "'"))->num_rows === 0) {
	exit('not logged');
}

$user = $userRequest->fetch_assoc();

// connexion au compte cards
$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0) {
	exit("Veuillez d'abord aller à la page suivante : agnd.fr/cards");
}
$cardsUser = $result->fetch_assoc();

// vérification de la présence dans un match
$result = sendRequest("SELECT * FROM MATCHES WHERE opponent1 = '".$cardsUser['id']."' OR opponent2 = '".$cardsUser['id']."'");
if ($result->num_rows !== 0) {
	exit("playing");
}

// annulation de la recherche si demandée
if (isset($_REQUEST['cancel'])) {
    sendRequest("UPDATE `CARDSUSERS` SET `lastSearchDate` = '2001-00-00 00:00:00' WHERE `CARDSUSERS`.`id` = '", $cardsUser['id'], "'");
    exit("canceled");
}

// vérification de la possession d'un deck
if (count(array_filter(json_decode($cardsUser['deck'])[$cardsUser['choosenDeck']])) < 8) {
    exit('nodeck');
}

// recherche d'adversaire
$result = isset($_REQUEST['bot'])
    ? sendRequest("SELECT * FROM `CARDSUSERS` WHERE id = -807") // le bot
    : sendRequest("SELECT * FROM `CARDSUSERS` WHERE TIMESTAMPDIFF(SECOND, lastSearchDate, NOW()) <= 5 AND id != ", $cardsUser['id']);
if ($result->num_rows === 0) {
	// actualistion de la recherche
	sendRequest("UPDATE `CARDSUSERS` SET `lastSearchDate` = NOW() WHERE `CARDSUSERS`.`id` = '", $cardsUser['id'], "'");
	exit("searching");
} else {
	$opponent = $result->fetch_assoc();
	sendRequest("UPDATE `CARDSUSERS` SET `lastSearchDate` = '2000-00-00 00:00:00' WHERE `CARDSUSERS`.`id` = ", $cardsUser['id'], " OR `CARDSUSERS`.`id` = ", $opponent['id']);
	$result = sendRequest("SELECT * FROM CARDS WHERE official > 0 OR official = -1");
	$allcards = [];
	while ($row = $result->fetch_assoc())
		$allcards[$row['id']] = $row;
	$matchInfos = new stdClass();
	$matchInfos->playing = 0;
	$matchInfos->start = time();
	$matchInfos->end = time() + TURN_TIME;
	$matchInfos->place = array();
	$matchInfos->mL = MIN_MANA + 1;
	$o1 = new stdClass();
	$o2 = new stdClass();
	$o1->hp = $o2->hp = START_HP;
	$o1->mana = MIN_MANA;
	$o2->mana = MIN_MANA+1;
	$o1->discard = array();
	$o2->discard = array();
	$o1->profs = array();
	$o2->profs = array();
	$o1->deck = [];
	foreach (json_decode($cardsUser['deck'])[$cardsUser['choosenDeck']] as $deckcard) {
		if ($deckcard === 0) continue;
		if (!array_key_exists(intval($deckcard), $allcards)) continue;
		$card = new stdClass();
		$card->id = intval($deckcard);
		$card->p = strpos($deckcard, "p") !== false;
		$card->type = $allcards[intval($deckcard)]['type'];
		$card->scripts = array($allcards[intval($deckcard)]['script1'], $allcards[intval($deckcard)]['script2']);
		$cardinfos = json_decode($allcards[intval($deckcard)]['infos']);
		$card->cost = intval($cardinfos->cost);
		if ($card->type == 'prof') $card->hp = intval($cardinfos->hp);
		if ($card->type == 'prof') $card->hpmax = intval($cardinfos->hp);
		if ($card->type == 'prof') $card->proftype = $cardinfos->proftype;
		//if ($cardname == 'Anissa') $card->sortir =  intval(1); //Modif de Léo
		array_push($o1->deck, $card);
	}
	shuffle($o1->deck);
	$o2->deck = [];
	foreach (json_decode($opponent['deck'])[$opponent['id']==-807?random_int(0,count(json_decode($opponent['deck']))-1):$opponent['choosenDeck']] as $deckcard) {
		if ($deckcard === 0) continue;
		if (!array_key_exists(intval($deckcard), $allcards)) continue;
		$card = new stdClass();
		$card->id = intval($deckcard);
		$card->p = strpos($deckcard, "p") !== false;
		$card->type = $allcards[intval($deckcard)]['type'];
		$card->scripts = array($allcards[intval($deckcard)]['script1'], $allcards[intval($deckcard)]['script2']);
		$cardinfos = json_decode($allcards[intval($deckcard)]['infos']);
		$card->cost = intval($cardinfos->cost);
		if ($card->type == 'prof') $card->hp = intval($cardinfos->hp);
		if ($card->type == 'prof') $card->hpmax = intval($cardinfos->hp);
		if ($card->type == 'prof') $card->proftype = $cardinfos->proftype;
		//if ($cardname == 'Anissa') $card->sortir =  intval(1); //Modif de Léo
		array_push($o2->deck, $card);
	}
	shuffle($o2->deck);
	$o1->hand = array(array_pop($o1->deck), array_pop($o1->deck), array_pop($o1->deck), array_pop($o1->deck), array_pop($o1->deck));
	$o2->hand = array(array_pop($o2->deck), array_pop($o2->deck), array_pop($o2->deck), array_pop($o2->deck), array_pop($o2->deck));
	
	$matchInfos->opponents = array($o1, $o2);
	sendRequest("INSERT INTO `MATCHES` (`opponent1`, `opponent2`, `infos`) VALUES ('", $cardsUser['id'], "', '", $opponent['id'], "', '", json_encode($matchInfos), "')");
	exit("founded");
}








