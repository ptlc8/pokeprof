<?php

include("../init.php"); // init bdd and sendRequest

// connexion à un compte
$user = login(false, true);

//if (!in_array($user["id"], array("0", "19", "59", "72", "73"))) { // Kévin, Brayan, Timothée, Léo, Edwin
if (sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "' AND admin=1")->num_rows <= 0) {
	exit("not granted");
}

// récupération de la carte
if (!isset($_REQUEST['id'])) {
    exit("need id");
}
$cardRequest = sendRequest("SELECT * FROM CARDS WHERE id = '", $_REQUEST['id'], "'");
if ($cardRequest->num_rows === 0)
    exit("no card match id");
$card = $cardRequest->fetch_assoc();

$isADataToSet = false;
foreach (array("official", "name", "cost", "color", "type", "proftype", "hp", "atk1name", "atk1desc", "atk1dama", "atk1script", "atk2name", "atk2desc", "atk2dama", "atk2script", "rarity", "booster", "origin", "prestigeable", "types") as $dataName)
    if (isset($_REQUEST[$dataName]))
        $isADataToSet = true;
if (!$isADataToSet)
    exit("no data to set");

// modification de la carte
$request = "UPDATE CARDS SET ";
if (isset($_REQUEST['name'])) $request .= "name = '" . str_replace(array("\\","'"), array("\\\\", "\\'"), $_REQUEST['name']) . "', ";
if (isset($_REQUEST['type'])) $request .= "type = '" . str_replace(array("\\","'"), array("\\\\", "\\'"), $_REQUEST['type']) . "', ";
if (isset($_REQUEST['atk1script'])) $request .= "script1 = '" . str_replace(array("\\","'"), array("\\\\", "\\'"), $_REQUEST['atk1script']) . "', ";
if (isset($_REQUEST['atk2script'])) $request .= "script2 = '" . str_replace(array("\\","'"), array("\\\\", "\\'"), $_REQUEST['atk2script']) . "', ";
if (isset($_REQUEST['rarity']) && is_numeric($_REQUEST['rarity'])) $request .= "rarity = '" . str_replace(array("\\","'"), array("\\\\", "\\'"), $_REQUEST['rarity']) . "', ";
if (isset($_REQUEST['booster']) && is_numeric($_REQUEST['booster'])) $request .= "boosterId = '" . str_replace(array("\\","'"), array("\\\\", "\\'"), $_REQUEST['booster']) . "', ";
if (isset($_REQUEST['official']) && is_numeric($_REQUEST['official'])) $request .= "official = '" . str_replace(array("\\","'"), array("\\\\", "\\'"), $_REQUEST['official']) . "', ";
if (isset($_REQUEST['prestigeable']) && in_array($_REQUEST['prestigeable'], ["true","false"])) $request .= "prestigeable = '" . ($_REQUEST['prestigeable']=="true"?1:0) . "', ";
if (isset($_REQUEST['official']) && is_numeric($_REQUEST['official']) && in_array(intval($_REQUEST['official']), array(2,3,4))) $request .= "lastEditDate = NOW(), ";

$cardInfos = json_decode($card['infos']);
if (isset($_REQUEST['cost'])) $cardInfos->cost = intval($_REQUEST['cost']);
if (isset($_REQUEST['color'])) $cardInfos->color = $_REQUEST['color'];
if (isset($_REQUEST['proftype'])) $cardInfos->proftype = $_REQUEST['proftype'];
if (isset($_REQUEST['types'])) $cardInfos->types = array_filter(explode(',', $_REQUEST['types']));
if (isset($_REQUEST['hp'])) $cardInfos->hp = $_REQUEST['hp'];
if (isset($_REQUEST['atk1name'])) $cardInfos->atk1->name = $_REQUEST['atk1name'];
if (isset($_REQUEST['atk1desc'])) $cardInfos->atk1->desc = $_REQUEST['atk1desc'];
if (isset($_REQUEST['atk1dama'])) $cardInfos->atk1->dama = $_REQUEST['atk1dama'];
if (isset($_REQUEST['atk2name'])) $cardInfos->atk2->name = $_REQUEST['atk2name'];
if (isset($_REQUEST['atk2desc'])) $cardInfos->atk2->desc = $_REQUEST['atk2desc'];
if (isset($_REQUEST['atk2dama'])) $cardInfos->atk2->dama = $_REQUEST['atk2dama'];
if (isset($_REQUEST['origin'])) $cardInfos->origin = $_REQUEST['origin'];

$request .= "infos = '". str_replace(array("\\", "'"), array("\\\\", "\\'"), json_encode($cardInfos)) ."'";

sendRequest($request." WHERE id ='", $_REQUEST['id'], "'");

// on efface la carte du cache
set_error_handler(function() { /* ignore errors */ });
$cachefilename = '../../cached/card'.$_REQUEST['id'].'.json';
unlink($cachefilename);
restore_error_handler();

//Ajout de la carte chez Léo, test et le créateur si elle est publiée
$usersId = [-72,-77,-188];
if (isset($_REQUEST['official']) && intval($_REQUEST['official'])==2)
    array_push($usersId, intval($card['authorId']));
foreach ($usersId as $id) {
    $leo = sendRequest("SELECT cards FROM CARDSUSERS WHERE id = '", $id, "' ") -> fetch_assoc()['cards'];
    $leo = json_decode($leo);
    $idCardToAdd=$_REQUEST['id'];
    if (!isset($leo->$idCardToAdd)) {
        $leo->$idCardToAdd=($id==-72||$id==-77||$id==-188)?2:1;
        $leo=json_encode($leo);
        sendRequest("UPDATE CARDSUSERS SET cards='".$leo."' WHERE id='", $id, "'");
    }
}

