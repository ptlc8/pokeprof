<?php
session_start();

define('MAX_MANA', 10);
define('TURN_TIME', 75); // en secondes
define('TROPHIES', 60);
define('ELECTRIFY_DAMAGE', 10);

include('../init.php');
// connexion à un compte
if (!isset($_SESSION['username'], $_SESSION['password'])
	|| ($userRequest = sendRequest("SELECT id, name FROM USERS WHERE `name` = '", $_SESSION['username'], "' and `password` = '", $_SESSION['password'], "'"))->num_rows === 0) {
	echo('not logged');
	exit;
}

$user = $userRequest->fetch_assoc();

// récupération des cartes et du deck du joueur
$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
if ($result->num_rows === 0) {
	exit("<span>Veuillez d'abord aller à la page suivante : agnd.fr/cards/</span>");
}
$cardsUser = $result->fetch_assoc();

// vérification du paramètre action
if (!isset($_REQUEST['action'])) {
	echo("no action");
	exit;
}
$action = $_REQUEST['action'];

// vérification de la présence dans un match
$result = sendRequest("SELECT MATCHES.*, USERS.name AS hisname FROM `MATCHES` JOIN USERS ON (USERS.id = opponent1 OR USERS.id = opponent2) AND USERS.id != '".$cardsUser['id']."' WHERE opponent1 = '".$cardsUser['id']."' OR opponent2 = '".$cardsUser['id']."'");
if ($result->num_rows === 0) {
	echo("not");
	exit;
}
$match = $result->fetch_assoc();
if ($match['opponent1'] == $cardsUser['id']) {
	$you = 0;
	$him = 1;
} else {
	$you = 1;
	$him = 0;
};
$matchId = $match['id'];
$infos = json_decode($match['infos']);

switch ($action) {
	case 'get':
		echo('infos {"myturn":'
			.($infos->playing == $you ? 'true' : 'false')
			.', "me":{"hand":'
			.json_encode($infos->opponents[$you]->hand)
			.', "hp":'
			.$infos->opponents[$you]->hp
			.', "mana":'
			.$infos->opponents[$you]->mana
			.', "discard":'
			.json_encode($infos->opponents[$you]->discard)
			.', "profs":'
			.json_encode($infos->opponents[$you]->profs)
			.', "deck":'
			.count($infos->opponents[$you]->deck)
			.'}, "him":{"name":"'
			.$match["hisname"]
			.'", "hand":'
			.json_encode(sizeof($infos->opponents[$him]->hand))
			.', "hp":'
			.$infos->opponents[$him]->hp
			.', "mana":'
			.$infos->opponents[$him]->mana
			.',"profs":'
			.json_encode($infos->opponents[$him]->profs)
			.', "discard":'
			.json_encode($infos->opponents[$him]->discard)
			.', "deck":'
			.count($infos->opponents[$him]->deck)
			.'}, "place":'
			.json_encode($infos->place)
			.', "start":'
			.$infos->start
			.', "end":'
			.$infos->end
			.', "now":'
			.time()
			.'}');
		break;
	case 'playcard':
		if ($infos->playing != $you) exit('not your turn');
		if (isset($_REQUEST['card'])) {
			$cardIndex = intval($_REQUEST['card']);
			if (count($infos->opponents[$you]->hand) <= $cardIndex) {
				exit('not in hand');
			}
			$card = $infos->opponents[$you]->hand[$cardIndex];
			if ($card->cost > $infos->opponents[$you]->mana) {
				exit('need mana');
			}
			$toreturn = playCard($infos, $card);
			save($infos, $matchId);
			echo('success '.json_encode($toreturn));
		} else {
			exit('need args');
		}
		break;
	case 'attack':
		if ($infos->playing != $you) exit('not your turn');
		if (isset($_REQUEST['card'], $_REQUEST['n'])) {
			$cardIndex = intval($_REQUEST['card']);
			if (count($infos->opponents[$you]->profs) <= $cardIndex)
				exit('not in profs');
			$card = $infos->opponents[$you]->profs[$cardIndex];
			if (count($card->scripts) <= intval($_REQUEST['n'])) exit('this attack doesn\'t exist');
			$toreturn = profCardAttack($infos, $card, $_REQUEST['n']);
			save($infos, $matchId);
			echo('success '.json_encode($toreturn));
		} else {
			exit('need args');
		}
		break;
	case 'endturn':
		if ($infos->playing != $you) exit('not your turn');
		$toreturn = endTurn($infos, $you, $him);
		save($infos, $matchId);
		echo('success '.json_encode($toreturn));
		break;
	case 'giveup':
	    $toreturn = [];
	    attackProf($infos, $infos->opponents[$you], 10000, true);
	    save($infos, $matchId);
		echo('success '.json_encode($toreturn));
	    break; //Modif de Léo
	default:
		echo('unknow action');
}

if ($infos->end <= time()) {
	endTurn($infos, $infos->playing, $infos->playing===0?1:0);
	save($infos, $matchId);
}

exit;

