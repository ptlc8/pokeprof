<?php
include('../../init.php');

// connexion à un compte
$user = login(false, true);

// connexion au compte cards
$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0) {
    exit('invalid account');
}
$cardsUser = $result->fetch_assoc();

// récupération de l'index du deck et vérification
$deckIndex = intval($_REQUEST['deck'] ?? $cardsUser['choosenDeck']);
if ($deckIndex < 0 && $deckIndex >= count(json_decode($cardsUser['deck'])))
    exit('invalid deck');

// réponse
echo json_encode(json_decode($cardsUser['deck'])[$deckIndex]);
?>