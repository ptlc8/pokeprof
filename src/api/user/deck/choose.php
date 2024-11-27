<?php
// vérification
if (!isset($_REQUEST['deck']))
    exit('need deck');

// connexion 
include('../../init.php');
$user = login();
if ($user == null)
    exit("not logged");

$result = sendRequest("SELECT deck, choosenDeck FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0)
	exit('invalid account');
$cardsUser = $result->fetch_assoc();

// vérification supplémentaire
$deckIndex = intval($_REQUEST['deck']);
if ($deckIndex<0 && $deckIndex>=count(json_decode($cardsUser['deck'])))
    exit('invalid deck');

// changement
if ($deckIndex != intval($cardsUser['choosenDeck']))
    sendRequest("UPDATE CARDSUSERS SET choosenDeck = '", $deckIndex, "' WHERE id = '", $user['id'], "'");
echo 'success';

?>