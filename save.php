<?php
include('init.php');

// connexion à un compte
$user = login(false, true);
$cardsUserRequest = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($cardsUserRequest->num_rows==0)
    exit('Veuillez d\'abord aller à la page '.$_SERVER['HOST_NAME'].'/cards');
$cardsUser = $cardsUserRequest->fetch_assoc();

if (!isset($_REQUEST['name']) || $_REQUEST['name']=='') exit('La carte a besoin d\'un nom');
if (!isset($_REQUEST['image']) || $_REQUEST['image']=='') exit('La carte a besoin d\'une image');
if (!isset($_REQUEST['type']) || $_REQUEST['type']=='') exit('La carte a besoin d\'un type');
if (!isset($_REQUEST['color']) || strpos($_REQUEST['color'], '#') !== 0) exit('La carte a besoin d\une couleur');
if (!isset($_REQUEST['cost']) || !is_numeric($_REQUEST['cost'])) exit('La carte a besoin d\'un coût d\'invocation');
if (!isset($_REQUEST['origin']) || $_REQUEST['origin']=='') exit('Explique-nous l\'origine de ta super carte. On adore les ragots :)');

$dataImage = base64_decode(substr($_REQUEST['image'], strpos($_REQUEST['image'], ",") + 1));
if (strlen($dataImage) > 250000) exit("L'image envoyé est trop volumineuse, elle dépasse 250 kilo-octets");
try {
    $image = imagecreatefromstring($dataImage);
} catch (Exception $e) {
    $image = false;
}
if (!$image) {
    exit("L'image que tu as envoyé n'est pas valide, seule une image en JPEG, PNG, GIF, BMP, WBMP, GD2 et WEBP sera acceptée");
}

// envoi dans la BDD
$cardInfos = new stdClass();
$cardInfos->color = $_REQUEST['color'];
$cardInfos->cost = intval($_REQUEST['cost']);
if (isset($_REQUEST['hp'])) $cardInfos->hp = $_REQUEST['hp'];
if (isset($_REQUEST['proftype'])) $cardInfos->proftype = array_filter(explode(',', $_REQUEST['proftype']))[0];
if (isset($_REQUEST['proftype'])) $cardInfos->types = array_filter(explode(',', $_REQUEST['proftype']));
$cardInfos->atk1 = array("name" => $_REQUEST['atk1name'] ?? '', "desc" => $_REQUEST['atk1desc'] ?? '', "dama" => $_REQUEST['atk1dama'] ?? '');
$cardInfos->atk2 = array("name" => $_REQUEST['atk2name'] ?? '', "desc" => $_REQUEST['atk2desc'] ?? '', "dama" => $_REQUEST['atk2dama'] ?? '');
$cardInfos->origin = $_REQUEST['origin'];

if (!isset($_REQUEST['rarity']) || $_REQUEST['rarity'] == '')
    sendRequest("INSERT INTO `CARDS` (`authorId`, `name`, `type`, `infos`, `date`) VALUES ('".$user['id']."', '", $_REQUEST['name'],"', '",$_REQUEST['type'],"', '",json_encode($cardInfos),"', NOW());");
else
    sendRequest("INSERT INTO `CARDS` (`authorId`, `name`, `type`, `infos`, `rarity`, `date`) VALUES ('".$user['id']."', '", $_REQUEST['name'],"', '",$_REQUEST['type'],"', '",json_encode($cardInfos),"', '", $_REQUEST['rarity'], "', NOW());");

// enregistrement le l'image de la carte dans les assets
$cardId = sendRequest(" SELECT MAX(id) AS id FROM CARDS")->fetch_assoc()['id'];
imagepng($image, "assets/cards/".$cardId.".png");

// pour le tag créateur de cartes
$tags = json_decode($cardsUser['tags']);
if (!in_array("@🧩 Créateur de cartes", $tags)) {
    array_push($tags, "@🧩 Créateur de cartes");
    sendRequest("UPDATE CARDSUSERS SET tags = '", json_encode($tags), "' WHERE id = '", $user['id'], "'");
}

// envoi d'un message dans le salon Discord #cartes-créées
set_error_handler(function() { /* ignore errors */ });
$url = "https://discord.com/api/webhooks/897633797560991794/K-lV-38h2C3IfMVUChL3NX9mH-fhs03Exbut68T9HpluZuwmNq2WqXmXyjbqTthApkDi";
$context  = stream_context_create(array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query(array('content' => '**'.$user['name'].'** a créé une carte ***'.$_REQUEST['name'].'*** ('.$_REQUEST['type'].', '.$_REQUEST['cost'].' manas, *'.($_REQUEST['atk1name']??'').'*, *'.($_REQUEST['atk2name']??'').'*)'."\n".implode("\n",array_map(function($line){return '> '.$line;},explode("\n",$_REQUEST['origin'])))))
    )
));
file_get_contents($url, false, $context);
restore_error_handler();

// succès
echo('success '.$cardId);
?>