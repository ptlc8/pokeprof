<?php
if (!isset($_REQUEST['id'])) exit('need user id');

include('../../init.php');

// envoi de la requête
$result = sendRequest("SELECT name, trophies, tags, cards"/*ROUND((LENGTH(cards)-LENGTH(REPLACE(cards,'\"','')))/2) as cards, ROUND((LENGTH(cards)-LENGTH(REPLACE(cards,'p','')))) as prestiges, ROUND((LENGTH(cards)-LENGTH(REPLACE(cards,'s','')))) as shinies, ROUND((LENGTH(cards)-LENGTH(REPLACE(cards,'h','')))) as holos*/.", lastConnection FROM CARDSUSERS JOIN USERS ON CARDSUSERS.id = USERS.id WHERE USERS.id = '", $_REQUEST['id'], "'");
if ($result->num_rows === 0) exit('not found');

$u = $result->fetch_assoc();
$u['tags'] = json_decode($u['tags']);
$cards = json_decode($u['cards'], true);
$uniqueCards = [];
foreach ($cards as $id=>$card)
    if (!in_array(intval($id),$uniqueCards))
        array_push($uniqueCards, intval($id));
$u['cards'] = count($uniqueCards);
$u['prestiges'] = count(array_filter($cards,function($id){return strpos($id,"p")!==false;},ARRAY_FILTER_USE_KEY));
$u['shinies'] = count(array_filter($cards,function($id){return strpos($id,"s")!==false;},ARRAY_FILTER_USE_KEY));
$u['holos'] = count(array_filter($cards,function($id){return strpos($id,"h")!==false;},ARRAY_FILTER_USE_KEY));
echo json_encode($u);

?>