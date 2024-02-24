<?php
include('api/init.php');
// connexion à un compte
$user = login(true);
// transfert d'erreur s'il y en a une
if (isset($_REQUEST['error'])) {
	if (defined('POKEPROF_WEBHOOK_ERROR') && POKEPROF_WEBHOOK_ERROR!=null) {
    	$content = '__Erreur JS (play.php) : **'.$_REQUEST['error'].'**__'."\n".'Ligne : '.(!isset($_REQUEST['line'])||$_REQUEST['line']==''?'???':$_REQUEST['line'])."\n".'Fichier : '.(!isset($_REQUEST['file'])||$_REQUEST['file']==''?'???':$_REQUEST['file'])."\n".'Stack : '.(!isset($_REQUEST['stack'])||$_REQUEST['stack']==''?'???':$_REQUEST['stack']);
    	sendToDiscord(POKEPROF_WEBHOOK_ERROR, $content);
	}
    exit(header('Location: play.php'));
}
?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8" />
		<title>Jouer | PokéProf</title>
		<link rel="stylesheet" href="style.css?<?=time()?>" />
		<link rel="stylesheet" href="play.css" />
		<script src="/utils.js?2"></script>
		<script src="cards.js?<?=time()?>"></script>
		<link rel="icon" type="image/png" href="assets/icon.png" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
	</head>
	<body>
		<span id="logged">Vous êtes connecté en tant que <?= htmlspecialchars($user['name']) ?></span>
		<div id="game">
			<!--<div id="veil" style="display: none;"></div>-->
			<span id="end-turn" class="button" onclick="endTurn()">Finir le tour</span>
			<span id="give-up" class="button" onclick="giveUp()">Abandonner</span>
			<div class="hp-container" id="player-hp-container" title="Tes points de vie">
				<span id="player-hp"></span>
			</div>
			<div class="mana-container" id="player-mana-container" title="Ton mana">
				<span id="player-mana"></span>
			</div>
			<div class="hp-container" id="opponent-hp-container" title="Points de vie de l'adversaire">
				<span id="opponent-hp"></span>
			</div>
			<div class="mana-container" id="opponent-mana-container" title="Mana de l'adversaire">
				<span id="opponent-mana"></span>
			</div>
			<span id="opponent-name">Adversaire</span>
			<span id="player-name">Joueur</span>
			<div id="opponent" onclick="onClickCard(this)">
				<span>Votre adversaire</span>
			</div>
			<div id="select-attack" style="display: none;" onclick="if(!tuto)this.style.display='none';">
				<div id="select-attack-card" onclick="event.stopPropagation()"></div>
				<div id="select-attack-effects" class="effects"></div>
			</div>
			<div id="timer"><div></div></div>
		</div>
		<script>
			var match = undefined
			var historyIndex = undefined;
			var gameDiv = document.getElementById("game");
			var playerHandDivs = [];
			var opponentHandDivs = [];
			var playerFightersDivs = [];
			var opponentFightersDivs = [];
			var playerDrawDiv = undefined;
			var opponentDrawDiv = undefined;
			var playerDiscardDiv = undefined;
			var opponentDiscardDiv = undefined;
			var placeDiv = undefined;
			var playerManaAff = document.getElementById("player-mana");
			var playerHpAff = document.getElementById("player-hp");
			var opponentManaAff = document.getElementById("opponent-mana");
			var opponentHpAff = document.getElementById("opponent-hp");
			var endTurnButton = document.getElementById("end-turn");
			var timerBar = document.querySelector("#timer > div");
			var tuto = <?=isset($_REQUEST['tuto'])?'true':'false'?>;
			window.addEventListener("load", function() {
				if (tuto) {
					initTuto();
				} else {
					get();
					
					setInterval(refreshTimer, 100);
					setInterval(function(){
						if (!match || match.playing == match.playerId || match.ended || stop)
							return;
						getUpdate();
					}, 3000);
				}
			});
			
			function get() {
				sendRequest("POST", "api/match/play.php", "<?=isset($_REQUEST['test'])?'test&':''?>action=get").then(onServerResponse);
			}
			
			function getUpdate() {
				sendRequest("POST", "api/match/play.php", "<?=isset($_REQUEST['test'])?'test&':''?>action=update").then(onServerResponse);
			}
			
			// Quand le serveur répond
			const errors = {
				"not your turn": "Ce n'est pas encore ton tour",
				"engaged": "Ce combattant a déjà effectué une action",
				"mi": "Ce combattant vient d'être invoqué, il ne peut attaquer",
				"sleeping": "Ce combattant est endormi, il ne peut ni attaquer ni défendre",
				"paralysed": "Ce combattant est paralysé, il ne peut ni attaquer ni défendre",
				"affraid": "Ce combattant est effrayé, il ne peut pas attaquer",
				"defensors": "Un combattant adversaire est capable de défendre",
				"unfilled playcondition": "La condtion d'invocation n'est pas complétée",
				"unfilled actioncondition": "La condition de cette action n'est pas complétée"
			}
			function onServerResponse(response) {
				if (response=="not logged" || response=="not") {
					window.location.replace(".");
				} else {
					try{
						response = JSON.parse(response);
					} catch (e) {
						alert("Une erreur interne est survenue 😱, partagez là sur Discord pls\n"+response);
					} finally {
						if (!response.error) {
							if (response.match){
								init(response.match);
							}
							if (response.actions) {
								update(response.actions).catch(function(e){
								    if(window.location.replace)
								        window.location.replace("play.php?error="+encodeURIComponent(e.message)+"&file="+encodeURIComponent(e.fileName)+"&line="+encodeURIComponent(e.lineNumber)+"&stack="+encoreURIComponent(e.stack));
								    else window.location.reload();
								});
							}
						} else {
							aff(response.error in errors ? errors[response.error] : response.error);
							console.error("[Poképrof] "+response.error);
						}
					}
				}
			}
			
			// Initialisation ou réinitialisation du match
			function init(_match) {
				console.info("[Poképrof] "+(match?"Réi":"I")+"nitialisation du match");
				match = _match;
				historyIndex = _match.history.length;
				refresh();
			}
			
			// Initialisation du match tuto
			async function initTuto() {
			    gameDiv.appendChild(createElement("a", {className:"button", href:".", style:{position:"absolute",zIndex:300,top:"10%",right:"10%"}}, "Passer le toturiel"));
				init({playing:0,start:0,end:0,mL:4,place:[],history:[],opponents:[
					{hp:60, mana:4, discard:[], profs:[
						{id:"29",cost:2,scripts:["onaction{attack(target,10)}",""],vars:{},type:"prof",hpmax:30,hp:30,types:["student"],t:0,eg:false,mi:false,slp:0,efr:0,prl:0,elc:0,strength:0,shield:0}
					],deck:3, hand:[
						{id:"11",cost:3,scripts:["onaction{seedraw(2) seedrawhim(1)}","onaction{attackif(target,20,targetsleep,3) wakeup(target)}"],vars:{},type:"prof",hpmax:60,hp:60,types:["math"],t:0,eg:false,mi:false,slp:0,efr:0,prl:0,elc:0,strength:0,shield:0},
						{id:"148",cost:2,scripts:["onplaycard{addstrength(targetofyou,40) electrify(targetofyou,3)}",""],vars:{},type:"effect"}
					], historyIndex:0},
					{hp:60, mana:5, discard:[], profs:[], deck:5, hand:3, historyIndex:0}
				], names:["Toi & Abo", "Botmiz"], trophies:[0,0], playerId:0});
				await displayTuto("Bienvenue dans l'arène Poképrof ! J'étais justement en train de me battre contre le robot de monseigneur Boumiz", {button:"Je veux jouer"});
				await displayTuto("Quel est ce débutant ? On dirait qu'il n'y connait rien, il n'a rien à faire ici", {button:"Ah !", speaker:"boumiz",left:true});
				await displayTuto("Il essaie de t'intimider mais je pense que vous pouvez battre son robot, je vais vous aider !", {button:"Ok"});
				await displayTuto("Regardez, vous avez 60 points de vie, si ceux-ci tombent à 0 vous avez perdu, vous devez faire descendre ceux de l'adversaire", {button:"Ok",showlock:[document.getElementById("player-hp-container")]});
				await displayTuto("Pour cela vous pouvez attaquer avec vos combattants...", {button:"Ok",showlock:playerFightersDivs});
				await displayTuto("Ou jouer des cartes de votre main (combattants, effets, lieux)", {button:"Ok",showlock:playerHandDivs});
				await displayTuto("Et ces dernières vous coûteront de la mana", {button:"Ok",showlock:[document.getElementById("player-mana-container")]});
				await displayTuto("Notre adversaire fonctionne de la même façon, mais pas avec les mêmes cartes\nChaque carte est unique et chaque deck l'est aussi", {button:"Ok"});
				await displayTuto("Trève de bavardage, seul ton talent fera la différence !", {speaker:"boumiz",button:"C'est parti !",left:true});
				await displayTuto("Vous pouvez commencer par attaquer avec la combattant sur le terrain", {show:[playerFightersDivs[0]]});
				await displayTuto("", {show:[document.querySelector("#select-attack .attacks > div")]});
				await displayTuto("Ciblez l'adversaire", {show:[document.getElementById("opponent")]});
				await update([{name:"engage",target:{teamId:0,index:0}},{name:"attack",damage:10,target:{teamId:1,index:"player"},agent:{teamId:0,index:0}}]);
				await displayTuto("Bien joué, vous pouvez maintenant jouer la carte Abo !", {show:[playerHandDivs[0]]});
				await update([{name:"playfightercard",playerId:0,index:0,card:null}]);
				await displayTuto("Nous n'avons plus rien à faire, finissez le tour", {show:[document.getElementById("end-turn")]});
				await update([{name:"nextturn",playing:1,start:0,end:0},{name:"setmana",playerId:0,mana:4},{name:"draw",playerId:0,card:{id:"51",cost:2,scripts:["onplaycard{sleep(target,1)}",""],vars:{},type:"effect"}}]);
				await displayTuto("Votre tour est terminé, vous avez récupéré votre mana et pioché une nouvelle carte. C'est maintenant le tour le l'adversaire", {button:"Voyons voir"});
				await update([{name:"playfightercard",playerId:1,index:1,card:{id:"138",cost:5,scripts:["onaction{attack(target,60) attack(it,20)}","onaction{scriptdézenfer}"],vars:{},type:"prof",hpmax:60,hp:60,types:["physik"],t:0,eg:false,mi:true,slp:0,efr:0,prl:0,elc:0,strength:0,shield:0}}]);
				await update([{name:"nextturn",playing:0,start:0,end:0},{name:"setmana",playerId:1,mana:5},{name:"draw",playerId:1}]);
				await displayTuto("Hahaha ! Tu ne peux pas vraincre ma carte version électromagnétisme !", {button:"Ok Boumiz !",left:true,speaker:"boumiz"});
				await displayTuto("Aïe ! Boumiz électro nous empêche d'attaquer l'adversaire directement, il faut l'éliminer !", {button:"Go"});
				await displayTuto("Endormez-le avec un fauteil confortable de l'amphi Condorcet", {show:[playerHandDivs[1]]});
				//document.getElementById("opponent").classList.remove("show");
				await displayTuto("", {show:[opponentFightersDivs[0]]});
				await update([{name:"sleep",target:{teamId:1,index:0},agent:{teamId:0,index:"unknow"},slp:1}]);
				await displayTuto("Maintenant vous pouvez utiliser l'attaque d'Abo \"Monsieur, vous dormez ?\" !", {show:[playerFightersDivs[1]]});
				let donotuseAction = document.querySelector("#select-attack .attacks > div");
				donotuseAction.classList.remove("selectable");
				donotuseAction.style.pointerEvents = "none";
				await displayTuto("", {show:[document.querySelector("#select-attack .attacks > div + div")]})
				await displayTuto("Et visez Boumiz électro", {show:[opponentFightersDivs[0]]});
				await update([{name:"attack",damage:60,target:{teamId:1,index:0},agent:{teamId:0,index:1}},{name:"eliminate",teamId:1,index:0}]);
				await displayTuto("Nooon ! Quel combo audacieux ! Condorcet a permis de triplé les dégâts d'Abo", {button:"Et ouais !",left:true,speaker:"boumiz"});
				await displayTuto("Il est maintenant temps d'en finir, attaquez-le avec Alcool et Élève random !", {show:[playerHandDivs[0]]});
				//playerFightersDivs[1].classList.remove("show");
				await displayTuto("", {show:[playerFightersDivs[0]]});
				await update([{name:"addstrength",strength:40,target:{teamId:0,index:0},agent:{teamId:0,index:"unknow"}},{name:"electrify",elc:3,target:{teamId:0,index:0},agent:{teamId:0,index:"unknow"}}]);
				await displayTuto("L'élève a gagné en force !!! (Même s'il sera mal plus tard)", {button:"Ah ouais comme moi"});
				await displayTuto("", {show:[playerFightersDivs[0]]});
				await displayTuto("", {show:[document.querySelector("#select-attack .attacks > div")]});
				await displayTuto("Finissez-en !", {show:[document.getElementById("opponent")]});
				await update([{name:"attack",damage:50,target:{teamId:1,index:"player"},agent:{teamId:0,index:0}},{name:"endgame",winner:0,gain:0,lost:0,winnerReward:6,loserReward:1}]);
				await displayTuto("Ok tu as bien joué je dois l'admettre, mais tes adversaires seront plus malins que mon robot, ils viennent de l'EISTI tout de même. Je veillerai sur toi 🌸🙏", {button:"Merci seigneur Boumiz",left:true,speaker:"boumiz"});
				displayTuto("", {show:[document.getElementById("end-screen")],speaker:"boumiz",left:true});
			}
			
			// Actualisation du match
			async function update(newActions) {
				if (!match) return;
				console.info("[Poképrof] Actualisation de l'historique");
				let player = match.opponents[match.playerId];
				if (newActions.find(a=>a.name=="endgame"))
					stop = true;
				match.history.push(...newActions);
				if (historyIndex < match.history.length-newActions.length)
					return;
				let startHistoryIndex = historyIndex;
				while (historyIndex < match.history.length) {
					let action = match.history[historyIndex];
					console.info("[Poképrof] Action '"+action.name+"'");
					let agent;
					switch (action.name) {
						case "nextturn":
							for (let fighter of match.opponents[match.playing].profs) { // à la fin du tour
								if (fighter.mi) fighter.mi = false;
								for (let effect of ["slp","prl","efr","elc","pvq"])
									if (fighter[effect] > 0) {
										fighter[effect]--;
									}
								/*if (fighter.elc > 0) {
									fighter.hp -= Math.max(0, 10-fighter.shield)
									fighter.shield = Math.max(0, fighter.shield-10);
								}*/
							}
							match.playing = action.playing;
							match.start = action.start;
							match.end = action.end;
							if (match.place.length>0) match.place[match.place.length-1].t++;
							for (let fighter of match.opponents[match.playing].profs) { // au debut du tour
								fighter.eg = false;
								fighter.t++;
							}
							break;
						case "setmana":
							playSound("nextturn", "wav");
							match.opponents[action.playerId].mana = action.mana;
							break;
						case "playfightercard":
							playSound("cardPlace", "ogg", 4);
							if (action.playerId == match.playerId) {
								let card = player.hand.splice(action.index, 1)[0];
								player.mana -= card.cost;
								player.profs.push(card);
								card.mi = true;
								let cardDiv = playerHandDivs.splice(action.index, 1)[0];
								playerFightersDivs.push(cardDiv);
							} else {
								let opponent = match.opponents[action.playerId];
								opponent.hand--;
								opponent.mana -= action.card.cost;
								opponent.profs.push(action.card);
								let cardDiv = opponentHandDivs.splice(action.index, 1)[0];
								opponentFightersDivs.push(cardDiv);
							}
							break;
						case "summon": // et invoc
							playSound("summon", "mp3", 3);
							let opponent = match.opponents[action.teamId];
							opponent.profs.push(action.card);
							let cardDiv = createGamePlacedCard(action.card.id, {x:50, y:50, edit:action.card});
							(action.teamId==match.playerId?playerFightersDivs:opponentFightersDivs).push(cardDiv);
							break;
						case "playplacecard":
							playSound("cardPlace", "ogg", 4);
							if (action.playerId == match.playerId) {
								let card = player.hand.splice(action.index, 1)[0];
								player.mana -= action.card.cost;
								match.place.push(card);
								if (placeDiv) placeDiv.parentElement.removeChild(placeDiv);
								placeDiv = playerHandDivs.splice(action.index, 1)[0];
							} else {
								let opponent = match.opponents[action.playerId];
								opponent.hand--;
								opponent.mana -= action.card.cost;
								match.place.push(action.card);
								let cardDiv = opponentHandDivs.splice(action.index, 1)[0];
								if (placeDiv) placeDiv.parentElement.removeChild(placeDiv);
								placeDiv = cardDiv;
							}
							break;
						case "eliminate":
							playSound("cardSlide", "ogg", 8);
							if (action.teamId == match.playerId) {
								player.discard.push(player.profs.splice(action.index, 1)[0]);
								let oldDiscard = playerDiscardDiv;
								playerDiscardDiv = playerFightersDivs.splice(action.index, 1)[0];
								if (oldDiscard) setTimeout(()=>oldDiscard.parentElement.removeChild(oldDiscard), 500);
							} else {
								let opponent = match.opponents[action.teamId];
								opponent.discard.push(opponent.profs.splice(action.index, 1)[0]);
								let oldDiscard = opponentDiscardDiv;
								opponentDiscardDiv = opponentFightersDivs.splice(action.index, 1)[0];
								if (oldDiscard) setTimeout(()=>oldDiscard.parentElement.removeChild(oldDiscard), 500);
							}
							break;
						case "draw":
							playSound("cardTakeOutPackage", "ogg", 2);
							if (action.playerId == match.playerId) {
								match.opponents[action.playerId].deck--;
								match.opponents[action.playerId].hand.push(action.card);
								playerHandDivs.push(createGamePlacedCard("back", {targetPos:playerDrawDiv, dataset:{zone:"my-draw"}}));
							} else {
								match.opponents[action.playerId].deck--;
								match.opponents[action.playerId].hand++;
								opponentHandDivs.push(createGamePlacedCard("back", {targetPos:opponentDrawDiv, dataset:{zone:"his-draw"}}));
							}
							break;
						case "playeffectcard":
							playSound("cardPlace", "ogg", 4);
							if (action.playerId == match.playerId) {
								let card = player.hand.splice(action.index, 1)[0];
								player.mana -= action.card.cost;
								player.discard.push(card);
								if (playerDiscardDiv) playerDiscardDiv.parentElement.removeChild(playerDiscardDiv);
								playerDiscardDiv = playerHandDivs.splice(action.index, 1)[0];
							} else {
								let opponent = match.opponents[action.playerId];
								opponent.hand--;
								opponent.mana -= action.card.cost;
								opponent.discard.push(action.card);
								let cardDiv = opponentHandDivs.splice(action.index, 1)[0];
								if (opponentDiscardDiv) opponentDiscardDiv.parentElement.removeChild(opponentDiscardDiv);
								opponentDiscardDiv = cardDiv;
							}
							break;
						case "attack": {
							playSound("fightImpact", "mp3", 20);
							let target = getCard(action.target);
							let damage = action.damage;
							if (target.shield) {
								if (target.shield > damage) {
									target.shield -= damage;
									damage = 0;
								} else {
									damage -= target.shield;
									target.shield = 0;
								}
							}
							target.hp -= damage;
							let victimDiv = getCardDiv(action.target), agentDiv = getCardDiv(action.agent);
							displayParticle("damage", {targetPos:victimDiv,text:action.damage});
							if (agentDiv) await bump(agentDiv, {targetPos:victimDiv});
							break;
						}
						case "engage":  
							getCard(action.target).eg = true;
							break;
						case "disengage":
							getCard(action.target).eg = false;
							getCard(action.target).mi = false;
							break;
						case "heal":
							playSound("heal", "wav");
							let target = getCard(action.target);
							target.hp = Math.min(target.hp+action.heal, target.hpmax||160);
							agent = getCard(action.agent);
							displayParticle("heal", {targetPos:getCardDiv(action.target),text:action.heal});
							break;
						case "sleep":
							getCard(action.target).slp = action.slp;
							break;
						case "wakeup":
							getCard(action.target).slp = 0;
							break;
						case "seedraw":
							await seeDraw(Number.isInteger(action.cards)?new Array(action.cards).fill({id:"back"}):action.cards, action.playerId!=match.playerId/*, action.playerId!=0?.5:1*/);
							break;
						case "seedrawhim":
							await seeDraw(Number.isInteger(action.cards)?new Array(action.cards).fill({id:"back"}):action.cards, action.playerId==match.playerId/*, action.playerId!=0?.5:1*/);
							break;
						case "paralyse":
							getCard(action.target).prl = action.prl;
							displayParticle("paralyse", {targetPos:getCardDiv(action.target)});
							break;
						case "affraid":
							getCard(action.target).efr = action.efr;
							break;
						case "courage":
							getCard(action.target).efr = 0;
							break;
						case "electrify":
							getCard(action.target).elc = action.elc;
							displayParticle("bolt", {targetPos:getCardDiv(action.target)});
							break;
						case "diselectrify":
							getCard(action.target).elc = 0;
							break;
						case "setvar":
						    answr=getCard(action.card);
						    //console.log(action.card);
						    if (answr!=null) { //Modif Léo
							    getCard(action.card).vars[action.varname] = action.value;
						    }
							break;
						case "leaveplace":
							playSound("cardSlide", "ogg", 8);
							let placeCard = match.place.pop();
							if (placeDiv) {
							    let oldPlaceDiv = placeDiv;
							    placeDiv = undefined;
							    updateGamePlacedCard(oldPlaceDiv, {x:110,y:50});
							    setTimeout(()=>oldPlaceDiv.parentElement.removeChild(oldPlaceDiv), 500);
							}
							// TODO : où mettre la carte ?
							break;
						case "delmana":
							match.opponents[action.playerId].mana = Math.max(0, match.opponents[action.playerId].mana-action.mana);
							break;
						case "givemana":
							playSound("nextturn", "wav");
							match.opponents[action.playerId].mana += action.mana;
							break;
						case "drop":
							playSound("cardSlide", "ogg", 8);
							if (action.playerId==match.playerId) {
								player.discard.push(player.hand.splice(action.index,1)[0]);
								if (playerDiscardDiv) playerDiscardDiv.parentElement.removeChild(playerDiscardDiv);
								playerDiscardDiv = playerHandDivs.splice(action.index,1)[0];
							} else {
								let opponent = match.opponents[match.playerId==0?1:0];
								opponent.hand--;
								opponent.discard.push(action.card);
								if (opponentDiscardDiv) opponentDiscardDiv.parentElement.removeChild(opponentDiscardDiv);
								opponentDiscardDiv = opponentHandDivs.splice(action.index,1)[0];
							}
							break;
						case "addshield":
							getCard(action.target).shield += action.shield;
							displayParticle("shield", {targetPos:getCardDiv(action.target),text:action.shield,textColor:"black"});
							break;
						case "removeshield":
							getCard(action.target).shield -= action.shield;
							displayParticle("shield", {targetPos:getCardDiv(action.target),text:-action.shield,textColor:"black"});
							break;
						case "addstrength":
							getCard(action.target).strength += action.strength;
							displayParticle("fist", {targetPos:getCardDiv(action.target),text:action.strength,textColor:"black"});
							break;
						case "convert": {
							playSound("cardSlide", "ogg", 8);
							let opponent = match.opponents[match.playerId==0?1:0];
							if (action.playerId == match.playerId) {
								player.profs.push(opponent.profs.splice(action.index,1)[0]);
								playerFightersDivs.push(opponentFightersDivs.splice(action.index,1)[0]);
							} else {
								opponent.profs.push(player.profs.splice(action.index,1)[0]);
								opponentFightersDivs.push(playerFightersDivs.splice(action.index,1)[0]);
							}
							break;
						}
						case "endgame":
							match.ended = true;
							break;
						case "rescue":
							playSound("cardTakeOutPackage", "ogg", 2);
							let card = match.opponents[action.playerId].discard.splice(action.index, 1)[0];
							card.t = 0;
							card.eg = card.mi = false;
							card.slp = card.prl = card.efr = card.pvq = card.elc = card.shield = card.strength = 0;
							card.hp = card.hpmax;
							if (action.playerId==match.playerId) player.hand.push(card);
							else match.opponents[action.playerId].hand++;
							(action.playerId==match.playing?playerFightersDivs:opponentFightersDivs).push(createGamePlacedCard(card.id, {x:50, y:50, edit:card}));
							break;
						case "givecard": {
							playSound("cardSlide", "ogg", 8);
							if (action.playerId==match.playerId) player.hand.push(action.card);
							else match.opponents[action.playerId].hand++;
							let cardDiv = createGamePlacedCard(action.card.id, {x:50, y:50, edit:action.card});
							(action.playerId==match.playerId?playerHandDivs:opponentHandDivs).push(cardDiv);
							await sleep(500);
							break; }
						case "retreat":
							playSound("cardSlide", "ogg", 8);
							if (action.playerId == match.playerId) {
							    var fighter = player.profs.splice(action.index,1)[0];
								fighter.t = 0;
								fighter.eg = fighter.mi = false;
								fighter.slp = fighter.prl = fighter.efr = fighter.pvq = fighter.elc = fighter.shield = fighter.strength = 0;
								fighter.hp = fighter.hpmax;
								player.hand.push(fighter);
								playerHandDivs.push(playerFightersDivs.splice(action.index,1)[0]);
							} else {
								let opponent = match.opponents[match.playerId==0?1:0];
								opponent.hand++;
								opponent.profs.splice(action.index,1)[0]
								opponentHandDivs.push(opponentFightersDivs.splice(action.index,1)[0]);
							}
							break;
						case "makeprovoking":
						    getCard(action.target).pvq = action.pvq;
							//displayParticle("provoke", {targetPos:getCardDiv(action.target)});
							break;
						default:
							console.error("[Poképrof] Unknow action name : "+action.name);
					}
					refresh();
					await sleep(Math.min(500, 5000/(match.history.length-startHistoryIndex)));
					historyIndex++;
				}
				/*if (newActions.length!=0)
					refresh();*/
			}
			
			// Recuperation d'une carte avec un index-id
			function getCard(cardIndex) {
				if (cardIndex.teamId=="place")
					return match.place[cardIndex.index];
				if (cardIndex.teamId=="unknow")
				    return null;   
				if (cardIndex.discard)
					return match.opponents[cardIndex.teamId].discard[cardIndex.index];
				if (cardIndex.index == "player")
					return match.opponents[cardIndex.teamId];
				return match.opponents[cardIndex.teamId].profs[cardIndex.index];
			}
			// Récupération d'une div de carte avec un index-id
			function getCardDiv(cardIndex) {
				if (cardIndex.teamId=="place")
					return placeDiv;
				if (cardIndex.teamId=="unknow")
					return null;
				if (cardIndex.discard)
					return (cardIndex.teamId==match.playerId?playerDiscardDiv:opponentDiscardDiv);
				if (cardIndex.index == "player")
					return cardIndex.teamId==match.playerId?document.getElementById("player-hp-container"):document.getElementById("opponent-hp-container");
				return (cardIndex.teamId==match.playerId?playerFightersDivs:opponentFightersDivs)[cardIndex.index];
			}
			
			// Rafraîchir graphiquement à partir de la globale match
			function refresh() {
				var portrait = matchMedia("(orientation:portrait)").matches;
				console.info("[Poképrof] Rafraîchissement graphique"+(portrait?" (version portrait)":""));
				if (match.ended) {
					let endGameAction = match.history.find(a=>a.name=="endgame");
					let win = endGameAction.winner==match.playerId;
					game.classList.add("ended");
					var div = createElement("div", {id:"end-screen"});
					div.appendChild(createElement("span", {className: "title"}, win?"Victoire":"Défaite"));
					div.appendChild(createElement("span", {className: "text"}, "Face à "+match.names[match.playerId==0?1:0]+" ("+match.trophies[match.playerId==0?1:0]+"🏆)"));
					if (endGameAction.gain || endGameAction.lost)
						div.appendChild(createElement("span", {className:"text"}, (win?("+"+endGameAction.gain):("-"+endGameAction.lost)+" 🏆")));
					if ((win && endGameAction.winnerReward) || (!win && endGameAction.loserReward))
						div.appendChild(createElement("span", {className:"text"}, [
							createElement("span", {}, (win?endGameAction.winnerReward:endGameAction.loserReward)+" "),
							createElement("img", {src:"assets/mana.svg", className:"emote"})
						]));
					div.appendChild(createElement("span", {className: "button"}, "OK", {click:function(){
						window.location.href = ".";
					}}));
					document.body.appendChild(div);
				}
				let playerId = match.playerId;
				let opponentId = playerId==0?1:0;
				endTurnButton.innerText = match.playing==playerId ? "Finir le tour" : "En attente de l'adversaire";
				//setTimeout(get, 2000);
				// nom des joueurs
				document.getElementById('player-name').textContent = match.names[playerId];
				document.getElementById('opponent-name').textContent = match.names[opponentId];
				// pioche du joueur
				if (match.opponents[playerId].deck !== 0) {
					if (!playerDrawDiv)
						playerDrawDiv = createGamePlacedCard("back", {x:10, y:portrait?80:85, dataset:{zone:"my-draw"}});
					else updateGamePlacedCard(playerDrawDiv, {x:10, y:portrait?80:85, id:"back", dataset:{zone:"my-draw"}});
				} else if(playerDrawDiv) {
					playerDrawDiv.parentElement.removeChild(playerDrawDiv);
					playerDrawDiv = undefined;
				}
				// pioche de l'adversaire
				if (match.opponents[opponentId].deck !== 0) {
					if (!opponentDrawDiv)
						opponentDrawDiv = createGamePlacedCard("back", {x:90, y:portrait?10:7, dataset:{zone:"his-draw"}});
					else updateGamePlacedCard(opponentDrawDiv, {x:90, y:portrait?10:7, id:"back", dataset:{zone:"his-draw"}});
				} else if (opponentDrawDiv) {
					opponentDrawDiv.parentElement.removeChild(opponentDrawDiv);
					opponentDrawDiv = undefined;
				}
				// combattants du joueur
				let n = match.opponents[playerId].profs.length-1;
				for (let i = 0; match.opponents[playerId].profs[i] || playerFightersDivs[i]; i++) {
					let card = match.opponents[playerId].profs[i];
					if (!card) {
						for(let ex of playerFightersDivs.splice(i))
							ex.parentElement.removeChild(ex);
					} else if (!playerFightersDivs[i]) {
						let cardDiv = createGamePlacedCard(card.id, {x:portrait?(i<5?i*20+50-20*Math.min(n,4)/2:(i-5)*20+100-20*(n/2)):i*10+50-10*n/2, y:portrait?(i<5?57:65):60, edit:card, dataset:{zone:"my-fighters"}});
						playerFightersDivs.push(cardDiv);
					} else {
						updateGamePlacedCard(playerFightersDivs[i], {x:portrait?(i<5?i*20+50-20*Math.min(n,4)/2:(i-5)*20+100-20*(n/2)):i*10+50-10*n/2, y:portrait?(i<5?57:65):60, id:card.id, edit:card, dataset:{zone:"my-fighters"}});
					}
				}
				// combattants de l'adversaire
				n = match.opponents[opponentId].profs.length-1;
				for (let i = 0; match.opponents[opponentId].profs[i] || opponentFightersDivs[i]; i++) {
					let card = match.opponents[opponentId].profs[i];
					if (!card) {
						for (let ex of opponentFightersDivs.splice(i))
							ex.parentElement.removeChild(ex);
					} else if (!opponentFightersDivs[i]) {
						let cardDiv = createGamePlacedCard(card.id, {x:portrait?(i<5?i*20+50-20*Math.min(n,4)/2:(i-5)*20+100-20*(n/2)):i*10+50-10*n/2, y:portrait?(i<5?22:30):32, edit:card, dataset:{zone:"his-fighters"}});
						opponentFightersDivs.push(cardDiv);
					} else {
						updateGamePlacedCard(opponentFightersDivs[i], {x:portrait?(i<5?i*20+50-20*Math.min(n,4)/2:(i-5)*20+100-20*(n/2)):i*10+50-10*n/2, y:portrait?(i<5?22:30):32, id:card.id, edit:card, dataset:{zone:"his-fighters"}});
					}
				}
				// main du joueur
				n = match.opponents[playerId].hand.length-1;
				for (let i = 0; match.opponents[playerId].hand[i] || playerHandDivs[i]; i++) {
					let card = match.opponents[playerId].hand[i];
					if (!card) {
						for (let ex of playerHandDivs.splice(i))
							ex.parentElement.removeChild(ex);
					} else if (!playerHandDivs[i]) {
						let cardDiv = createGamePlacedCard(card.id, {x:portrait?(i<5?i*20+50-20*Math.min(n,4)/2:(i-5)*20+100-20*(n/2)):(i*10+50-10*(n/2)), y:portrait?(i<5?95:105):100, edit:card, dataset:{zone:"my-hand"}});
						playerHandDivs.push(cardDiv);
					} else {
						updateGamePlacedCard(playerHandDivs[i], {x:portrait?(i<5?i*20+50-20*Math.min(n,4)/2:(i-5)*20+100-20*(n/2)):(i*10+50-10*(n/2)), y:portrait?(i<5?95:105):100, id:card.id, edit:card, dataset:{zone:"my-hand"}});
					}
				}
				// main de l'adversaire
				n = match.opponents[opponentId].hand;
				for (let i = 0; i<n || opponentHandDivs[i]; i++) {
					if (i>=n) {
						for (let ex of opponentHandDivs.splice(i))
							ex.parentElement.removeChild(ex);
					} else if (!opponentHandDivs[i]) {
						opponentHandDivs.push(createGamePlacedCard("back", {x:i*10+50-10*((n-1)/2), y:0, dataset:{zone:"his-hand"}}));
					} else {
						updateGamePlacedCard(opponentHandDivs[i], {x:i*10+50-10*((n-1)/2), y:0/*, id:"back"*/, dataset:{zone:"his-hand"}});
					}
				}
				// pile de défausse du joueur
				if (match.opponents[playerId].discard && match.opponents[playerId].discard.length !== 0) {
					let card = match.opponents[playerId].discard[match.opponents[playerId].discard.length-1];
					if (!playerDiscardDiv)
						playerDiscardDiv = createGamePlacedCard(card.id, {x:90, y:portrait?80:85, edit:card, dataset:{zone:"my-discard"}});
					else
						updateGamePlacedCard(playerDiscardDiv, {x:90, y:portrait?80:85, id:card.id, edit:card, dataset:{zone:"my-discard"}});
				} else if (playerDiscardDiv) {
					playerDiscardDiv.parentElement.removeChild(playerDiscardDiv);
					playerDiscardDiv = undefined;
				}
				// pile de défausse de l'adversaire
				if (match.opponents[opponentId].discard && match.opponents[opponentId].discard.length !== 0) {
					let card = match.opponents[opponentId].discard[match.opponents[opponentId].discard.length-1];
					if (!opponentDiscardDiv)
						opponentDiscardDiv = createGamePlacedCard(card.id, {x:10, y:portrait?10:7, edit:card, dataset:{zone:"his-discard"}});
					else
						updateGamePlacedCard(opponentDiscardDiv, {x:10, y:portrait?10:7, id:card.id, edit:card, dataset:{zone:"his-discard"}});
				} else if (opponentDiscardDiv) {
					opponentDiscardDiv.parentElement.removeChild(opponentDiscardDiv);
					opponentDiscardDiv = undefined;
				}
				// terrain
				if (match.place && match.place.length !== 0) {
					if (!placeDiv)
						placeDiv = createGamePlacedCard(match.place[match.place.length-1].id, {x:10, y:portrait?44:46, dataset:{zone:"place"}});
					else
						updateGamePlacedCard(placeDiv, {x:10, y:portrait?44:46, id:match.place[match.place.length-1].id, dataset:{zone:"place"}});
					gameDiv.style.backgroundImage = "url('assets/cards/"+parseInt(match.place[match.place.length-1].id)+".png')";
				} else {
					gameDiv.style.backgroundImage = "";
				    if (placeDiv) {
					    game.removeChild(placeDiv);
					    placeDiv = undefined;
				    }
				}
				// mana
				playerManaAff.innerText = match.opponents[playerId].mana;
				opponentManaAff.innerText = match.opponents[opponentId].mana;
				// pv
				playerHpAff.innerText = match.opponents[playerId].hp;
				opponentHpAff.innerText = match.opponents[opponentId].hp;
			}
			
			// Quand on clique sur une carte
			async function onClickCard(cardDiv) {
				if (resolveTarget)
					return resolveTarget(cardDiv);
				if (playerHandDivs.includes(cardDiv)) {
					let index = playerHandDivs.indexOf(cardDiv);
					showPlayCard(match.opponents[match.playerId].hand[index], index);
				} else if (playerFightersDivs.includes(cardDiv)) {
					let index = playerFightersDivs.indexOf(cardDiv);
					showSelectAttack(match.opponents[match.playerId].profs[index], index);
				} else if (opponentFightersDivs.includes(cardDiv)) {
					showCard(match.opponents[match.playerId==0?1:0].profs[opponentFightersDivs.indexOf(cardDiv)]);
				} else if (placeDiv==cardDiv)
					showCard(match.place[match.place.length-1]);
				else if (opponentDiscardDiv==cardDiv)
					showCard(match.opponents[match.playerId==0?1:0].discard.slice(-1)[0]);
				else if (playerDiscardDiv==cardDiv)
					showCard(match.opponents[match.playerId].discard.slice(-1)[0]);
			}
			
			// Jouer une carte
			async function playCard(index) {
				if (match.playing != match.playerId) return;
				var card = match.opponents[match.playerId].hand[index];
				if (card.cost > match.opponents[match.playerId].mana)
					return shake(document.getElementById("player-mana-container"));
				if (tuto) resolveTuto();
				var context = {targetsofyou:[],targetsofhim:[]};
				for (let script of card.scripts.map(s=>decompileScript(s))) {
					if (script.trigger == "onplaycard") { // TODO : choisir un seul script ?
						context = await queryScriptTargets(script, context);
						if (!testScriptCondition(script.condition, card, match.playerId, context))
							return alert("Toutes les conditions ne sont pas réunis pour jouer cette carte.");
					}
				}
				console.info("[Poképrof] Utilisation de la carte "+index);
				let targets = context.targetsofyou.map((t,i)=>"&target"+i+"ofyou="+t).join("")+context.targetsofhim.map((t,i)=>"&target"+i+"ofhim="+t).join("");
				if (!tuto) sendRequest("POST", "api/match/play.php", "<?=isset($_REQUEST['test'])?'test&':''?>action=playcard&index="+index+targets).then(onServerResponse);
			}
			
			// Faire attaquer un combattant
			async function attack(cardIndex, scriptIndex, card) {
				if (match.playing != match.playerId) return;
				let context = await queryScriptTargets(decompileScript(card.scripts[scriptIndex]), {});
				console.info("[Poképrof] Attaque du combattant "+cardIndex+" avec le script "+scriptIndex);
				let targets = context.targetsofyou.map((t,i)=>"&target"+i+"ofyou="+t).join("")+context.targetsofhim.map((t,i)=>"&target"+i+"ofhim="+t).join("");
				if (!tuto) sendRequest("POST", "api/match/play.php", "<?=isset($_REQUEST['test'])?'test&':''?>action=attack&cardindex="+cardIndex+"&scriptindex="+scriptIndex+targets).then(onServerResponse);
			}
			
			// Finir le tour
			function endTurn() {
				if (match.playing != match.playerId) return;
				console.info("[Poképrof] Fin du tour");
				if (tuto) resolveTuto();
				else sendRequest("POST", "api/match/play.php", "<?=isset($_REQUEST['test'])?'test&':''?>action=endturn").then(onServerResponse);
			}
			
			// Abandonner le match
			function giveUp() {
				console.info("[Poképrof] Abandon");
				sendRequest("POST", "api/match/play.php", "<?=isset($_REQUEST['test'])?'test&':''?>action=giveup").then(onServerResponse);
			}
			
			// Actualiser le timer
			var notUpdateButTimerIsEnd = true;
			var stop = false;
			function refreshTimer() {
				if (!match || match.ended || stop) return;
				if (Date.now()/1000 > match.end) {
					timerBar.style.width = "100%";
					if (notUpdateButTimerIsEnd) {
						notUpdateButTimerIsEnd = false;
						setTimeout(function(){
							getUpdate();
							notUpdateButTimerIsEnd = true;
						}, 1500);
					}
				} else
					timerBar.style.width = (Date.now()/1000-match.start)/(match.end-match.start)*100+"%";
			}
			
			// Afficher le menu de sélection d'attaque
			async function showSelectAttack(card, cardIndex) {
				var selectAttackDiv = document.getElementById("select-attack");
				await showCard(card);
				if (match.playerId==match.playing && !card.mi && !card.eg && card.slp<=0 && card.prl<=0 && card.efr<=0) {
					if (card.scripts[0]) {
						let script = decompileScript(card.scripts[0]);
						if (script.trigger=="onaction" && (!script.condition || testScriptCondition(script.condition, card, match.playerId, {}))) {
							let attackDiv = selectAttackDiv.querySelector(".attacks > :nth-child(1)");
							attackDiv.classList.add("selectable");
							attackDiv.addEventListener("click", function() {
								selectAttackDiv.style.display = "none";
								attack(cardIndex, 0, card);
								if (tuto) resolveTuto();
							});
						}
					}
					if (card.scripts[1]) {
						let script = decompileScript(card.scripts[1]);
						if (script.trigger=="onaction" && (!script.condition || testScriptCondition(script.condition, card, match.playerId, {}))) {
							let attackDiv = selectAttackDiv.querySelector(".attacks > :nth-child(2)");
							attackDiv.classList.add("selectable");
							attackDiv.addEventListener("click", function() {
								attack(cardIndex, 1, card);
								selectAttackDiv.style.display = "none";
								if (tuto) resolveTuto();
							});
						}
					}
				}
				if (tuto) resolveTuto();
			}
			// Afficher le menu pour jouer une carte
			async function showPlayCard(card, index) {
				var selectAttackDiv = document.getElementById("select-attack");
				await showCard(card);
				document.querySelector("#select-attack-card .inner").appendChild(createElement("span", {className:"button"}, "Jouer cette carte", {click:function(){
					playCard(index);
					selectAttackDiv.style.display = "none";
				}}));
			}
			// Afficher une carte
			async function showCard(card) {
				var selectAttackDiv = document.getElementById("select-attack");
				await setCardElementById(document.getElementById("select-attack-card"), card.id, card);
				var effectsDiv = document.getElementById("select-attack-effects");
				effectsDiv.innerHTML = "";
				for (let id in EFFECTS)
					if (card[id]>0)
						effectsDiv.appendChild(createElement("div", {}, [
							createElement("span", {className:"time"}, card[id]+" tour"+(card[id]>1?"s":"")),
							createElement("span", {className:"name"}, EFFECTS[id].emote+" "+EFFECTS[id].name),
							createElement("span", {}, EFFECTS[id].description)
						]));
				selectAttackDiv.style.display = "";
			}
			
			// 
			function createGamePlacedCard(cardId, data={}) {
				data.parent = data.parent||gameDiv;
				data.onclick = function() {onClickCard(this);};
				let div = createPlacedCard(cardId, data);
				div.offsetWidth; // reflow
				if (data.edit)
					div.classList[data.edit.eg||data.edit.mi?"add":"remove"]("engaged");
				return div;
			}
			//
			function updateGamePlacedCard(div, data={}) {
				updatePlacedCard(div, data);
				if (data.edit)
					div.classList[data.edit.eg||data.edit.mi?"add":"remove"]("engaged");
			}
			
			function updateCardEffects(cardDiv, card) {
				let div = cardDiv.getElementsByClassName("particles")[0];
				if (!div) cardDiv.appendChild(div = createElement("div", {className:"particles"}));
				let n = Object.keys(EFFECTS).filter(effect=>card[effect]>0).length;
				let i = 0;
				for (let effectId in EFFECTS) {
					if (card[effectId]>0) {
						setTimeout(function() {
							for (let j = 0; j < 3; j++) {
								let particle = div.getElementsByClassName(effectId+j)[0];
								if (!particle) {
									particle = document.createElement("span");
									particle.className = effectId+j;
									particle.innerText = EFFECTS[effectId].emote;
									div.appendChild(particle);
								}
							}
						}, 2000*i/n);
					} else {
						let particle;
						for (let j = 0; (particle = div.getElementsByClassName(effectId+j)[0])!=null; j++)
							div.removeChild(particle);
					}
					if (card[effectId]) i++;
				}
			}
			const EFFECTS = {
				slp: {name:"Endormie", emote:"💤", description:"La carte ne peut pas attaquer"},
				elc: {name:"Électrifiée", emote:"⚡", description:"La carte prend 10 dégâts à chaque fin de tour"},
				efr: {name:"Appeurée", emote:"😱", description: "La carte ne peut pas attaquer"},
				prl: {name:"Paralysée", emote:"🚫", description: "La carte ne peut pas attaquer"},
				pvq: {name:"Provoquante", emote:"🤬", description: "L'ennemi doit cibler cette carte en priorité"}
			};
			
			// Animations de particule
			function displayParticle(name, data={}) { // name, {x, y, text="", parent=gameDiv, targetPos, textColor}
				let particle = createElement("div", {className:"particle", style:{backgroundImage:"url('assets/"+name+".png')"}}, [
					createElement("div"),
					createElement("span", {}, data.text===undefined?"":data.text)
				]);
				particle.style.left = data.x+"%";
				particle.style.top = data.y+"%";
				if (data.targetPos) {
					let rect = data.targetPos.getBoundingClientRect();
					particle.style.left = rect.left + rect.width/2 + "px";
					particle.style.top = rect.top + rect.height/2 + "px";
				}
				if (data.textColor)
					particle.style.color = data.textColor;
				(data.parent||gameDiv).appendChild(particle);
				setTimeout(()=>(data.parent||gameDiv).removeChild(particle), 2000);
			}
			
			// Animation pour regarder des cartes
			async function seeDraw(cards, isOpponent=false, sp=1) { // Durée = (nombre de cartes * 2 + 1) * 1400 ms * sp
				let promise = new Promise((resolve, reject) => {
					let drawDiv = document.querySelector(isOpponent?"[data-zone='his-draw']":"[data-zone='my-draw']");
					let cardsDivs = [];
	   				for (let i in cards) {
			  			setTimeout(() => cardsDivs[i]=createGamePlacedCard("back", {targetPos:drawDiv}), (1400*i)*sp);
						setTimeout(() => updateGamePlacedCard(cardsDivs[i], {id:cards[i].id,edit:cards[i]}), (200+1400*i)*sp);
						setTimeout(() => {updateGamePlacedCard(cardsDivs[i], {x:50+i*20-(cards.length-1)*10, y:50}); cardsDivs[i].classList.add('zoom');}, (1200+1400*i)*sp);
					}
					for (let j = cards.length-1; j >= 0; j--) {
				   		setTimeout(() => {updateGamePlacedCard(cardsDivs[j], {targetPos:drawDiv, id:cards[j].id, edit:cards[j]}); cardsDivs[j].classList.remove('zoom');}, (1400*(-j+2*cards.length))*sp);
						setTimeout(() => updateGamePlacedCard(cardsDivs[j], {id:"back"}), (200+1400*(-j+2*cards.length))*sp);
						setTimeout(() => cardsDivs[j].parentElement.removeChild(cardsDivs[j]), (1200+1400*(-j+2*cards.length))*sp);
					}
					setTimeout(resolve, (1400*2*cards.length+1400)*sp);
				});
				return promise;
			}
			
			// Secouer un élément HTML
			function shake(el) {
				el.classList.add('shake');
				setTimeout(function() {
					el.classList.remove('shake');
				}, 1000);
			}
			// Met au premier plan une liste d'éléments HTML
			function show(elements) {
				var onShowEnd;
				var promise = new Promise(function(resolve,reject){onShowEnd=resolve;});
				var veil = createElement("div", {className:"veil"}, [], {click:function(e){
					this.parentElement.removeChild(this);
					for (let el of elements)
						el.classList.remove('show');
					onShowEnd();
				}});
				gameDiv.appendChild(veil);
				for (let el of elements)
					el.classList.add('show');
				promise.end = function() {
					veil.click();
				}
				return promise;
			}
			
			// Faire une animation d'attaque
			async function bump(cardDiv, data) { // cardDiv, {x, y, targetPos}
				cardDiv.style.transitionDuration = ".2s"
				cardDiv.style.transitionTimingFunction = "ease-in";
				let prevTop = cardDiv.style.top;
				let prevLeft = cardDiv.style.left;
				if (data.targetPos) {
					let rect = data.targetPos.getBoundingClientRect();
					cardDiv.style.left = rect.left+rect.width/2+"px";
					cardDiv.style.top = rect.top+rect.height/2+"px";
				} else {
					cardDiv.style.left = data.x+"%";
					cardDiv.style.top = data.y+"%";
				}
				await sleep(200);
				cardDiv.style.transitionDuration = ""
				cardDiv.style.transitionTimingFunction = "";
				cardDiv.style.top = prevTop;
				cardDiv.style.left = prevLeft;
				await sleep(200);
			}

			// Jouer un son
			function playSound(name, ext="mp3", count=0) {
				let audio = new Audio("assets/sounds/" + name + (count != 0 ? parseInt(Math.random() * count) + 1 : "") + "." + ext);
				audio.play();
			}
			
			// Décompiler un script de compétence de carte
			function decompileScript(script) {
				if (!script) return {};
				let matches = script.matchAll(/([a-z]+)({([^}]*)}|)(\[([^\]]*)\]|)/g).next().value;
				return {
					trigger: matches[1],
					functions: matches[3]?matches[3].split(" ").filter(f=>f!="").map(f=>decompileScriptFunction(f)):[],
					condition: matches[5]
				};
			}
			
			// Décompiler une fonction de script de compétence de carte
			function decompileScriptFunction(scriptFunction) {
				let matches = scriptFunction.matchAll(/([a-z]+)(\(([^\)]*)\)|)(\[([^\]]*)\]|)/g).next().value;
				return {
					name: matches[1],
					args: matches[3]?matches[3].split(","):[],
					condition: matches[5]
				};
			}
			
			// Quelles sont les cibles requises pour un script ?
			function queryScriptRequieredTargets(script, required={targetsofhim:{n:0,conditions:[]},targetsofyou:{n:0,conditions:[]}}) {
				required = queryScriptShardRequieredTargets(script.condition, required);
				for (let f of script.functions) {
					required = queryScriptShardRequieredTargets(f.condition, required);
					//if (testScriptCondition(f.condition, card, playerId, context))
						for (let arg of f.args)
							required = queryScriptShardRequieredTargets(arg, required);
				}
				return required;
			}
			// Quelles sont les cibles requises pour un bout de script ?
			function queryScriptShardRequieredTargets(shard, required) { // return : {targetofhim:{n:2,conditions:[c1,c2]},targetofyou:{n:1,conditions:[c1,c2]}}
				if (!shard) return required;
				if (shard.includes("targetofyou")) {
					required.targetsofyou.n = Math.max(required.targetsofyou.n, 1);
					let matches = shard.match("targetofyou\\[([^\\[\\]]+)\\]");
					required.targetsofyou.conditions.push(matches?matches[1]:"");
				} else if (shard.includes("target2ofyou")) {
					required.targetsofyou.n = Math.max(required.targetsofyou.n, 2);
					let matches = shard.match("target2ofyou\\[([^\\[\\]]+)\\]");
					required.targetsofyou.conditions.push(matches?matches[1]:"");
				} else if ((shard.includes("target2ofhim") || shard.includes("target2"))) {
					required.targetsofhim.n = Math.max(required.targetsofhim.n, 2);
					let matches = shard.match("target2(ofhim|)\\[([^\\[\\]]+)\\]");
					required.targetsofhim.conditions.push(matches?matches[2]:"");
				} else if ((shard.includes("targetofhim") || shard.includes("target"))) {
					required.targetsofhim.n = Math.max(required.targetsofhim.n, 1);
					let matches = shard.match("target(ofhim|)\\[([^\\[\\]]+)\\]");
					required.targetsofhim.conditions.push(matches?matches[2]:"");
				}
				return required;
			}
			
			// Demander les cibles éventuelles d'un script
			async function queryScriptTargets(script, context) {
				var required = queryScriptRequieredTargets(script);
				context.targetsofhim = await queryTarget(true, required.targetsofhim.n, required.targetsofhim.conditions.includes("")?undefined:required.targetsofhim.conditions.join("|"), context);
				context.targetsofyou = await queryTarget(false, required.targetsofyou.n, required.targetsofyou.conditions.includes("")?undefined:required.targetsofyou.conditions.join("|"), context);
				return context;
			}
			
			// Demander des cibles
			var resolveTarget = undefined;
			async function queryTarget(opponentTeam, amount, condition=undefined, context={}) {
				var promise = new Promise(async function(resolve, reject) {
					var elects = [];
					if (opponentTeam) {
						for (let i in match.opponents[match.playerId==0?1:0].profs) {
							if (!condition || testScriptCondition(condition, match.opponents[match.playerId==0?1:0].profs[i], match.playerId, context))
								elects.push(opponentFightersDivs[i]);
						}
						if (!condition || testScriptCondition(condition, match.opponents[match.playerId==0?1:0], match.playerId, context))
							elects.push(document.getElementById("opponent"));
					} else {
						for (let i in match.opponents[match.playerId].profs) {
							if (!condition || testScriptCondition(condition, match.opponents[match.playerId].profs[i], match.playerId, context))
								elects.push(playerFightersDivs[i]);
						}
					}
					let s = show(elects);
					s.then(function(){
						resolveTarget = undefined;
					});
					var targets = [];
					for (let i = 0; i < amount; i++) {
						let target = await getTarget();
						if (tuto) resolveTuto();
						console.info("[Poképrof] Cible selectionnée");
						targets.push(target.id=="opponent" ? "him" : opponentTeam ? opponentFightersDivs.indexOf(target) : playerFightersDivs.indexOf(target))
					}
					resolve(targets);
					s.end();
				});
				return promise;
			}
			async function getTarget() {
				return new Promise(function(resolve, reject) {
					resolveTarget = resolve;
				});
			}
			
			// Tester une condition de script
			function testScriptCondition(expr, card, playerId, context) {
				if (expr===""||expr===undefined) return true;
				if (!expr) return true;
				var values;
				if (expr.includes("|")) {
					return expr.split("|").reduce(function(acc,expr){
						return acc | testScriptCondition(expr, card, playerId, context);
					}, false);
				} else if (expr.includes("&")) {
					return expr.split("&").reduce(function(acc,expr){
						return acc & testScriptCondition(expr, card, playerId, context);
					}, true);
				} else if (expr.includes("=")) {
					if (expr.includes("!=")) {
						values = expr.split("!=");
						return getScriptValue(values[0], card, playerId, context) != getScriptValue(values[1], card, playerId, context) ? 1 : 0;
					} else {	  
						values = expr.split("=");
						return getScriptValue(values[0], card, playerId, context) == getScriptValue(values[1], card, playerId, context) ? 1 : 0;
					}
				} else if (expr.includes("!")) {
					values = expr.split("!")
					return getScriptValue(values[0], card, playerId, context) != getScriptValue(values[1], card, playerId, context) ? 1 : 0;
				} else if (expr.includes(">")) {
					values = expr.split(">");
					return getScriptValue(values[0], card, playerId, context) > getScriptValue(values[1], card, playerId, context);
				} else if (expr.includes("<")) {
					values = expr.split("<");
					return getScriptValue(values[0], card, playerId, context) < getScriptValue(values[1], card, playerId, context);
				} else if (expr == "targetsleep") {
					target = getScriptProfs("targetofhim", null, playerId, context)[0];
					return target.slp>0;
				}
				let words = expr.split("_");
				switch (words[0]) {
					case 'isplace': // cardId
						return getScriptValue(words[1], card, playerId, context) == match.place[place.length-1].id;
					case 'in': // profs, cardId
						profs = getScriptProfs(words[1], card, playerId, context);
						for (let prof of profs)
							if (parseInt(prof.id) == getScriptValue(words[2], card, playerId, context))
								return true;
						return false;
					case "hastype":
					    if (card.type=="prof")
					        return card.types.includes(words[1]);
					    else return words[1]=="player";
					case "hasnottype":
					    if (card.type=="prof")
					        return !card.types.includes(words[1]);
					    else return words[1]!="player";
				}
			}
			
			function getScriptProfs(expr, card, playerId, context) {
				let otherPlayerId = playerId==0 ? 1 : 0; // en supposant qu'il n'y a que 2 joueurs
				let matches = expr.matchAll(/([^\[]+)(\[([^\]]*)\]|)/g).next().value;
				let profSelector = matches[0];
				var condition = matches[3];
				var profs = [];
				switch(profSelector) {
					case 'all':
						profs = [];
						for (let player of match.opponents)
							for (let prof of player.profs)
								profs.push(prof);
						break;
					case 'allofhim':
						profs = match.opponents[otherPlayerId].profs.slice(0);
						break;
					case 'allofyou':
						profs = match.opponents[playerId].profs.slice(0);
						break;
					case 'target': // deprecated ???
					case 'targetofhim':
						if (context.targetsofhim.length>0)
							profs = context.targetsofhim.slice(0,1);
						else throw new Exception('need targetofhim');
						break;
					case 'target2': // deprecated
					case 'target2ofhim':
						if (context.targetsofhim.length>1)
							profs = context.targets2ofhim.slice(0,2);
						else throw new Exception('need target2ofhim');
						break;
					case 'targetofyou':
						if (context.targetsofyou.length>0)
							profs = context.targetsofyou.slice(0,1);
						else throw new Exception('need targetofyou');
						break;
					case 'target2ofyou':
						if (context.targetsofyou.length>1)
							profs = context.targetsofyou.slice(0,2);
						else throw new Exception('need target2ofyou');
						break;
					case 'it':
						profs = [card];
						break;
					case 'you':
						profs = [match.opponents[playerId]];
						break;
					case 'him':
						profs = [match.opponents[otherPlayerId]];
						break;
					case 'summoned':
						profs = [context.summoned];
						break;
					case "randomofyou":
						profs = match.opponents[playerId].profs[0]?[match.opponents[playerId].profs[0]]:[]; // pas besoin d'être vraiment aléatoire
						break;
					case "randomofhim":
						profs = match.opponents[otherPlayerId].profs[0]?[match.opponents[otherPlayerId].profs[0]]:[]; // pas besoin d'être vraiment aléatoire
						break;
					default:
						throw new Exception("malformed prof selector : "+expr);
				}
				for (let i in profs)
					if (!testScriptCondition(condition, profs[i], playerId, context)) {
						profs.splice(i, 1);
					}
				return profs;
			}
			
			function getScriptValue(expr, card, playerId, context) {
				if (!isNaN(parseInt(expr))) return parseInt(expr);
				var values;
				if (expr.includes("+")) {
					return expr.split("+").reduce(function(acc, expr){
						return acc + getScriptValue(expr, card, playerId, context);
					}, 0);
				} else if (expr.includes("-")) {
					return expr.split("-").slice(1).reduce(function(acc, expr) {
						return acc - getScriptValue(expr, card, playerId, context);
					}, expr.split("-")[0]==""?0:getScriptValue(expr.split("-")[0], card, playerId, context));
				} else if (expr.includes("*")) {
					return expr.split("*").reduce(function(acc, expr){
						return acc * getScriptValue(expr, card, playerId, context);
					}, 1);
				} else if (expr.includes("%")) {
					return expr.split("%").slice(1).reduce(function(acc, expr) {
						return acc % getScriptValue(expr, card, playerId, context);
					}, getScriptValue(expr.split("%")[0], card, playerId, context));
				}
				switch (expr) {
					case "type":
						if (card.type == "prof")
							return card.types[0];
						else
							return "player";
					case "place":
						if (match.place.length == 0)
							return -1;
						return parseInt(match.place[match.place.length-1].id);
				}
				let words = expr.split("_");
				switch (words[0]) {
					case "getvar":
						let varname = words[1];
						if (!(card.vars[varname])) card.vars[varname] = 0;
						return card.vars[varname];
					case "random":
						return getScriptValue(words[1], card, playerId, context); // pas utile de le mettre vraiment aléatoire
					case "count":
						return getScriptProfs(words[1], card, playerId, context).length;
				}
				if (expr=="id") return parseInt(card.id);
				if (card[expr]!==undefined) return card[expr];
				return expr;
			}
		</script>
	</body>
</html>