function playCard($infos, $card) {
    global $you;
    $infos->opponents[$you]->mana -= $card->cost;
    foreach ($card->scripts as $script) {
		if (getScriptName($script) == 'playcardcondition') {
			preg_match_all('/[^\[\]]+/', $script, $values);
			$values = $values[0];
			if (!getScriptCondition($infos, $values[1], $card)) exit('unfilledplaycondition');
		}
	}
	if ($card->type == 'prof') {
		$toreturn = playProfCard($infos, $card);
	} else if ($card->type == 'effect') {
		$toreturn = applyEffectCard($infos, $card);
	} else { // place
		$toreturn = setNewPlace($infos, $card);
	}
	return $toreturn;
}

function endTurn($infos, $ender, $starter) {
	$infos->playing = $starter;
	$infos->start = time();
	$infos->end = time()+TURN_TIME;
	foreach ($infos->opponents[$ender]->profs as $prof) { // à la fin du tour
		if (isset($prof->mi)) unset($prof->mi);
		foreach (array('slp', 'prl', 'efr', 'elc') as $effect)
			if (isset($prof->$effect)) {
				$prof->$effect--;
				if ($prof->$effect == 0) unset($prof->$effect);
			}
		if (isset($prof->elc) && $prof->elc>0) attackProf($infos, $prof, ELECTRIFY_DAMAGE, true); // dégâts de l'electrisation
	}
	$toreturn = applyPlaceScript($infos, "onturn", array()); //Modif de Léo // Kévin : ou ça ?
	foreach ($infos->opponents[$starter]->profs as $prof) { // au debut du tour
		$prof->eg = false;
		$prof->t++;
		foreach ($prof->scripts as $script) { // modif de Kévin
		    if (getScriptName($script) == 'onturn') {
			    foreach (applyScript($infos, $script, $prof) as $r)
				    array_push($toreturn, $r);
		    }
		}
	}
	if ($infos->mL < MAX_MANA) $infos->mL += .5;
	$infos->opponents[$ender]->mana = intval($infos->mL);
	if (count($infos->opponents[$ender]->deck) > 0) {
		array_push($infos->opponents[$ender]->hand, array_shift($infos->opponents[$ender]->deck));
	} else {
		attackProf($infos, $infos->opponents[$ender], 10, true);
	}
	global $match;
	if ($match['opponent'.($ender==0?'2':'1')] == -807) {
	    global $you, $him;
    	$tmp = $you;
    	$you = $him;
    	$him = $tmp;
    	bot($infos);
	}
	return $toreturn;
}

function save($infos, $matchId) {
	sendRequest("UPDATE `MATCHES` SET `infos` = '", json_encode($infos), "' WHERE id = '".$matchId."'");
}

function playProfCard($infos, $card) {
	global $you;
	$toreturn = [];
	$card->mi = true;
	$card->t = 0;
	$key = array_search($card, $infos->opponents[$you]->hand);
	array_splice($infos->opponents[$you]->hand, $key, 1);
	array_push($infos->opponents[$you]->profs, $card);
	foreach ($card->scripts as $script) {
		if (getScriptName($script) == 'onplaycard') {
			foreach (applyScript($infos, $script, $card) as $r)
				array_push($toreturn, $r);
		}
	}
	$toreturn = array_merge($toreturn, applyPlaceScript($infos, "onsummon", array("summoned"=>$card)));
	return $toreturn;
}

function applyEffectCard($infos, $card) {
	global $you;
	$toreturn = [];
	$key = array_search($card, $infos->opponents[$you]->hand);
	array_splice($infos->opponents[$you]->hand, $key, 1);
	foreach ($card->scripts as $script) {
		if (getScriptName($script) == 'onplaycard') {
			foreach (applyScript($infos, $script, $card) as $r)
				array_push($toreturn, $r);
		}
	}
	array_push($infos->opponents[$you]->discard, $card);
	return $toreturn;
}

function setNewPlace($infos, $card) {
	global $you;
	$toreturn = [];
	$key = array_search($card, $infos->opponents[$you]->hand);
	array_splice($infos->opponents[$you]->hand, $key, 1);
	array_push($infos->place, $card);
	foreach ($card->scripts as $script) {
		if (getScriptName($script) == 'onplaycard') {
			foreach (applyScript($infos, $script, $card) as $r)
			array_push($toreturn, $r);
		}
	}
	return $toreturn;
}

function profCardAttack($infos, $card, $n) {
	$toreturn = [];
	if (isset($card->eg) && $card->eg) exit('engaged');
	if (isset($card->mi) && $card->mi) exit('mi');
	if (isset($card->slp) && $card->slp) exit('sleeping');
	if (isset($card->prl) && $card->prl) exit('paralysed');
	if (isset($card->efr) && $card->efr) exit('affraid');
	$card->eg = true;
	if (getScriptName($card->scripts[$n]) == 'onaction') {
		foreach (applyScript($infos, $card->scripts[$n], $card) as $r)
			array_push($toreturn, $r);
	}
	/*if (getScriptName($card->scripts[$n]) == 'onturn') { // modif de leo
		foreach (applyPlaceScript($infos, "onsummon", array("summoned"=>$card)) as $r)
		array_push($toreturn, $r);
    }*/
	return $toreturn;
}

