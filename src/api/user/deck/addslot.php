<?php
include('../../init.php');

// connexion à un compte
$user = login();
if ($user == null)
    exit("not logged");

// connexion au compte cards
$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0) {
	exit('invalid account');
}
$cardsUser = $result->fetch_assoc();

// vérification de l'argent
if (intval($cardsUser['money']) < 10)
	exit('need money');

// payement et ajout du slot
$decks = json_decode($cardsUser['deck']);
array_push($decks, [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]);
sendRequest("UPDATE CARDSUSERS SET money = money-10, deck = '", json_encode($decks), "' WHERE id = '", $user['id'], "'");

echo 'success';
?>