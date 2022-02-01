<?php
include("../init.php");

// connexion à un compte
$user = login(false, true);

// récupération du joueur
$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0)
	exit('invalid account');
$cardsUser = $result->fetch_assoc();

// récupération de la boutique
$shop = new stdClass();
$shop->money = $cardsUser['money'];

/*
$result = sendRequest("SELECT *, (SELECT GROUP_CONCAT(id ORDER BY rarity DESC) FROM CARDS WHERE boosterId = BOOSTERS.id".(isset($_REQUEST['all'])?"":" AND official>0")." ORDER BY rarity) as cards FROM BOOSTERS");
$shop->boosters = [];
while (($booster = $result->fetch_assoc()) != null) {
    $booster['cards'] = explode(',', $booster['cards']);
    array_push($shop->boosters, $booster);
}
*/

//Version Léo
$result = sendRequest("SELECT *, (SELECT GROUP_CONCAT(id ORDER BY rarity DESC) FROM CARDS WHERE boosterId = BOOSTERS.id".(isset($_REQUEST['all'])?"":" AND official>0")." ORDER BY rarity) as cards, (SELECT AVG(rarity) FROM CARDS WHERE boosterId = BOOSTERS.id".(isset($_REQUEST['all'])?"":" AND official>0").") as calculprice FROM BOOSTERS");
$shop->boosters = [];
while (($booster = $result->fetch_assoc()) != null) {
    $booster['cards'] = explode(',', $booster['cards']);
    $booster['price'] =intval($booster['calculprice']*(5-(min(20,count($booster['cards']))/10)));
    array_push($shop->boosters, $booster);
}

$shop->prestiges = [];
$allcards = sendRequest("SELECT * FROM CARDS WHERE official > 0")->fetch_all(MYSQLI_ASSOC);
$shop->cards = [];
for ($i=1; $i<4; $i++) {
    $card = $allcards[(time()/60/60/24*$i+intval($user['id']))%count($allcards)];
    array_push($shop->cards, array('id'=>$card['id'], 'price'=>pow(2,intval($card['rarity']))));
}

echo json_encode($shop);

?>