function applyPlaceScript($infos, $scriptName, $eventData) {
	if (count($infos->place) === 0) return [];
	$toreturn = [];
	global $summoned; // tmp
	if (isset($eventData['summoned'])) $summoned = $eventData['summoned']; // tmp
	$theplace = $infos->place[count($infos->place)-1];
	foreach ($theplace->scripts as $script) {
		if (getScriptName($script) == $scriptName) {
			foreach (applyScript($infos, $script, $theplace) as $r)
				array_push($toreturn, $r);
		}
	}
	return $toreturn;
}

function getScriptName($script) {
	if ($script=="") return "";
    	preg_match('/\w*(?=[{\[])/', $script, $scriptName);
	return $scriptName[0];
}

function applyScript($infos, $script, $card) {
	global $you;
	global $him;
	$toreturn = [];
	if (strpos($script, 'onlyfirst') === 0 && $card->t > 1) return $toreturn;
	preg_match('/(?<=}\[)[^\[\]]+(?=\])/', $script, $scriptCondition);
	if (count($scriptCondition) != 0 && !getScriptCondition($infos, $scriptCondition[0], $card)) return $toreturn;
	preg_match_all('/\w+\([^ ]*\)(\[[^\[\]]+\]|)/', $script, $actions);
	//print_r($actions);
	foreach ($actions[0] as $action) {
		preg_match('/\w+(?=\()/', $action, $actionName);
		preg_match('/(?<=\)\[)[^\[\]]+(?=\])/', $action,  $actionCondition);
		//print_r($actionCondition);
		if (count($actionCondition) != 0 && !getScriptCondition($infos, $actionCondition[0], $card)) continue;
		preg_match_all('/[^\(,]+(?=[,\)])/', $action, $parameters);
		$parameters = $parameters[0];
		//print_r($parameters);
		switch ($actionName[0]) {
			case 'attackif': // profs, damage, condition, coeff
				if (getScriptCondition($infos, $parameters[2], $card)) {
					$profs = getScriptProfs($infos, $parameters[0], $card);
					foreach ($profs as $prof)
						array_push($toreturn, attackProf($infos, $prof, getScriptValue($infos, $parameters[1], $card)*getScriptValue($infos, $parameters[3], $card)+getStrength($card), false));
					break;
				} // else ⬇
			case 'attack': // profs, damage
				$profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof)
					array_push($toreturn, attackProf($infos, $prof, getScriptValue($infos, $parameters[1], $card)+getStrength($card), isset($parameters[2])?$parameters[2]=="true":false));
				break;
			case 'heal': // profs, heal
				$profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof) {
					$prof->hp = min($prof->hp+getScriptValue($infos, $parameters[1], $card), $prof->hpmax);
					$r = new stdClass();
					$r->name = 'heal';
	                $r->target = getProfIndex($infos, $prof);
	                array_push($toreturn, $r);
				}
				break;
			case 'sleep': // profs, time
				$profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof) $prof->slp = getScriptValue($infos, $parameters[1], $card);
				break;
			case 'wakeup': // profs
				$profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof) unset($prof->slp);
				break;
			case 'seedraw': // number
				$r = new stdClass();
				$r->name = 'seedraw';
				$r->cards = array_slice($infos->opponents[$you]->deck, 0, getScriptValue($infos, $parameters[0], $card));
				array_push($toreturn, $r);
				break;
			case 'seedrawhim': // number // TODO : programmer l'animation! //Modif de Léo
				$r = new stdClass();
				$r->name = 'seedrawhim';
				$r->cards = array_slice($infos->opponents[$him]->deck, 0, getScriptValue($infos, $parameters[0], $card));
				array_push($toreturn, $r);
				break;
			case 'kick': // profs
				$profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof)
					attackProf($infos, $prof, 1000000, true);
				break;
			case 'paralyse': // profs, time
				$profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof) $prof->prl = getScriptValue($infos, $parameters[1], $card);
				break;
			case 'affraid': // profs, time
				$profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof) $prof->efr = getScriptValue($infos, $parameters[1], $card);
				break;
			case 'courage': // profs
				$profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof) unset($prof->efr);
				break;
			case 'electrify': // profs, time
				$profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof) {
				    $prof->elc = getScriptValue($infos, $parameters[1], $card);
				    $r = new stdClass();
				    $r->name = 'electrify';
	                $r->target = getProfIndex($infos, $prof);
				    array_push($toreturn, $r);
				}
				break;
			case 'diselectrify': // profs
				$profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof) unset($prof->elc);
				break;
			case 'setvar': // varname, value
				$varname = '_'.$parameters[0];
				$card->$varname = getScriptValue($infos, $parameters[1], $card);
				break;
			case 'leaveplace': //
				array_pop($infos->place); // actuellement suppr et pas envoyé en défausse
				break;
			case 'delmana': // who, value
				if ($parameters[0] == 'you') $infos->opponents[$you]->mana -= getScriptValue($infos, $parameters[1], $card);
				if ($parameters[0] == 'him') $infos->opponents[$him]->mana -= getScriptValue($infos, $parameters[1], $card);
				break;
			case 'givemana': // who, value
				if ($parameters[0] == 'you') $infos->opponents[$you]->mana += getScriptValue($infos, $parameters[1], $card);
				if ($parameters[0] == 'him') $infos->opponents[$him]->mana += getScriptValue($infos, $parameters[1], $card);
				break;
			case 'draw': // who
			    $drawer = ($parameters[0] == 'him') ? $him : $you;
				if (count($infos->opponents[$drawer]->deck) > 0)
					array_push($infos->opponents[$drawer]->hand, array_shift($infos->opponents[$drawer]->deck));
				break;
			case 'drop': // who, index // TODO : seul random est valable actuellement
			    $dropper = ($parameters[0] == 'him') ? $him : $you;
			    if ($parameters[1]=='random') $parameters[1]=random_int(0, count($infos->opponents[$dropper]->hand)-1);
				if (count($infos->opponents[$dropper]->hand) > 0)
					array_push($infos->opponents[$dropper]->discard, array_splice($infos->opponents[$dropper]->hand, intval($parameters[1]), 1)[0]);
				break;
			case 'addshield': // profs, amount
			    $profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof)
				    $prof->shield = (isset($prof->shield)?$prof->shield:0) + getScriptValue($infos, $parameters[1], $card);
			    break;
			case 'removeshield': // profs, amount
			    $profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof) if (isset($prof->shield)) {
				    $parameter[1] = getScriptValue($infos, $parameters[1], $card);
				    if ($prof->shield <= $parameters[1]) unset($prof->shield);
				    else $prof->shield -= $parameters[1];
				}
			    break;
			case 'addstrength': // profs, amount
			    $profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $prof)
				    $prof->strength = (isset($prof->strength)?$prof->strength:0) + getScriptValue($infos, $parameters[1], $card);
			    break;
			case 'summon': // amount, cardId, proftype, pv, damage
			    for ($i = 0; $i < getScriptValue($infos, $parameters[0], $card); $i++) {
			        $minion = new stdClass();
			        $minion->id = intval($parameters[1]);
			        $minion->type = 'prof';
			        $minion->proftype = $parameters[2];
			        $minion->hp = $minion->hpmax = getScriptValue($infos, $parameters[3], $card);
			        $minion->scripts = array("onaction{attack(target,".getScriptValue($infos, $parameters[4], $card).")}", "");
			        $minion->mi = true;
			        $minion->t = 0;
			        $minion->cost = 0; // non affiché ?
			        array_push($infos->opponents[$you]->profs, $minion);
			    }
			    break;
		    case 'convert': // profs
		        $profs = getScriptProfs($infos, $parameters[0], $card);
				foreach ($profs as $toConvert) {
				    for ($i = 0; $i < count($infos->opponents[$him]->profs); $i++) {
				        if ($infos->opponents[$him]->profs[$i] == $toConvert) {
				            array_push($infos->opponents[$you]->profs, array_splice($infos->opponents[$him]->profs, $i, 1)[0]);
				            break;
				        }
				    }
				    
				}
		        break;
	        case 'disengage': // profs
	            $profs = getScriptProfs($infos, $parameters[0], $card);
	            foreach ($profs as $prof) {
				    $prof->eg = false;
				    $prof->mi = false;
	            }
	            break;
	        case 'invoc': // player, cardId 
		        //CA MARCHE PAS!!!!! KEVIIIIIIIIIN!!!
		        //$toreturn = playCard($infos, getScriptValue($infos, $parameters[1], $card));
			    $result = sendRequest("SELECT * FROM CARDS WHERE id='", getScriptValue($infos, $parameters[1], $card), "'")->fetch_assoc();
			    $card = new stdClass();
        		$card->id = intval($result['id']);
        		$card->p = false;
        		$card->type = $result['type'];
        		$card->scripts = array($result['script1'], $result['script2']);
        		$cardinfos = json_decode($result['infos']);
        		$card->cost = intval($cardinfos->cost);
        		if ($card->type == 'prof') $card->hp = intval($cardinfos->hp);
        		if ($card->type == 'prof') $card->hpmax = intval($cardinfos->hp);
        		if ($card->type == 'prof') $card->proftype = $cardinfos->proftype;
			    array_push($infos->opponents[getScriptValue($infos, $parameters[0], $card)=='him'?$him:$you]->profs, $card);
			    break;
		}
	}
	return $toreturn;
}

