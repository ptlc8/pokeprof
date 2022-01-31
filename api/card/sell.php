<?php
include('../init.php');
$user = login(false, true);

// vérification du post
if (!isset($_REQUEST['card'], $_REQUEST['amount'])) {
	echo('need more args');
	exit;
}

$cardId = $_REQUEST['card'];
$amount = intval($_REQUEST['amount']);

if ($amount<=0)
    exit('invalid amount');

// vérification de la possession de la carte // NON ?
$result = sendRequest("SELECT cards, rewardLevel, id FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0) {
	exit("<span>Veuillez d'abord aller à la page suivante : agnd.fr/cards/</span>");
}
$cardsUser = $result->fetch_assoc();
$cards = json_decode($cardsUser['cards']);

// vérification de l'existence dans ses cartes et en trop peu // TODO ? : et de leur présence en deck
if (!property_exists($cards, $cardId)) {
	exit('card doesn\'t have/exist');
}
if ($cards->$cardId - $amount <= 2) {
    exit('notenoughofthis '.$cards->$cardId);
}

// récupération de la valeur de la carte
$result = sendRequest("SELECT rarity FROM CARDS WHERE id = '", intval($cardId), "'");
if ($result->num_rows == 0) exit('card doesn\'t have/exist');
$rarity = $result->fetch_assoc()['rarity'];
switch ($rarity) {
    case 1:
        $value = 1;
        break;
    case 2:
        $value = 2;
        break;
    case 3:
        $value = 4;
        break;
    case 4:
        $value = 7;
        break;
    default:
        exit('can\'t sell');
}

// échange de la carte contre des points de récompenses
$cards->$cardId -= $amount;
sendRequest("UPDATE CARDSUSERS SET cards = '", json_encode($cards), "', rewardLevel = '", $cardsUser['rewardLevel']+$value*$amount, "' WHERE CARDSUSERS.id = '".$cardsUser['id']."'");
echo('success');
