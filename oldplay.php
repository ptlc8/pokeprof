<?php session_start() ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>Jouer | PokéProf</title>
		<link rel="stylesheet" href="oldstyle.css" />
		<script src="cards.js"></script>
		<link rel="icon" type="image/png" href="assets/icon.png" />
	</head>
	<body>
		<?php
		include('init.php');
		// connexion à un compte
		if (!isset($_SESSION['username'], $_SESSION['password'])
			|| ($userRequest = sendRequest("SELECT id, name FROM USERS WHERE `name` = '", $_SESSION['username'], "' and `password` = '", $_SESSION['password'], "'"))->num_rows === 0) {
			echo('<script>window.location.replace(\'/connect.php?go=\'+encodeURIComponent(window.location.pathname))</script>');
		} else {
			$user = $userRequest->fetch_assoc();
			echo('<span id="logged">Vous êtes connecté en tant que '.$user['name'].'</span>');
		}
		
		?>
		<div id="game">
			<div id="veil" style="display: none;"></div>
			<div id="his-hand"></div>
			<div id="my-hand"></div>
			<!--<span>e</span> why ?-->
			<span id="end-turn" class="button">Finir le tour</span>
			<span id="give-up" class="button" onclick="giveup()">Abandonner</span>
			<div id="hp-container" title="Vos points de vie">
				<span id="hp"></span>
				<img id="hp-image" src="assets/hp.png" alt="vos points de vie" />
			</div>
			<div id="mana-container" title="Votre mana">
				<span id="mana"></span>
				<img id="mana-image" src="assets/mana.png" alt="votre mana" />
			</div>
			<div id="his-hp-container" title="Ses points de vie">
				<span id="his-hp"></span>
				<img id="his-hp-image" src="assets/hp.png" alt="ses points de vie" />
			</div>
			<div id="his-mana-container" title="Sa mana">
				<span id="his-mana"></span>
				<img id="his-mana-image" src="assets/mana.png" alt="sa mana" />
			</div>
			<div id="him"><span>Votre adversaire</span></div>
			<div id="card-more" style="display: none;">
				<canvas id="card-more-image" onclick="event.stopPropagation()"></canvas>
				<div id="card-more-attack1"></div>
				<div id="card-more-attack2"></div>
			</div>
			<div id="timer"><div></div></div>
			<span id="his-name"></span>
		</div>
		<script>
			var game = document.getElementById('game');
			var veil = document.getElementById('veil');
			var myTurn = true;
			var hisHand = document.getElementById('his-hand');
			var myHand = document.getElementById('my-hand');
			var myHandCards = [];
			var myProfCards = [];
			var manaAff = document.getElementById('mana');
			var hpAff = document.getElementById('hp');
			var mana = 0;
			var hp = 200;
			var hisProfCards = [];
			var hisManaAff = document.getElementById('his-mana');
			var hisHpAff = document.getElementById('his-hp');
			var hisMana = 0;
			var hisHp = 200;
			var cardMore = document.getElementById('card-more');
			var cardMoreCanvas = document.getElementById('card-more-image');
			var cardMoreAttack1 = document.getElementById('card-more-attack1');
			var cardMoreAttack2 = document.getElementById('card-more-attack2');
			var endTurnButton = document.getElementById('end-turn');
			var timerBar = document.querySelector('#timer > div');
			var timer = {};
			var timerId = undefined;
			window.onload = () => {
				request();
				cardMore.style.display = 'none';
				//setInterval(request, 2000);
				timerId = setInterval(refreshTimer, 100);
			}
			function request() {
				post('oldyoplay.php', 'action=get', (response) => {
					let cmd = response.match(/\w*/i)[0];
					if (cmd == 'infos') {
						refresh(JSON.parse(response.replace(cmd, '').trim()));
					} else if (cmd == 'not') {
						window.location.replace('index.php');
					} else {
						alert(response);
					}
				});
			}
			function refresh(infos) {
				console.log(infos);
				// actualisation statut de tour
				//playSound('turn');
				if (infos.myturn) {
					endTurnButton.innerText = 'Finir le tour';
					endTurnButton.onclick = endTurn;
				} else {
					endTurnButton.innerText = 'En attente de l\'adversaire';
					endTurnButton.onclick = () => {};
					setTimeout(request, 2000);
				}
				myTurn = infos.myturn;
				//actualisation du nom de l'adversaire
				document.getElementById('his-name').textContent = infos.him.name;
				// actualisation de la pioche de moi
				if (infos.me.deck !== 0) placeImg('my-draw', 10, 85, 'back');
				else removeImg('my-draw');
				// actualisation de la pioche de lui
				if (infos.him.deck !== 0) placeImg('his-draw', 90, 7, 'back');
				else removeImg('his-draw');
				// actulisation des profs de moi
				let n = infos.me.profs.length-1;
				for (let i = 0; infos.me.profs[i] || document.getElementById('my-prof-card'+i); i++) {
					let card = infos.me.profs[i];
					let cardImg;
					if (!card) removeImg('my-prof-card'+i);
					else {
						cardImg = placeImg('my-prof-card'+i, i*10+50-10*n/2, 60, card.id, {hp: card.hp, shield: card.shield, strength: card.strength, cost: card.cost});
						cardImg.onclick = () => showCardMore(card);
						cardImg.classList[card.eg||card.mi?'add':'remove']('engaged');
					}
					if (!card) removeImg('my-prof-card'+i+'-effects');
					else placeEffects('my-prof-card'+i+'-effects', i*10+50-10*(n/2), 60, card);
				}
				myProfCards = infos.me.profs;
				// actulisation des profs de lui
				n = infos.him.profs.length-1;
				for (let i = 0; infos.him.profs[i] || document.getElementById('his-prof-card'+i); i++) {
					let card = infos.him.profs[i];
					let cardImg;
					if (!card) removeImg('his-prof-card'+i);
					else {
						cardImg = placeImg('his-prof-card'+i, i*10+50-10*(n/2), 32, card.id, {hp: card.hp, shield: card.shield, strength: card.strength});
						cardImg.classList[card.eg||card.mi?'add':'remove']('engaged');
					}
					if (!card) removeImg('his-prof-card'+i+'-effects');
					else placeEffects('his-prof-card'+i+'-effects', i*10+50-10*(n/2), 32, card);
				}
				hisProfCards = infos.him.profs;
				// actualisation de la main de moi
				for (let i = 0; infos.me.hand[i] || document.getElementById('my-hand-card'+i); i++) {
					let card = infos.me.hand[i];
					if (!card) removeImg('my-hand-card'+i);
					else {
						placeImg('my-hand-card'+i, 0, 0, card.id, {hp: card.hp, shield: card.shield, strength: card.strength}, 'relative-card', myHand)
							.onclick = () => playCard(card);
					}
				}
				myHandCards = infos.me.hand;
				// actualisation de la main de lui
				for (let i = 0; i<infos.him.hand || document.getElementById('his-hand-card'+i); i++) {
					if (i>=infos.him.hand) removeImg('his-hand-card'+i);
					else placeImg('his-hand-card'+i, 0, 0, 'back', {}, 'relative-card', hisHand);
				}
				// actualisation de ma pile de défausse
				if (infos.me.discard && infos.me.discard.length !== 0) {
					let card = infos.me.discard[infos.me.discard.length-1];
					placeImg('my-discard', 90, 85, card.id, {hp: card.hp, shield: card.shield, strength: card.strength});
				}
				// actualisation de la pile de défausse de lui
				if (infos.him.discard && infos.him.discard.length !== 0) {
					let card = infos.him.discard[infos.him.discard.length-1];
					placeImg('his-discard', 10, 7, card.id, {hp: card.hp, shield: card.shield, strength: card.strength});
				}
				// actualisation du terrain
				if (infos.place && infos.place.length !== 0) {
					placeImg('place-card', 10, 46, infos.place[infos.place.length-1].id);
				}
				// actualisation de la mana
				manaAff.innerText = mana = infos.me.mana;
				// actualisation de la mana de lui
				hisManaAff.innerText = hisMana = infos.him.mana;
				// actualisation de la vie
				hpAff.innerText = hp = infos.me.hp;
				// actualisation de la vie de lui
				hisHpAff.innerText = hisHp = infos.him.hp;
				timer = {start: infos.start, end: infos.end, now: infos.now};
			}
			async function playCard(card) {
				if (!isMyTurn()) return;
				if (card.cost > mana) {
					shake('mana-image');
					return;
				}
				let targetsId = [];
				for (let script of card.scripts) {
					if (script.match(/onplaycard{/)) {
						if (script.match(/\Wtargetofhim\W/) || script.match(/\Wtarget\W/))
							targetsId = await queryTargetId();
						if (script.match(/\Wtarget2ofhim\W/) || script.match(/\Wtarget2\W/))
							targetsId = await queryTargetId(2);
						if (script.match(/\Wtargetofyou\W/))
							targetsId = await queryTargetId(1, true);
						if (script.match(/\Wtarget2ofyou\W/))
							targetsId = await queryTargetId(2, true);
					}
				}
				post('oldyoplay.php', 'action=playcard&card='+myHandCards.indexOf(card)+(targetsId[0]?'&target='+targetsId[0]:'')+(targetsId[1]?'&target2='+targetsId[1]:''), onResponse);
				playSound('playcard'); // TODO : tmp
			}
			async function attack(n, card) {
				if (!isMyTurn()) return;
				let cardIndex = myProfCards.indexOf(card);
				let targetsId = [];
				if (card.scripts[n].match(/\Wtargetofhim\W/) || card.scripts[n].match(/\Wtarget\W/)) targetsId = await queryTargetId();
				if (card.scripts[n].match(/\Wtarget2ofhim\W/) || card.scripts[n].match(/\Wtarget2\W/)) targetsId = await queryTargetId(2);
				if (card.scripts[n].match(/\Wtargetofyou\W/)) targetsId = await queryTargetId(1, true);
				if (card.scripts[n].match(/\Wtarget2ofyou\W/)) targetsId = await queryTargetId(2, true);
				post('oldyoplay.php', 'action=attack&card='+cardIndex+'&n='+n+(targetsId[0]?'&target='+targetsId[0]:'')+(targetsId[1]?'&target2='+targetsId[1]:''), (response) => {
					if (response.startsWith('success')) {		//  5 lin. : à virer plus tard
						let el = document.getElementById('my-prof-card'+cardIndex);
						el.classList.add('attacking');
						setTimeout(() => el.classList.remove('attacking'), 1000);
					}
					onResponse(response);
				});
			}
			function onResponse(response) {
				let cmd = response.match(/[^ ]+/)[0];
				let arg = response.replace(cmd, '').trim();
				switch (cmd) {
				    case 'success':
    					if (arg != '') for (let action of JSON.parse(arg)) {
    					    var pos = {x:50, y:50};
    					    if (action.target) {
    					        if (action.target == 'him') pos = {x:50, y:10};
    					        else if (action.target == 'you') pos = {x:50, y:80};
    					        else if (action.target.startsWith('h')) pos = {x:action.target.substring(1)*10+50-(hisProfCards.length*10/2)+5, y:32};
    					        else if (action.target.startsWith('y')) pos = {x:action.target.substring(1)*10+50-(myProfCards.length*10/2)+5, y:60};
    					    }
    						switch(action.name) {
    							case 'seedraw':
    								seeDraw(action.cards);
    								break;
    							case 'seedrawhim':
    							    seeDrawHim(action.cards);
    							    break; //Modif de Léo
    							case 'attack':
    							    playSound('attack');
    							    placeParticle('damage', pos.x, pos.y, action.damage);
    							    break;
    							case 'electrify':
    							    playSound('electrify');
    							    placeParticle('bolt', pos.x, pos.y);
    							    break;
    							case 'heal':
    							    playSound('heal');
    							    placeParticle('heal', pos.x, pos.y); // TODO
    							    break;
    							default:
    								alert('undefined action.name, \'cause new ?');
    						}
    					}
    					request();
    					break;
    				case 'engaged':
    				    aff("Ce combattant a déjà effectué une action");
    				    break;
    				case 'mi':
    				    aff("Ce combattant vient d'être invoqué, il ne peut attaquer");
    				    break;
    				case 'sleeping':
    				case 'paralysed':
    				case 'affraid':
    				    aff("Ce combattant est "+(cmd=='sleeping'?"endormi, il ne peut ni attaquer ni défendre":cmd=='paralysed'?"paralysé, il ne peut ni attaquer ni défendre":cmd=='affraid'?"effrayé, il ne peut pas attaquer":cmd));
    				    break;
    				case 'defensors':
    				    aff("Un combattant adversaire est capable de défendre");
    				    break;
    				case "unfilledplaycondition":
    				    aff("La condtion d'invocation n'est pas complétée");
    				    break;
    				case 'endgame':
    				    game.parentElement.removeChild(game);
    				    clearInterval(timerId);
    				    arg = JSON.parse(arg);
    				    printEndScreen(arg.result, arg.opponent, arg.rewards);
    				    break;
    				default:
				        alert(response);
				}
			}
			function refreshTimer() {
			    if (!timer) return;
			    timer.now += 0.1;
			    if (timer.now > timer.end) {
			        request();
			        return;
			    } else {
			        timerBar.style.width = (timer.now-timer.start)/(timer.end-timer.start)*100+'%';
			    }
			}
			async function seeDraw(cards, sp=1) {
				let promise = new Promise((resolve, reject) => {
	   				for (let i = 0; i < cards.length; i++) {
			  			let card;
			  			setTimeout(() => card = placeImg('seedraw-'+i, 10, 85, 'back'), (2000*i)*sp);
						setTimeout(() => card.classList.add('flip'), (500+2000*i)*sp);
					  	setTimeout(() => {
							placeImg('seedraw-'+i, 10, 85, cards[i].id, {hp: cards[i].hp, shield: cards[i].shield, strength: cards[i].strength});
							card.classList.remove('flip');
						}, (1000+2000*i)*sp);
						setTimeout(() => {placeImg('seedraw-'+i, 50, 50, cards[i].id, {hp: cards[i].hp, shield: cards[i].shield, strength: cards[i].strength}); card.classList.add('zoom');}, (1500+2000*i)*sp);
					}
					for (let i = cards.length-1; i >= 0; i--) {
				  		let card;
				   		setTimeout(() => {card = placeImg('seedraw-'+i, 10, 85, cards[i].id, {hp: cards[i].hp, shield: cards[i].shield, strength: cards[i].strength}); card.classList.remove('zoom');}, (2000*(-i+2*cards.length))*sp);
						setTimeout(() => card.classList.add('flip'), (500+2000*(-i+2*cards.length))*sp);
						setTimeout(() => {
							placeImg('seedraw-'+i, 10, 85, 'back');
							card.classList.remove('flip');
						}, (1000+2000*(-i+2*cards.length))*sp);
						setTimeout(() => removeImg('seedraw-'+i), (1500+2000*(-i+2*cards.length))*sp);
					}
					setTimeout(resolve, (2000*2*cards.length+2000)*sp);
				});
				return promise;
			}
			async function seeDrawHim(cards, sp=1) {
				let promise = new Promise((resolve, reject) => {
	   				for (let i = 0; i < cards.length; i++) {
			  			let card;
			  			setTimeout(() => card = placeImg('seedraw-'+i, 90, 7, 'back'), (2000*i)*sp);
						setTimeout(() => card.classList.add('flip'), (500+2000*i)*sp);
					  	setTimeout(() => {
							placeImg('seedraw-'+i, 90, 7, cards[i].id, {hp: cards[i].hp, shield: cards[i].shield, strength: cards[i].strength});
							card.classList.remove('flip');
						}, (1000+2000*i)*sp);
						setTimeout(() => {placeImg('seedraw-'+i, 50, 50, cards[i].id, {hp: cards[i].hp, shield: cards[i].shield, strength: cards[i].strength}); card.classList.add('zoom');}, (1500+2000*i)*sp);
					}
					for (let i = cards.length-1; i >= 0; i--) {
				  		let card;
				   		setTimeout(() => {card = placeImg('seedraw-'+i, 90, 7, cards[i].id, {hp: cards[i].hp, shield: cards[i].shield, strength: cards[i].strength}); card.classList.remove('zoom');}, (2000*(-i+2*cards.length))*sp);
						setTimeout(() => card.classList.add('flip'), (500+2000*(-i+2*cards.length))*sp);
						setTimeout(() => {
							placeImg('seedraw-'+i, 90, 7, 'back');
							card.classList.remove('flip');
						}, (1000+2000*(-i+2*cards.length))*sp);
						setTimeout(() => removeImg('seedraw-'+i), (1500+2000*(-i+2*cards.length))*sp);
					}
					setTimeout(resolve, (2000*2*cards.length+2000)*sp);
				});
				return promise;
			} //Modif de Léo
			async function queryTargetId(n=1, us=false) {
				var p = new Promise((resolve, reject) => {
					let elects = document.querySelectorAll(us ? '[id^=my-prof-card]' : '[id^=his-prof-card], #him');
					show(elects);
					var targets = [];
					for (let elect of elects) {
						let f = () => {
							n--;
							targets.push(elect.id=='him'?'him':elect.id.match(/[0-9]+/)[0]);
							elect.onclick = () => {};
							elect.classList.remove('show');
							if (n > 0) return;
							resolve(targets);
							veil.click();
						}
						elect.onclick = f;
					}
				});
				return p;
			}
			function isMyTurn() {
				if (!myTurn) shake('end-turn');
				return myTurn;
			}
			function endTurn() {
				post('oldyoplay.php', 'action=endturn', onResponse);
			}
			function giveup() {
			    post('oldyoplay.php', 'action=giveup', onResponse);
			}
			function show(els) {
				veil.style.display = '';
				for (let el of els)
					el.classList.add('show');
				veil.onclick = () => {
					veil.style.display = 'none';
					for (let el of els)
						el.classList.remove('show');
				}
			}
			function shake(id) {
				let el = document.getElementById(id);
				if (!el) return;
				el.classList.add('shake');
				setTimeout(() => {
					el.classList.remove('shake');
				}, 1000);
			}
			function placeImg(id, x, y, cardId, edit={}, className='placed-card', where=game) {
				let cvs = document.getElementById(id);
				if (!cvs) {
					cvs = document.createElement('canvas');
					cvs.id = id;
					cvs.className = className;
					where.appendChild(cvs);
				}
				if (cardId == 'back') drawCardBack(cvs);
				else drawCardById(cvs, cardId, edit);
				cvs.style.top = y + '%';
				cvs.style.left = x + '%';
				return cvs;
			}
			function removeImg(id) {
				let img = document.getElementById(id);
				if (img) img.parentElement.removeChild(img);
			}
			function placeEffects(id, x, y, card, where=game) {
				let efcDiv = document.getElementById(id);
				if (!efcDiv) {
					efcDiv = document.createElement('div');
					efcDiv.id = id;
					efcDiv.className = 'particles flying';
					where.appendChild(efcDiv);
				}
				let efcs = {slp:{text:'💤'}, elc:{text:'⚡'}, efr:{text:'😱'}, prl:{text:'🚫'}/*, eg:{text:'⏳'}*/};
				let n = Object.keys(efcs).filter(eft => card[eft]).length;
				let j = 0;
				for (let efc of Object.keys(efcs)) {
					setTimeout(()=>{
						for (let i = 0; i < 3; i++) {
							let el = document.getElementById(id+'-'+efc+i);
							if (card[efc]) {
								if (!el) {
									el = document.createElement('span');
									el.id = id+'-'+efc+i;
									el.innerText = efcs[efc].text;
									efcDiv.appendChild(el);
								}
							} else {
								if (el) efcDiv.removeChild(el);
							}
						}
					}, 2000*j/n);
					if (card[efc]) j++;
				}
				efcDiv.style.top = y + '%';
				efcDiv.style.left = x + '%';
				return efcDiv;
			}
			function showCardMore(card) {
				drawCardById(cardMoreCanvas, card.id, {hp: card.hp, shield: card.shield, strength: card.strength});
				if (card.scripts[0] && card.scripts[0].match(/\w*(?={)/) && card.scripts[0].match(/\w*(?={)/)[0] == 'onaction' && !(card.scripts[0].startsWith('onlyfirst') || card.mi || card.eg) && (!card.scripts[0].match(/(}\[)[^\[\]]+(?=\])/) || testScriptCondition(undefined, card, card.scripts[0].match(/(}\[)[^\[\]]+(?=\])/)[0].substring(2)))) {
					cardMoreAttack1.style.display = '';
					cardMoreAttack1.onclick = () => {veil.click();attack(0, card);};
				} else {
					cardMoreAttack1.style.display = 'none';
				}
				if (card.scripts[1] && card.scripts[1].match(/\w*(?={)/) && card.scripts[1].match(/\w*(?={)/)[0] == 'onaction' && !(card.scripts[1].startsWith('onlyfirst') || card.mi || card.eg) && (!card.scripts[1].match(/(}\[)[^\[\]]+(?=\])/) || testScriptCondition(undefined, card, card.scripts[1].match(/(}\[)[^\[\]]+(?=\])/)[0].substring(2)))) {
					cardMoreAttack2.style.display = '';
					cardMoreAttack2.onclick = () => {veil.click();attack(1, card);};
				} else {
					cardMoreAttack2.style.display = 'none';
				}
				show([cardMore]);
			}
			function testScriptCondition(infos, card, expr) {
				if (expr.includes('=')) {
					let values = expr.split('=');
					return getScriptValue(infos, card, values[0]) == getScriptValue(infos, card, values[1]);
				} else if (expr.includes('>')) {
					let values = expr.split('>');
					return getScriptValue(infos, card, values[0]) > getScriptValue(infos, card, values[1]);
				} else if (expr.includes('<')) {
					let values = expr.split('<');
					return getScriptValue(infos, card, values[0]) < getScriptValue(infos, card, values[1]);
				}
			}
			function getScriptValue(infos, card, expr) {
				if (typeof expr == 'number') return parseInt(expr);
				if (expr.includes('+')) {
					let values = expr.split('+');
					return getScriptValue(infos, card, values[0]) + getScriptValue(infos, card, values[1]);
				} else if (expr.includes('-')) {
					let values = expr.split('-');
					return getScriptValue(infos, card, values[0]) - getScriptValue(infos, card, values[1]);
				} else if (expr.includes('%')) {
					let values = expr.split('%');
					return getScriptValue(infos, card, values[0]) % getScriptValue(infos, card, values[1]);
				}
				switch (expr) {
					case 'type':
						return card.proftype;
				}
				let words = expr.split('_');
				switch (words[0]) {
					case 'getvar':
						let varname = '_'+words[1];
						if (!card[varname]) card[varname] = 0;
						return card[varname];
				}
				if (card[expr]) return card[expr];
				return expr;
			}
			function placeParticle(name, x, y, text='', where=game) {
                let particle = document.createElement('div');
            	particle.className = 'particle';
            	particle.style.backgroundImage = "url('assets/"+name+".png')";
            	particle.appendChild(document.createElement('div'));
            	let span = document.createElement('span');
            	span.innerText = text;
            	particle.appendChild(span);
            	particle.style.left = x+'%';
            	particle.style.top = y+'%';
            	where.appendChild(particle);
            	setTimeout(()=>where.removeChild(particle), 2000);
            }
			function aff(text, t=4000, where=game) {
				let el = document.createElement('span');
				el.innerText = text;
				el.style.position = 'absolute';
				el.style.left = '50%';
				el.style.top = '50%';
				el.style.transform = 'translate(-50%,-50%)';
				el.style.fontSize = '3em';
				el.style.opacity = '1';
				where.appendChild(el);
				el.style.transition = 'top '+t/1000+'s ease-out, opacity '+t/2000+'s ease';
				setTimeout(()=>el.style.top = '40%', 10);
				setTimeout(()=>el.style.opacity = '0', t/2);
				setTimeout(()=>where.removeChild(el), t);
				return el;
			}
			function printEndScreen(result, opponent, rewards=[]) {
                let ex = document.getElementById('end-screen');
                if (ex) {
                    ex.parentElement.removeChild(ex);
                }
                var div = document.createElement('div');
                div.id = 'end-screen';
                var title = document.createElement('span');
                title.className = 'title';
                title.innerText = result=='win'?'Victoire':result=='lose'?'Défaite':result=='draw'?'Égalité':result;
                div.appendChild(title);
                var o = document.createElement('span');
                o.className = 'text';
                o.innerText = 'Face à '+opponent.name+' ('+opponent.trophies+'🏆)';
                div.appendChild(o);
                for (let reward of rewards) {
                    if (reward.amount==0) continue;
                    let r = document.createElement('span');
                    r.className = 'text';
                    r.innerHTML = (reward.amount>0?'+':'-')+' '+Math.abs(reward.amount)+' '+(reward.type=='trophy'?'🏆':reward.type=='booster'?'<img src="assets/booster.webp" class="emote" />':reward.type=='rewardLevel'?'<img src="assets/mana.png" class="emote" />':reward.type);
                    div.appendChild(r);
                }
                var button = document.createElement('span');
                button.className = 'button';
                button.innerText = 'OK';
                button.onclick = () => window.location.href = window.location.pathname.replace(/[^\/]*$/, '');
                div.appendChild(button);
                document.body.appendChild(div);
            }
            function playSound(name) {
                var audio = new Audio('assets/sounds/'+name+'.mp3');
                audio.play();
            }
			function post(url, content='', onResponse=()=>{}) {
				var xhr = new XMLHttpRequest();
				xhr.open("POST", url);
				xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				xhr.onreadystatechange = function() {
					if (this.readyState === XMLHttpRequest.DONE && this.status === 200) onResponse(xhr.responseText);
				};
				xhr.send(content);
			}
			function equals(a, b) { // marche pas, à delete
			    if (Object.keys(a).length !== Object.keys(b).length) return false;
			    for (let k of Object.keys(a)) {
			        if (!equals(a[k],b[k])) return false;
			    }
			    return true
			}
		</script>
	</body>
</html>
