<?php
// vérification
if (!isset($_REQUEST['deck']))
    exit('need deck');

// connexion 
include('../../init.php');
$user = login(false, true);
$result = sendRequest("SELECT deck, choosenDeck FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0)
	exit("<span>Veuillez d'abord aller à la page suivante : agnd.fr/cards/</span>");
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