// envoi d'un message dans le salon Discord #patch-notes
if (defined('POKEPROF_WEBHOOK_CARD_EDIT') && POKEPROF_WEBHOOK_CARD_EDIT!=null) {
	if (intval($card['official'])>0 || (isset($_REQUEST['official']) && intval($_REQUEST['official'])>0)) {
		set_error_handler(function() { /* ignore errors */ });
		$official = isset($_REQUEST['official']) ? intval($_REQUEST['official']) : 1;
		$cardInfos = json_decode($card['infos']);
		if ($official==2) {
		    $content = '__✨ **Ajout** de la carte **'.$card['name'].'** ('.$card['id'].') de '.(sendRequest("SELECT name FROM CARDSUSERS WHERE id = '".$card['authorId']."'")->fetch_assoc()['name']).'__';
		    $content .= "\n".'Disponible dans le booster **'.(sendRequest("SELECT name FROM BOOSTERS WHERE id = '", $_REQUEST['boosterId']??$card['boosterId'], "'")->fetch_assoc()['name']).'**';
		    if ($_REQUEST['prestigeable'] ?? $card['prestigeable']) $content .= "\n".'Disponible en version prestige/fullart';
		} else {
		    $content = '__';
		    $content .= $official==2 ? '✨ **Ajout**'
		            : ($official==3 ? '📈 **Buff**'
		            : ($official==4 ? '📉 **Nerf**'
		            : ($official<0 ? '🔒 **Désactivation**'
		            : '🖋 **Modification**')));
		    $content .= ' de la carte **'.$card['name'].'** ('.$card['id'].')__ (par '.$user['name'].')';
		    if (isset($_REQUEST['name'])) $content .= "\n".'Nouveau nom : '.$_REQUEST['name'];
		    if (isset($_REQUEST['cost'])) $content .= "\n".'Coût : '.$cardInfos->cost.' → '.$_REQUEST['cost'];
		    if (isset($_REQUEST['type'])) $content .= "\n".'Type de carte : '.getTypeName($card['type']).' → '.getTypeName($_REQUEST['type']);
		    if (isset($_REQUEST['proftype'])) $content .= "\n".'Type de combattant : '.getFighterTypeName($cardInfos->proftype).' → '.getFighterTypeName($_REQUEST['proftype']);
		    if (isset($_REQUEST['types'])) $content .= "\n".'Types de combattant : '.implode('&',array_map('getFighterTypeName', $cardInfos->types)).' → '.implode('&',array_map('getFighterTypeName', array_filter(explode(',', $_REQUEST['types']))));
		    if (isset($_REQUEST['hp'])) $content .= "\n".'Points de vie : '.$cardInfos->hp.' → '.$_REQUEST['hp'];
		    if (isset($_REQUEST['atk1name'])||isset($_REQUEST['atk1dama'])||isset($_REQUEST['atk1desc'])) $content .= "\n".'Attaque 1 : '.$cardInfos->atk1->name.' ('.$cardInfos->atk1->dama.') *'.$cardInfos->atk1->desc.'* → '.($_REQUEST['atk1name']??$cardInfos->atk1->name).' ('.($_REQUEST['atk1dama']??$cardInfos->atk1->dama).') *'.($_REQUEST['atk1desc']??$cardInfos->atk1->desc).'*';
		    if (isset($_REQUEST['atk2name'])||isset($_REQUEST['atk2dama'])||isset($_REQUEST['atk2desc'])) $content .= "\n".'Attaque 2 : '.$cardInfos->atk2->name.' ('.$cardInfos->atk2->dama.') *'.$cardInfos->atk2->desc.'* → '.($_REQUEST['atk2name']??$cardInfos->atk2->name).' ('.($_REQUEST['atk2dama']??$cardInfos->atk2->dama).') *'.($_REQUEST['atk2desc']??$cardInfos->atk2->desc).'*';
		    if (isset($_REQUEST['rarity'])) $content .= "\n".'Rareté : '.getRarityName($card['rarity']).' → '.getRarityName($_REQUEST['rarity']);
		    if (isset($_REQUEST['prestigeable'])) $content .= "\n".($_REQUEST['prestigeable']=='true'?'Disponible':'Indisponible').' en version prestige/fullart';
		    if (isset($_REQUEST['boosterId'])) $content .= "\n".'Maintenant disponible dans le booster **'.(sendRequest("SELECT name FROM BOOSTERS WHERE id = '", $_REQUEST['boosterId'], "'")->fetch_assoc()['name']).'**';
		}
		$context  = stream_context_create(array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => 'POST',
		        'content' => http_build_query(array('content' => $content))
		    )
		));
		file_get_contents(POKEPROF_WEBHOOK_CARD_EDIT, false, $context);
		restore_error_handler();
	}
}

echo "success";

function getRarityName($rarity) {
    switch ($rarity) {
        case 1:
            return 'Commune';
        case 2:
            return 'Rare';
        case 3:
            return 'Épique';
        case 4:
            return 'Légendaire';
    }
    return 'Non définie';
}

function getTypeName($type) {
    switch ($type) {
        case 'prof':
            return 'Combattant';
        case 'place':
            return 'Lieu';
        case 'effect':
            return 'Effet';
    }
    return 'Non défini';
}

$types = null;
function getFighterTypeName($fType) {
    global $types;
    if ($types == null) {
        $result = sendRequest("SELECT * FROM FIGHTERSCARDSTYPES")->fetch_all(MYSQLI_ASSOC);
        $types = [];
        foreach($result as $type)
            $types[$type['id']] = $type['name'];
    }
    return $types[$fType] ?? 'Aucun';
}

?>