function attackProf($infos, $prof, $damage, $ignoredef) {
	global $him;
	global $you;
	$r = new stdClass();
	if (!$ignoredef && ($prof == $infos->opponents[$him] || $prof == $infos->opponents[$you])) // RÈGLES DES DÉFENSEURS, ENFIIIIIN !!!
	    foreach ($prof->profs as $def)
	        if (!isset($def->eg) && !isset($def->mi) && !isset($def->slp) && !isset($def->prl))
	            exit("defensors");
    $r->name = 'attack';
	$r->damage = $damage;
	$r->target = getProfIndex($infos, $prof);
	if (isset($prof->shield)) {
	    if ($prof->shield <= $damage) {
	        $damage -= $prof->shield;
	        unset($prof->shield);
	    } else {
	        $prof->shield -= $damage;
	        $damage = 0;
	    }
	}
	$prof->hp -= $damage;
	if ($prof->hp <= 0) {
		if ($prof != $infos->opponents[$him] && $prof != $infos->opponents[$you]) {
			for ($i = 0; $i < count($infos->opponents[$him]->profs); $i++) {
				if ($infos->opponents[$him]->profs[$i]->hp <= 0)
					array_push($infos->opponents[$him]->discard, array_splice($infos->opponents[$him]->profs, $i, 1)[0]);
			}
			for ($i = 0; $i < count($infos->opponents[$you]->profs); $i++) {
				if ($infos->opponents[$you]->profs[$i]->hp <= 0)
					array_push($infos->opponents[$you]->discard, array_splice($infos->opponents[$you]->profs, $i, 1)[0]);
			}
		} else {
			global $match;
			$winnerId = $prof==$infos->opponents[1] ? $match['opponent1'] : $match['opponent2'];
			$loserId = $prof==$infos->opponents[0] ? $match['opponent1'] : $match['opponent2'];
			sendRequest("DELETE FROM MATCHES WHERE id = '", $match['id'], "'");
			if ($winnerId < -1) exit('endgame {"result":"lose","opponent":{"name":"B0T","trophies":"🤖"},"rewards":[]}');
			if ($loserId < -1 && $winnerId >= -1) {
			    $winnerReward = 4;
			    $winner = sendRequest("SELECT CARDSUSERS.*, USERS.name FROM CARDSUSERS JOIN USERS ON USERS.id = CARDSUSERS.id WHERE CARDSUSERS.id = '", $winnerId, "'");
			    $winner=$winner->fetch_assoc();
			    sendRequest("UPDATE `CARDSUSERS` SET `rewardLevel` = '", intval($winner['rewardLevel'])+$winnerReward,"' WHERE `id` = '", $winnerId, "'"); //Modif de Léo et Edwin
			}
			if ($loserId < -1) exit('endgame {"result":"win","opponent":{"name":"B0T","trophies":"🤖"},"rewards":[{"type":"rewardLevel","amount":'.$winnerReward.'}]}'); 
			$winner = sendRequest("SELECT CARDSUSERS.*, USERS.name FROM CARDSUSERS JOIN USERS ON USERS.id = CARDSUSERS.id WHERE CARDSUSERS.id = '", $winnerId, "'")->fetch_assoc();
			$loser = sendRequest("SELECT CARDSUSERS.*, USERS.name FROM CARDSUSERS JOIN USERS ON USERS.id = CARDSUSERS.id WHERE CARDSUSERS.id = '", $loserId, "'")->fetch_assoc();
			$winnerGain = ceil(1/(1+exp((intval($winner['trophies'])-intval($loser['trophies']))/100))*TROPHIES);
			$loserLost = floor(1/(1+exp((intval($winner['trophies'])-intval($loser['trophies']))/100))*TROPHIES);
			$winnerReward = $prof->hp > -839 ? 3 : 15;
			$loserReward = $prof->hp > -839 ? 1 : 8;
			sendRequest("UPDATE `CARDSUSERS` SET `rewardLevel` = '", intval($winner['rewardLevel'])+$winnerReward, "', `trophies` = '", intval($winner['trophies'])+$winnerGain, "' WHERE `CARDSUSERS`.`id` = '", $winnerId, "'");
			sendRequest("UPDATE `CARDSUSERS` SET `rewardLevel` = '", intval($loser['rewardLevel'])+$loserReward, "', `trophies` = '", max(0, intval($loser['trophies'])-$loserLost), "' WHERE `CARDSUSERS`.`id` = '", $loserId, "'");
			if ($prof==$infos->opponents[$you]) {
			    exit('endgame {"result":"lose","opponent":{"name":"'.$winner['name'].'","trophies":'.$winner['trophies'].'},"rewards":[{"type":"trophy","amount":-'.$loserLost.'},{"type":"rewardLevel","amount":'.$loserReward.'}]}');
			} else {
			    exit('endgame {"result":"win","opponent":{"name":"'.$loser['name'].'","trophies":'.$loser['trophies'].'},"rewards":[{"type":"trophy","amount":'.$winnerGain.'},{"type":"rewardLevel","amount":'.$winnerReward.'}]}');
			}
		}
	}
	return $r;
}

