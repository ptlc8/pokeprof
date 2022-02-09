<?php
// vérification de l'argument
if (!isset($_REQUEST['card'])) exit('{"msg":"need card id", "card":{}}');

// cache serveur, source : https://wesbos.com/simple-php-page-caching-technique/
$cachefile = 'cached/card'.$_REQUEST['card'].'.php';
$cachetime = 18000; // 5h
if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
  	include($cachefile);
	exit;
}

include('init.php');

// render the page and capture the HTML.
ob_start();

// envoi de la requête
$result = sendRequest("SELECT * FROM CARDS WHERE id = '", $_REQUEST['card'], "'");
if ($result->num_rows === 0) exit('{"msg":"card doesnt exist", "card":{}}');

// oraganisation des résultats
$card = $result->fetch_assoc();
$cardInfos = json_decode($card['infos']);
$cardInfos->id = $card['id'];
$cardInfos->name = $card['name'];
$cardInfos->type = $card['type'];
$cardInfos->rarity = intval($card['rarity']);
$cardInfos->date = $card['date'];

//Modif de Léo
$result = sendRequest("SELECT name FROM BOOSTERS WHERE id = '", intval($card['boosterId']), "'")->fetch_assoc();
$cardInfos->booster = $result==null ? "Aucun" : $result['name'];

//$cardInfos->image = "data:image/png;base64,".base64_encode($card['image']);

// affichage du résultat en JSON
echo(json_encode($cardInfos));

// mise en cache
$fp = fopen($cachefile, 'w');
fwrite($fp, ob_get_contents());
fclose($fp);
ob_end_flush();
?>