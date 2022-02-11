<?php
if (!isset($_REQUEST['id'])) exit('need user id');

include('../init.php');

// envoi de la requête
$result = sendRequest("SELECT name, email, trophies, tags, cards"/*ROUND((LENGTH(cards)-LENGTH(REPLACE(cards,'\"','')))/2) as cards, ROUND((LENGTH(cards)-LENGTH(REPLACE(cards,'p','')))) as prestiges, ROUND((LENGTH(cards)-LENGTH(REPLACE(cards,'s','')))) as shinies, ROUND((LENGTH(cards)-LENGTH(REPLACE(cards,'h','')))) as holos*/.", lastConnection FROM CARDSUSERS JOIN USERS ON CARDSUSERS.id = USERS.id WHERE USERS.id = '", $_REQUEST['id'], "'");
if ($result->num_rows === 0) exit('not found');

$u = $result->fetch_assoc();
$user = new stdClass();
$user->name = $u['name'];
$user->trophies = $u['trophies'];
$lastConnectionTime = strtotime($u['lastConnection']);
if ($lastConnectionTime > time()-3*60)
    $user->lastConnection = 'En ligne';
else if ($lastConnectionTime > time()-60*60)
    $user->lastConnection = 'Connecté il y a moins d\'une heure';
else if ($lastConnectionTime > time()-24*60*60)
    $user->lastConnection = 'Connecté il y a moins d\'un jour';
else if ($lastConnectionTime > time()-7*24*60*60)
    $user->lastConnection = 'Connecté il y a moins d\'une semaine';
else if ($lastConnectionTime > time()-30*24*60*60)
    $user->lastConnection = 'Connecté il y a moins d\'un mois';
else
    $user->lastConnection = 'Connecté il y a longtemps';
$user->tags = json_decode($u['tags']);
$cards = json_decode($u['cards'], true);
$uniqueCards = [];
foreach ($cards as $id=>$card)
    if (!in_array(intval($id),$uniqueCards))
        array_push($uniqueCards, intval($id));
$user->cards = count($uniqueCards);
$user->prestiges = count(array_filter($cards,function($id){return strpos($id,"p")!==false;},ARRAY_FILTER_USE_KEY));
$user->shinies = count(array_filter($cards,function($id){return strpos($id,"s")!==false;},ARRAY_FILTER_USE_KEY));
$user->holos = count(array_filter($cards,function($id){return strpos($id,"h")!==false;},ARRAY_FILTER_USE_KEY));
$user->picture = 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($u['email']))).'?s=200&d=robohash&r=pg';

echo json_encode($user);
?>