function getProfIndex($infos, $prof) { // à utiliser seuelement pour les retours client // Ajout de Kévin
    global $him, $you;
    if ($prof==$infos->opponents[$him]) return 'him';
	else if ($prof==$infos->opponents[$you]) return 'you';
	else if (array_search($prof, $infos->opponents[$him]->profs, false) !== false) return 'h'.array_search($prof, $infos->opponents[$him]->profs, false);
	else if (array_search($prof, $infos->opponents[$you]->profs, true) !== false) return 'y'.array_search($prof, $infos->opponents[$you]->profs, true);
	return 'unknow';
}

function getStrength($card) {
    if (!isset($card->strength)) return 0;
    return intval($card->strength);
}

function getScriptProfs($infos, $expr, $card) {
	global $him;
	global $you;
	preg_match_all('/[^\[\]]+/', $expr, $values);
	$values = $values[0];
	/*preg_match('/(targetofyou|targetofhim|target)([0-9]*)/', $values, $targetExpr); ////// NOUVEAU SYSTEME DE TARGETS !!!
	if (count($targetExpr) !== 0) {
	    global $targets;
	    if (isset($targets)) $profs = $targets;
	    else {
	        $target = [];
	        if ($targetExpr[1] == 'targetofyou') {
	            for ($i = 0; $i < isset($targetExpr[2])?intval($targetExpr[2]):1; $i++) {
	                if (!isset($_REQUEST['target'.($i==0?'':$i+1)])) exit('need target'.($i+1));
	                $targetIndex = $_REQUEST['target'.($i==0?'':$i+1)];
	                if (count($infos->opponents[$you]->profs) <= $targetIndex || $targetIndex < 0) exit('target'.($i+1).' not in profs');
	                array_push($targets, $infos->opponents[$you]->profs[$targetIndex]);
	            }
	        } else if ($targetExpr[1] == 'targetofhim') {
	            for ($i = 0; $i < isset($targetExpr[2])?intval($targetExpr[2]):1; $i++) {
	                if (!isset($_REQUEST['target'.($i==0?'':$i+1)])) exit('need target'.($i+1));
	                $targetIndex = $_REQUEST['target'.($i==0?'':$i+1)];
	                if (count($infos->opponents[$him]->profs) <= $targetIndex || $targetIndex < 0) exit('target'.($i+1).' not in profs');
	                array_push($targets, $infos->opponents[$him]->profs[$targetIndex]);
	            }
	        } else {
	            for ($i = 0; $i < isset($targetExpr[2])?intval($targetExpr[2]):1; $i++) {
	                if (!isset($_REQUEST['target'.($i==0?'':$i+1)])) exit('need target'.($i+1));
	                $targetIndex = $_REQUEST['target'.($i==0?'':$i+1)];
	                if (strpos($targetIndex, 'F')===0) $owner = $you;
	                else if (strpos($targetIndex, 'f')===0) $owner = $him;
	                else exit('target'.($i+1).' is unknow');
	                $targetIndex = substr($targetIndex, 1);
	                if (count($infos->opponents[$owner]->profs) <= $targetIndex || $targetIndex < 0) exit('target'.($i+1).' not in profs');
	                array_push($targets, $infos->opponents[$owner]->profs[$targetIndex]);
	            }
	        }
	    }
	} else*/
	switch($values[0]) {
		case 'all':
			$profs = array_reverse(array_merge($infos->opponents[$him]->profs, $infos->opponents[$you]->profs));
			break;
		case 'allofhim':
			$profs = array_reverse($infos->opponents[$him]->profs);
			break;
		case 'allofyou':
			$profs = array_reverse($infos->opponents[$you]->profs);
			break;
		case 'target': // deprecated ???
		case 'targetofhim':
			global $targetofhim;
			if (isset($targetofhim)) $profs = $targetofhim;
			else if (isset($_REQUEST['target'])) {
				$targetId = $_REQUEST['target'];
				if ($targetId != 'him' && count($infos->opponents[$him]->profs) < $targetId+1) exit('target not in profs');
				$profs = $targetofhim = array($targetId == 'him' ? $infos->opponents[$him] : $infos->opponents[$him]->profs[$targetId]);
				break;
			} else exit('need targetofhim');
			break;
		case 'target2': // deprecated
		case 'target2ofhim':
			global $target2ofhim;
			if (isset($target2ofhim)) $profs = $target2ofhim;
			else if (isset($_REQUEST['target'], $_REQUEST['target2'])) {
				$targetId = $_REQUEST['target'];
				$target2Id = $_REQUEST['target2'];
				if ($targetId == $target2Id) exit('same targets');
				if ($targetId != 'him' && count($infos->opponents[$him]->profs) < $targetId+1) exit('target not in profs');
				if ($target2Id != 'him' && count($infos->opponents[$him]->profs) < $target2Id+1) exit('target2 not in profs');
				$profs = $target2ofhim = array($targetId == 'him' ? $infos->opponents[$him] : $infos->opponents[$him]->profs[$targetId], $target2Id == 'him' ? $infos->opponents[$him] : $infos->opponents[$him]->profs[$target2Id]);
				break;
			} else exit('need target2ofhim');
			break;
		case 'targetofyou':
			global $targetofyou;
			if (isset($targetofyou)) $profs = $targetofyou;
			else if (isset($_REQUEST['target'])) {
				$targetId = $_REQUEST['target'];
				if (count($infos->opponents[$you]->profs) < $targetId+1) exit('target not in profs');
				$profs = $targetofhim = array($infos->opponents[$you]->profs[$targetId]);
				break;
			} else exit('need target');
			break;
		case 'target2ofyou':
			global $target2ofyou;
			if (isset($target2ofyou)) $profs = $target2ofyou;
			else if (isset($_REQUEST['target'], $_REQUEST['target2'])) {
				$targetId = $_REQUEST['target'];
				$target2Id = $_REQUEST['target2'];
				if ($targetId == $target2Id) exit('same targets');
				if (count($infos->opponents[$you]->profs) < $targetId+1) exit('target not in profs');
				if (count($infos->opponents[$you]->profs) < $target2Id+1) exit('target2 not in profs');
				$profs = $target2ofyou = array($infos->opponents[$you]->profs[$targetId], $infos->opponents[$you]->profs[$target2Id]);
				break;
			} else exit('need target');
			break;
		case 'it':
			$profs = array($card);
			break;
		case 'you':
			$profs = array($infos->opponents[$you]);
			break;
		case 'him':
			$profs = array($infos->opponents[$him]);
			break;
		case 'summoned':
			global $summoned;
			$profs = array($summoned);
			break;
		default:
			exit("malformed prof selector : ".$expr);
	}
	for ($i = 1; $i < count($values); $i++)
		foreach ($profs as $prof)
			if (!getScriptCondition($infos, $values[$i], $prof)) {
				$key = array_search($prof, $profs);
				array_splice($profs, $key, 1);
			}
	return $profs;
}

