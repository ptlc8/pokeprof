<?php
include('../init.php');

// connexion à un compte
$user = login(false, true);

// connexion au compte cards
$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0) {
	exit("<script>window.location.replace('');</script>");
}
$cardsUser = $result->fetch_assoc();

// vérification de la requête et de l'argument what, définition des paramètres de génération du loot
$loot = null;
$cMin = $rMin = $eMin = $lMin = 0;
$containsMoney = false;
if (isset($_REQUEST['what'])) {
	switch ($_REQUEST['what']) {
		case 'freecard':
			if (intval(strtotime($cardsUser['lastFreeCard'])) + (12*60*60) > time())
				exit('need to wait');
			$n = 1;
			sendRequest("UPDATE `CARDSUSERS` SET `lastFreeCard` = FROM_UNIXTIME('", time(), "') WHERE `id` = '".$cardsUser['id']."'");
			break;
		/*case 'booster':
		    if (intval($cardsUser['boosters']) < 1) exit('no more booster');
			$n = 3;
			sendRequest("UPDATE `CARDSUSERS` SET `boosters` = '", $cardsUser['boosters']-1, "' WHERE `id` = '".$cardsUser['id']."'");
			$cMin = $rMin = $eMin = $lMin = 0;
			break;*/
		case 'reward1':
		    if (intval($cardsUser['rewardLevel']) < 7) exit('need more');
		    $n = 3;
		    $containsMoney = true;
		    sendRequest("UPDATE `CARDSUSERS` SET `rewardLevel` = '", $cardsUser['rewardLevel']-7, "' WHERE `id` = '".$cardsUser['id']."'");
		    break;
		case 'reward2':
		    if (intval($cardsUser['rewardLevel']) < 14) exit('need more');
		    $n = 6;
		    $rMin = 1;
		    $containsMoney = true;
		    sendRequest("UPDATE `CARDSUSERS` SET `rewardLevel` = '", $cardsUser['rewardLevel']-14, "' WHERE `id` = '".$cardsUser['id']."'");
		    break;
		case 'reward3':
            if (intval($cardsUser['rewardLevel']) < 21) exit('need more');
		    $n = 10;
		    $eMin = 1;
		    $containsMoney = true;
		    sendRequest("UPDATE `CARDSUSERS` SET `rewardLevel` = '", $cardsUser['rewardLevel']-21, "' WHERE `id` = '".$cardsUser['id']."'");
		    break;
		case 'shopbooster':
		    if (!isset($_REQUEST['id'])) exit('need id');
		    //$result = sendRequest("SELECT * FROM BOOSTERS WHERE id = '".$_REQUEST['id']."'");
		    
		    //Version Léo
		    $result = sendRequest("SELECT *, (SELECT COUNT(id) FROM CARDS WHERE boosterId = BOOSTERS.id) as cards, (SELECT AVG(rarity) FROM CARDS WHERE boosterId = BOOSTERS.id) as calculprice FROM BOOSTERS WHERE id = '".$_REQUEST['id']."'");
		    
		    if ($result->num_rows==0) exit("unknow booster id");
		    $booster = $result->fetch_assoc();
		    
		    //Léo
		    $booster['price'] =intval($booster['calculprice']*(5-(min(20,$booster['cards'])/10)));
		    
		    if ($booster['price'] > $cardsUser['money']) exit('need money');
		    $n = 3;
		    //sendRequest("UPDATE `CARDSUSERS` SET `money` = '", intval($cardsUser['money'])-intval($booster['price']), "' WHERE `id` = '".$cardsUser['id']."'");
		    $cardsUser['money'] = intval($cardsUser['money'])-intval($booster['price']);
		    break;
		case 'shopcard':
		    if (!isset($_REQUEST['id'])) exit('need id');
		    // Récupération des cartes du shop
		    $allcards = sendRequest("SELECT * FROM CARDS WHERE official > 0")->fetch_all(MYSQLI_ASSOC);
            $shopcard = null;
            for ($i=1; $i<4; $i++) {
                $card = $allcards[(time()/60/60/24*$i+intval($cardsUser['id']))%count($allcards)];
                $card['price'] = pow(2,intval($card['rarity']));
                if ($card['id']==$_REQUEST['id']) $shopcard = $card;
            }
            if ($shopcard==null) exit("card id not in shop");
		    if ($shopcard['price'] > $cardsUser['money']) exit('need money');
		    //sendRequest("UPDATE `CARDSUSERS` SET `money` = '", intval($cardsUser['money'])-intval($shopcard['price']), "' WHERE `id` = '".$cardsUser['id']."'");
		    $cardsUser['money'] = intval($cardsUser['money'])-intval($shopcard['price']);
		    $card = new stdClass();
        	$card->type = 'card';
        	$card->id = intval($shopcard['id']);
        	$card->rarity = intval($shopcard['rarity']);
		    $loot = [$card];
		    break;
		default:
			exit('unknow loot');
	}
} else {
	exit('bad request');
}

