<?php
include('../init.php');
$user = login();
if ($user == null) exit("not logged");

// vérification du post
if (!isset($_REQUEST['id'], $_REQUEST['index'])) {
	exit('need more args');
}

$cardId = ($_REQUEST['id']);
$index = intval($_REQUEST['index']);

// vérification de la possession de la carte // NON ?
$result = sendRequest("SELECT cards, deck, id FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0) {
	exit('invalid account');
}
$cardsUser = $result->fetch_assoc();
$decks = json_decode($cardsUser['deck']);
$deckIndex = isset($_REQUEST['deck']) ? $_REQUEST['deck'] : $cardsUser['choosenDeck'];

// vérification de la valeur de l'index
if (0 > $index || $index >= count($decks[$deckIndex])) {
	echo('index must be between 0 and '.(count($decks[$deckIndex])-1).' included');
	exit;
}

// vérification de l'existence dans ses cartes et en trop peu et de la non présence triple de la carte dans le deck
$cards = json_decode($cardsUser['cards']);
if (!property_exists($cards, $cardId)) {
	exit('card doesn\'t have/exist');
}

if (intval($decks[$deckIndex][$index]) != intval($cardId) && count(array_filter($decks[$deckIndex], function($deckCardId)use($cardId){return intval($deckCardId)==intval($cardId);})) >= 3) {
	exit('maxamountindeck '.array_search($cardId, $decks[$deckIndex]).' 3'); //Modif de Léo
}
$occInDeck = array_count_values($decks[$deckIndex]);
if (in_array($cardId, $decks[$deckIndex]) && isset($occInDeck[$cardId]) && $cards->$cardId <= $occInDeck[$cardId]) {
    exit('notenoughofthis '.$cards->$cardId);
}

// placement de la carte dans le deck
$decks[$deckIndex][$index] = $cardId;
sendRequest("UPDATE CARDSUSERS SET deck = '", json_encode($decks), "' WHERE CARDSUSERS.id = '".$cardsUser['id']."'");
echo('success');