function getScriptCondition($infos, $expr, $card) {
    if (strpos($expr, '|') !== false) {
		$values = preg_split('/\|/', $expr);
		return getScriptCondition($infos, $values[0], $card) | getScriptCondition($infos, $values[1], $card);
	} else if (strpos($expr, '&') !== false) {
		$values = preg_split('/&/', $expr);
		return getScriptCondition($infos, $values[0], $card) & getScriptCondition($infos, $values[1], $card);
	} else if (strpos($expr, '=') !== false) {
	    if (strpos($expr, '!=') !== false) {
	        $values = preg_split('/!=/', $expr); //Modif de Léo
		    return getScriptValue($infos, $values[0], $card) != getScriptValue($infos, $values[1], $card) ? 1 : 0;
	    } else {      
		$values = preg_split('/=/', $expr);
		return getScriptValue($infos, $values[0], $card) == getScriptValue($infos, $values[1], $card) ? 1 : 0;
	    }
	} else if (strpos($expr, '!') !== false) {
		$values = preg_split('/!/', $expr); //Modif de Léo
		return getScriptValue($infos, $values[0], $card) != getScriptValue($infos, $values[1], $card) ? 1 : 0;
	} else if (strpos($expr, '>') !== false) {
		$values = preg_split('/>/', $expr);
		return getScriptValue($infos, $values[0], $card) > getScriptValue($infos, $values[1], $card);
	} else if (strpos($expr, '<') !== false) {
		$values = preg_split('/</', $expr);
		return getScriptValue($infos, $values[0], $card) < getScriptValue($infos, $values[1], $card);
	} else if ($expr == 'targetsleep') {
		$target = getScriptProfs($infos, 'target', null)[0];
		return isset($target->slp) && $target->slp;
	}
	$words = preg_split('/_/', $expr);
	switch ($words[0]) {
		case 'isplace': // cardId
			return getScriptValue($infos, $words[1], $card) == $infos->place[count($infos->place)-1]->id;
		case 'in': // profs, cardId
		    $profs = getScriptProfs($infos, $words[1], $card);
		    foreach ($profs as $prof)
		        if ($prof->id == getScriptValue($infos, $words[2], $card))
		            return true;
		    return false;
	}
}

