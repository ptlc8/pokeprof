<?php
include('../init.php');
$user = login();
if ($user == null)
    exit("not logged");

echo json_encode(sendRequest("SELECT id, name, admin, cards, deck, choosenDeck, trophies, lastFreeCard, money, rewardLevel, infos, tags FROM CARDSUSERS WHERE id = '", $user['id'], "'")->fetch_assoc());
?>