if ($loot==null) {
    // récupération des cartes
    $result = sendRequest("SELECT id, rarity, prestigeable FROM CARDS WHERE official > 0".($_REQUEST['what']=='shopbooster'?" AND boosterId = '".str_replace(array('\\', '\''), array('\\\\', '\\\''), $_REQUEST['id'])."'":''));
    if ($result->num_rows < $n)
        exit("not enough cards");
    $weightedCards = [];
    for ($i = 0; $c = $result->fetch_assoc(); ) {
    	$card = new stdClass();
    	$card->type = 'card';
    	$card->id = intval($c['id']);
    	$card->rarity = $c['rarity'];
    	$card->prestigeable = $c['prestigeable'];
    	$weight = pow(3, 4-$c['rarity']);
    	for ($j = 0; $j < $weight; $j++) {
    		$weightedCards[$i] = $card;
    		$i++;
    	}
    }
    
    // génération du loot de cartes
    do {
        $loot = [];
        for ($i = 0; $i < $n; $i++) {
            $cardLooted = clone $weightedCards[rand(0, count($weightedCards)-1)];
            if (!in_array($cardLooted, $loot)) array_push($loot, $cardLooted);
            else $i--;
            if (random_int(0,31)==0) $cardLooted->id .= 's';
            if (random_int(0,15)==0) $cardLooted->id .= 'h';
            if ($cardLooted->prestigeable && random_int(0,63)==0) $cardLooted->id .= 'p';
            unset($cardLooted->prestigeable);
        }
        $c = $r = $e = $l = 0;
        foreach ($loot as $cardLooted) {
            switch ($cardLooted->rarity) {
                case 1:
                    $c++;
                    break;
                case 2:
                    $r++;
                    break;
                case 3:
                    $e++;
                    break;
                case 4:
                    $l++;
                    break;
            }
        }
    } while ($c < $cMin || $r < $rMin || $e < $eMin || $c < $lMin);
}

// ajout de l'argent au joueur
if ($containsMoney) {
    $money = intval(count($loot)/3*random_int(0,9)/5);
    //echo count($loot).'/3*'.random_int(0,9).'/10+2='.$money;
    if ($money > 0)
        array_push($loot, (object)array('type'=>'money','id'=>'money','money'=>$money));
}

// ajout du loot aux cartes du joueur
$cards = json_decode($cardsUser['cards']);
$money = 0;
foreach ($loot as $cardLooted) {
    if ($cardLooted->type=='card') {
        $cardLootedId = $cardLooted->id;
    	if (isset($cards->$cardLootedId)) {
    		$cards->$cardLootedId++;
    	} else {
    		$cards->$cardLootedId = 1;
    	}
    } else if ($cardLooted->type=='money') {
        $money += $cardLooted->money;
    }
}
sendRequest("UPDATE `CARDSUSERS` SET `cards` = '", json_encode($cards), "', `money` = '", $cardsUser['money']+$money, "' WHERE `id` = '", $cardsUser['id'], "'");

echo(json_encode($loot));