function getScriptValue($infos, $expr, $card) {
	if (is_numeric($expr)) return intval($expr);
	if (strpos($expr, '+') !== false) {
		$values = preg_split('/\+/', $expr);
		return getScriptValue($infos, $values[0], $card) + getScriptValue($infos, $values[1], $card);
	} else if (strpos($expr, '-') !== false) {
		$values = preg_split('/\-/', $expr);
		return getScriptValue($infos, $values[0], $card) - getScriptValue($infos, $values[1], $card);
	} else if (strpos($expr, '*') !== false) {
		$values = preg_split('/\*/', $expr);
		return getScriptValue($infos, $values[0], $card) * getScriptValue($infos, $values[1], $card);
	} else if (strpos($expr, '%') !== false) {
		$values = preg_split('/%/', $expr);
		return getScriptValue($infos, $values[0], $card) % getScriptValue($infos, $values[1], $card);
	}
	switch ($expr) {
		case 'type':
		    //return $card->proftype;
		    if (isset($card->proftype))
			    return $card->proftype;
		    else
		        return "player"; //Modif de Léo
		case 'place':
		    if (isset($infos->place[count($infos->place)-1]->id)) {
		        return $infos->place[count($infos->place)-1]->id;
		    }
		    return -1;
	}
	$words = preg_split('/_/', $expr);
	switch ($words[0]) {
		case 'getvar':
			$varname = '_'.$words[1];
			if (!isset($card->$varname)) $card->$varname = 0;
			return $card->$varname;
		case 'random':
		    return random_int(0, getScriptValue($infos, preg_replace("/".preg_quote($expr,"/")."_/", "", $words[1], 1), $card));
		case 'count':
		    return count(getScriptProfs($infos, preg_replace("/".preg_quote($expr,"/")."_/", "", $words[1], 1), $card));
	}
	if (isset($card->$expr)) return $card->$expr;
	return $expr;
}

