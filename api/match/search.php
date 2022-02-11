<?php
include('../init.php');

// connexion à un compte
$user = login(false, true);

// connexion au compte cards
$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0) {
	exit('Veuillez d\'abord aller à la page suivante : '.$_SERVER['SERVER_NAME'].'/cards');
}
$cardsUser = $result->fetch_assoc();

// vérification de la présence dans un match
$result = sendRequest("SELECT * FROM MATCHES WHERE (opponent1 = '".$cardsUser['id']."' OR opponent2 = '".$cardsUser['id']."') AND end = '0'");
if ($result->num_rows !== 0) {
	exit("playing");
}

// annulation de la recherche si demandée
if (isset($_REQUEST['cancel'])) {
    sendRequest("UPDATE `CARDSUSERS` SET `lastSearchDate` = '2001-01-01 00:00:00' WHERE `CARDSUSERS`.`id` = '", $cardsUser['id'], "'");
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
	$connecteds = sendRequest("SELECT COUNT(*) AS connecteds FROM CARDSUSERS WHERE TIMEDIFF(NOW(),lastConnection) < '00:03'")->fetch_assoc()['connecteds'];
	$playings = sendRequest("SELECT COUNT(DISTINCT CARDSUSERS.id) AS playings FROM CARDSUSERS JOIN MATCHES ON (opponent1=CARDSUSERS.id OR opponent2=CARDSUSERS.id) WHERE NOW() < ADDTIME(lastConnection,'00:03') AND MATCHES.end = 0")->fetch_assoc()['playings'];
	exit("searching ".$connecteds." ".$playings);
}

// Arrêt des recherches et création du match
$opponentCardsUser = $result->fetch_assoc();
sendRequest("UPDATE `CARDSUSERS` SET `lastSearchDate` = '2000-01-01 00:00:00' WHERE `CARDSUSERS`.`id` = ", $cardsUser['id'], " OR `CARDSUSERS`.`id` = ", $opponentCardsUser['id']);
$result = sendRequest("SELECT * FROM CARDS WHERE official > 0 OR official = -1");
$allcards = [];
while ($row = $result->fetch_assoc())
	$allcards[$row['id']] = $row;
include('match.php');
// Création des joueurs
$opponents = [];
foreach(array($cardsUser,$opponentCardsUser) as $cU) {
    $decks = json_decode($cU['deck']);
    $o = new Player();
	foreach ($decks[$cU['id']==-807?random_int(0,count($decks)-1):$cU['choosenDeck']] as $deckcard) {
		if ($deckcard === 0) continue;
		if (!array_key_exists(intval($deckcard), $allcards)) continue;
		array_push($o->baseDeck, $deckcard);
		array_push($o->deck, Card::fromDBRow($deckcard, $allcards[intval($deckcard)]));
	}
	shuffle($o->deck);
	for ($i = 0; $i < 5; $i++)
	    array_push($o->hand, array_pop($o->deck));
	array_push($opponents, $o);
}
$match = new Match($opponents);
$totalCartes = count($allcards);
$nbCartes = min($totalCartes, count((array)json_decode($cardsUser['cards'])));
$q = ($totalCartes/2 - abs($nbCartes-$totalCartes/2)) / 2; // int : coefficient de divergence du niveu du bot, une personne sans carte sera contre un bot de niveau 0 et une persone ayant toutes les cartes contre un bot de niveau max, il est divisé par 2 ainsi quelqu'un ayant la moitié des cartes aura un bot de niveau >25% et <75%
$match->botDifficult = round(($nbCartes+random_int(-$q,$q))/$totalCartes*MAX_BOT_LEVEL); // MAX_BOT_LEVEL est défini dans match.php

//$match->botDifficult=random_int(0,5);

/*
$moyenne=0;
for ($i=0; $i<100; $i++) {
    $moyenne+=(($nbcartes+random_int(-$q,$q))/$nbcartes)*4;
}
$match->botDifficult = round($moyenne/100);
*/

//$match->botDifficult=4;

sendRequest("INSERT INTO `MATCHES` (`opponent1`, `opponent2`, `infos`) VALUES ('", $cardsUser['id'], "', '", $opponentCardsUser['id'], "', '", json_encode($match->toStd()), "')");
exit("founded");