function bot($infos) {
    global $you; // le bot
    global $him; // l'adversaire
    global $targetofhim;
    global $target2ofhim; 
    global $targetofyou;
    global $target2ofyou; // actuellement les cibles doivent soit être mises dans $targetofhim, $target2ofhim, $targetofyou ou $target2ofhim selon le script
    //$infos->opponents[$you]->hand // = main du bot
    //$infos->opponents[$him]->profs // = profs sur le terrain du bot
    //global $targetofhim; $targetofhim = array($z); // = pour définir une cible $z lors d'une attaque
    //global $target2ofyou; $target2ofyou = array($z, $y) // = pour définir une double cible $z & $y (genre abo grev.)
    //playCard($infos, $infos->opponents[$you]->hand[0]); // = jouer la première carte de la main
    //profCardAttack($infos, $infos->opponents[$you]->profs[0], 1); // attaquer avec le premier prof avec la deuxieme attaque
    //count($infos->opponents[$you]->hand) // = nombre de cartes en main
    //random_int($n, $m) // = nombre entier aléatoire entre $n et $m
    // si besoin d'une fonction php : https://www.php.net/manual/fr/ OU moteur de recherche genre Google
    // si besoin d'une fonction pokéProf, bah text me
    
    // à toi de jouer
    // ...
    //$j = 0;
    $targetofhim = array(count($infos->opponents[$him]->profs)==0 ? $infos->opponents[$him] : $infos->opponents[$him]->profs[random_int(0, count($infos->opponents[$him]->profs)-1)]);
    $target2ofhim = array(0,1); // pas utilisé dans le deck du bot
    $targetofyou = count($infos->opponents[$you]->profs)==0 ? null : array($infos->opponents[$you]->profs[0]); // position stable only ?
    $target2ofyou = count($infos->opponents[$you]->profs)<2 ? null : array($infos->opponents[$you]->profs[0],$infos->opponents[$you]->profs[1]); // pas utilisé dans le deck du bot
    if (random_int(0, 1) == 0) { // le bot joue des cartes
        for ($i = 0; $i < count($infos->opponents[$you]->hand); $i++) {
            if ($infos->opponents[$you]->hand[$i]->cost <= $infos->opponents[$you]->mana) {
                $no = false; for ($j = 0; $j < 2; $j++) if (strpos($infos->opponents[$you]->hand[$i]->scripts[$j], "targetofyou")!==false && $targetofyou==null) $no = true; if ($no) continue;
                playCard($infos, $infos->opponents[$you]->hand[$i]);
            }
        }
    } else {
        for ($i = count($infos->opponents[$you]->hand)-1; $i >= 0; $i--) {
            if ($infos->opponents[$you]->hand[$i]->cost <= $infos->opponents[$you]->mana) {
                $no = false; for ($j = 0; $j < 2; $j++) if (strpos($infos->opponents[$you]->hand[$i]->scripts[$j], "targetofyou")!==false && count($infos->opponents[$you]->profs)==0) $no = true; if ($no) continue;
                playCard($infos, $infos->opponents[$you]->hand[$i]);
            }
        }
    }
    
    //$randomcard = 0;
    for ($i = 0; $i < count($infos->opponents[$you]->profs); $i++) { // le bot fait attaquer ses profs
        if ((isset($infos->opponents[$you]->profs[$i]->eg) && $infos->opponents[$you]->profs[$i]->eg) || isset($infos->opponents[$you]->profs[$i]->slp) || isset($infos->opponents[$you]->profs[$i]->prl) || isset($infos->opponents[$you]->profs[$i]->mi) || isset($infos->opponents[$you]->profs[$i]->efr)) continue; // passage au prof suivant car engagé, endormi, paralysé, récement invoqué ou effrayé
        //echo "Le bot fait attaquer sont $i ème prof";
        $randomattack = 0;
        $randomcard = random_int(0, (count($infos->opponents[$you]->profs))-1);
        //print_r($infos->opponents[$you]->profs[$i]);
        if (getScriptName($infos->opponents[$you]->profs[$i]->scripts[1]) == 'onaction') {
            if (getScriptName($infos->opponents[$you]->profs[$i]->scripts[0]) == 'onaction') {
                $randomattack = random_int(0, 1);
            }
            else {
                $randomattack = 1;
            }
        }
        else {
            $randomattack = 0;
        }
        $targetofhim = array(count($infos->opponents[$him]->profs)==0 ? $infos->opponents[$him] : $infos->opponents[$him]->profs[random_int(0, count($infos->opponents[$him]->profs)-1)]);
        $target2ofhim = array(0,1); // pas utilisé dans le deck du bot
        $targetofyou = array(array($infos->opponents[$you]->profs[0])); // pareil ?
        $target2ofyou = array(0,1); // pas utilisé dans le deck du bot
        profCardAttack($infos, $infos->opponents[$you]->profs[$i], $randomattack);
        /*$randomattack = random_int(0, (count($infos->opponents[$him]->profs))-1);
        $targetofhim = array($randomattack);*/                  // si strpos($card->$scripts[0 ou 1], "targetofyou") alors il faut compléter targetof you etc.
    }
    endTurn($infos, $you, $him);
} //Modif de Léo



