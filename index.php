<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>PokéProf !</title>
		<link rel="stylesheet" href="style.css?<?php echo time() ?>" />
		<link rel="stylesheet" href="index.css?<?php echo time() ?>" />
		<script src="cards.js?<?php echo time() ?>"></script>
		<link rel="icon" type="image/png" href="assets/icon.png" />
		<link rel="manifest" href="manifest.webmanifest" />
		<script src="include-service-worker.js"></script>
		<script>{// script pour la version mobile/portrait
		    var classes = ["left","middle","right"];
		    function onScroll(scroll) {
		        if (scroll.x>10) {
		            for (let i = 0; i < classes.length-1; i++)
		                if (document.body.classList.contains(classes[i])) {
		                    document.body.classList.remove(classes[i]);
		                    document.body.classList.add(classes[i+1]);
		                    break;
		                }
		        } else if (scroll.x<-10) {
		            for (let i = 1; i < classes.length; i++)
		                if (document.body.classList.contains(classes[i])) {
		                    document.body.classList.remove(classes[i]);
		                    document.body.classList.add(classes[i-1]);
		                    break;
		                }
		        }
		    }
		    window.addEventListener("mousewheel", (e)=>onScroll({x:e.wheelDeltaX,y:e.wheelDeltaY}));
		    window.addEventListener("wheel", (e)=>onScroll({x:e.deltaX,y:e.deltaY}));
		    let touchesPos=[];
		    window.addEventListener("touchstart", function(e){
		        for (let t of e.changedTouches)
		            touchesPos[t.identifier] = t;
		    });
		    window.addEventListener("touchend", function(e){
		        for(let t of e.changedTouches)
		            onScroll({x:touchesPos[t.identifier].clientX-t.clientX,
		                    y:touchesPos[t.identifier].clientY-t.clientY});
		    });
		}</script>
	</head>
	<body class="middle">
		<div id="background"></div>
		<?php
		include('init.php');
		$user = login(true, true);
		
		// récupération des cartes et du deck du joueur
		$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
		if ($result->num_rows === 0) {
			sendRequest("INSERT INTO `CARDSUSERS` (`id`, `rewardLevel`) VALUES ('", $user['id'], "', '45')");
			$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
			$_REQUEST['tuto'] = 'tuto';
		}
		$cardsUser = $result->fetch_assoc();
		      
		//Tournament
		$result = sendRequest("SELECT id FROM TOURNAMENT");
		if ($result->num_rows != 0) {
			$tournament=array();
			$lengthTournmnt=0;
			$tabTourn=$result->fetch_all()[0];
			for ($i=0; $i<$result->num_rows; $i++) {
				$result2=sendRequest("SELECT * FROM TOURNAMENT WHERE id='",$tabTourn[$i],"'");
				$tournament[$lengthTournmnt]=$result2->fetch_assoc();
				$lengthTournmnt++;
			}
		}
		//A finir, Léo
		
		?>
		<span id="title" class="title">PokéProf !</span>
		<a href="deck.php<?= isset($_REQUEST['tuto'])?'?tuto':'' ?>" id="deck">
			<div class="full-width"><span class="subtitle">Ton jeu de cartes</span></div>
			<?php
			// affichage du deck du joueur
			$deck = json_decode($cardsUser['deck'])[$cardsUser['choosenDeck']];
			for ($i = 0; $i < count($deck); $i++) {
				if ($deck[$i]!=null&&$deck!==0)
					echo('<div id="deck-'.$i.'" class="card"></div><script async>setCardElementById(document.getElementById("deck-'.$i.'"), "'.$deck[$i].'");</script>');
			}
			if (count($deck) !== 0 && $deck[0] !== 0)
				echo '<script>document.getElementById("background").style.backgroundImage = "url(\'assets/cards/'.intval($deck[0]).'.png\')";</script>';
			?>
		</a>
		<!--Modif de Edwin/Kévin -->
		<div class="alerts">
			 <!--
			 <div id="discord_alert" class="alert">
				<a href="https://discord.gg/QAcmFez" target="_blank">N'hésitez pas à rejoindre notre serveur discord</a>
				<div class="btns">
					<span class="closebtn" onclick="this.parentElement.parentElement.style.display='none';">&times;</span>
					<span class="closebtn" style="font-size: 24px" onclick="openclose('discord_but_plus','closebtn','flex')">&vellip;</span>
				</div>
				
				<div id="discord_but_plus" class="morebtns">
						 <a  onclick="sendinfo('discord=0')">Ne plus afficher</a>
				</div>
				 
			</div>-->
		</div>
		<a href="#history" id="history-button" class="button">Historique</a>
		<a href="#rules" id="rules-button" class="button">Les règles</a>
		<a href="create.php" id="create-card-button" class="button">Créer une carte</a>
		<a href="gallery.php" id="gallery-button" class="button">Explorer la galerie</a>
		<a href="#shop" id="shop-button" class="button">Boutique</a>
		<a href="#search" id="play-button">Jouer</a>
		<div id="free-card">
			<img src="assets/back.png" onclick="getFreeCard()" />
			<br />
			<span>Carte gratuite</span>
		</div>
		<table id="podium">
			<tr><td colspan="2">Top 10 🥇🥈🥉</td></tr>
			<?php
				$result = sendRequest("SELECT trophies, USERS.name, USERS.id FROM CARDSUSERS JOIN USERS ON USERS.id = CARDSUSERS.id ORDER BY trophies DESC, id DESC LIMIT 10");
				while ($player = $result->fetch_assoc())
					echo '<tr><td><a href="#user='.$player['id'].'">'.htmlspecialchars($player['name']).'</a></td><td>'.$player['trophies'].' 🏆</td></tr>';
			?>
			<tr><td><hr /></td><td><hr /></td></tr>
			<tr><td><a href="#user=<?php echo $user['id']; ?>"><?= htmlspecialchars($user['name']); ?></a></td><td><?php echo $cardsUser['trophies']; ?> 🏆</td></tr>
		</table>
		<div id="rewards">
			<div id="get-rewards">
				<div>
					<img class="reward" src="assets/booster.webp" onclick="openRewardBooster(1)" />
					<span>3 cartes !</span>
				</div>
				<div>
					<img class="reward" src="assets/booster.webp" onclick="openRewardBooster(2)"/>
					<span>6 cartes !<br />Au moins 1 rare</span>
				</div>
				<div>
					<img class="reward" src="assets/parcel.webp" onclick="openRewardBooster(3)"/>
					<span>10 cartes<br />Au moins 1 &eacute;pique</span> <!--Modif de Léo-->
				</div>
			</div>
			<div id="rewards-progress">
				<?php
				for ($i = 0; $i < 21; $i++) {
					echo '<div></div>';
				}
				?>
			</div>
		</div>
		<div id="veil" style="display: none;"></div>
		<div id="left-arrow" onclick="onScroll({x:-100,y:0})">◀</div>
		<div id="right-arrow" onclick="onScroll({x:100,y:0})">▶</div>
		<script>
<?php if (intval(strtotime($cardsUser['lastFreeCard'])) + (250*60*60) > time()) { ?>
			wait(<?php echo strtotime($cardsUser['lastFreeCard'])+(12*60*60)-time() ?>, document.getElementById('free-card'));
<?php } ?>
			var veil = document.getElementById('veil');
			var rewardLevel = <?php echo $cardsUser['rewardLevel']; ?>;
			setRewardLevel(rewardLevel);
			async function getFreeCard() {
				if (resolveTuto) resolveTuto();
				let cardDiv = createPlacedCard("back", {targetPos:document.querySelector("#free-card img"), classList:["free-card"]});
				let response = await sendRequest("POST", "loot.php", "what=freecard");
				if (response == "need to wait") {
				    await sleep(5000);
				    cardDiv.parentElement.removeChild(cardDiv);
				    return;
				}
				let loot = JSON.parse(response);
				updatePlacedCard(cardDiv, {id:loot[0].id});
				await sleep(2000);
				updatePlacedCard(cardDiv, {targetPos:document.getElementById("deck")});
				await sleep(500);
				cardDiv.style.width = "0";
				await sleep(500);
				cardDiv.parentElement.removeChild(cardDiv);
				wait(12*60*60, document.getElementById('free-card'));
			}
			function wait(t, el) {
				let waitEl = document.createElement('span');
				waitEl.className = 'needtowait';
				el.appendChild(waitEl);
				let e = ['🕐','🕑','🕒','🕓','🕔','🕕','🕖','🕗','🕘','🕙','🕚','🕛'];
				let f = () => {
					if (t > 0) {
						t--;
						waitEl.innerText = e[e.length-1-t%e.length]+' '+~~(t/60/60)+'h '+~~(t/60%60)+'m '+~~(t%60)+'s';
						setTimeout(f, 1000)
					} else waitEl.parentElement.removeChild(waitEl);
				};
				f();
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
			function setRewardLevel(l) {
				var cells = document.querySelectorAll('#rewards-progress > div');
				var rewards = document.querySelectorAll("#get-rewards > div");
				let i = 0;
				for (let cell of cells) {
					cell.classList[i<l?"add":"remove"]('claimed');
					i++;
				}
				i = 6;
				for (let reward of rewards) {
					reward.classList[i<l?"add":"remove"]("available");
					i+=7;
				}
			}
			function openRewardBooster(level) {
				let promise = new Promise((resolve, reject) =>{
					displayLoading(sendRequest("POST", "loot.php", "what=reward"+level)).then(async function(response) {
						if (response == "need more") {
							aff("Gagne des points de récompenses en combattant !");
							resolve();
						} else {
							setRewardLevel(rewardLevel-=7*level);
							openBooster(JSON.parse(response), level==3?"assets/parcel.webp":"assets/booster.webp").then(resolve);
						}
					});
				});
				return promise;
			}
			function openBooster(cards, boosterAssetUrl="assets/booster.webp") {
			    return new Promise(function(resolve, reject) {
    			    cards.sort((a,b) => b.rarity-a.rarity);
    				veil.style.display = '';
    				let cardsDivs = [];
    				let booster = createElement("img", {className: "placed-booster"}, [], {load:async function(){
    				    this.style.top = '150%';
    					document.body.appendChild(this);
    					setTimeout(()=>this.style.top='', 100);
    				    
    					for (let card of cards) {
    						let cardDiv = createElement("div");
    						if (card.id=="money")
    						    setTextCardElement(cardDiv, card.money+" 💳");
    						else await setCardElementById(cardDiv, card.id);
    						cardsDivs.push(cardDiv);
    			 		}
    				}, click:function(){
    				    for (let i in cardsDivs) {
    						let cardDiv = cardsDivs[i];
    						cardDiv.className = "card placed-card booster-card";
    						document.body.appendChild(cardDiv);
    						cardDiv.addEventListener("click", function() {
    							updatePlacedCard(this, {targetPos:document.getElementById("deck")});
    							this.style.width = '0';
    							this.style.fontSize = '0';
    							setTimeout(() => document.body.removeChild(this), 500);
    							if (cardsDivs.indexOf(cardDiv)==0) {
    								resolve();
    								veil.style.display = 'none';
    							}
    						});
    					}
    					booster.style.top = '150%';
    					setTimeout(()=>document.body.removeChild(booster), 500);
    				}});
    				if (boosterAssetUrl!=null) booster.src = boosterAssetUrl;
    				else booster.click();
			    });
			}
			
		function openclose(id,but,method='block'){
//Usage: openclose(id du truc à ouvrir,id du bouton qui l'ouvre, comment l'afficher)
			function foo(evt){
				if (evt.target.className != but){
					document.removeEventListener('click',foo);
					document.getElementById(id).style.display='none';
				}
			}
			switch (document.getElementById(id).style.display){
				case method:
					document.getElementById(id).style.display='none';
				break;
				default:
					document.addEventListener('click',foo);
					document.getElementById(id).style.display=method;
				break;
			}
		}
		
		function reload(after=1000){
			setTimeout(function(){
			   window.location.reload(/*1*/); // Kévin : j'ai retiré le 1 pour ne pas vider le cache client
			}, after);
		}
		
		function sendinfo(info){
			post('postinfo.php',info);
			//window.open('./postinfo.php?' + info, ' ',"height=1,width=1,alwaysLowered=yes,left=1920,top=1080");
			//self.focus();
			reload();
		}
		
		 function newalert(text,btns){
						btns.close=(btns.close == true || btns.close == '1') && btns.close !== undefined?true:false;
						btns.more=(btns.more == true || btns.more == '1') && btns.more !== undefined?true:false;
						
						let alertbox = document.getElementsByClassName('alerts')[0];
						let thealert = document.createElement('div');
						let nmbr = document.getElementsByClassName('alert').length;
						let selfid = "alert_" + nmbr;
						thealert.className='alert';
						thealert.id = selfid;
						
						let textcontent = document.createElement('a');
						
						textcontent.href=(text.href === undefined)?"":text.href;
						textcontent.target=(text.target === undefined)?"_self":text.target;
						textcontent.innerText=(text.text === undefined)?"":text.text;
						
						if (text.textstyle !== undefined){
							textcontent.style = text.textstyle;
						}
						
						thealert.appendChild(textcontent);
						
						let boutons = document.createElement('div');
						boutons.className = "btns";
						
							if(btns.close === true){
								let closebtn = document.createElement('span');
								closebtn.className = "closebtn";
								closebtn.innerText = "\u00D7";
								boutons.appendChild(closebtn);
								closebtn.onclick = () => {
									let selfnmbr = parseInt(selfid.slice(6),10);
									for (var i=selfnmbr+1; i<document.getElementsByClassName('morebtns').length; i++){
										document.getElementById('moretab_'+i).style="top:" + (i-1)*25 + "%;";
										document.getElementById('moretab_'+i).id="moretab_" + (i-1);
										
										let themorebtn=document.getElementById('alert_'+i).getElementsByClassName("btns")[0].getElementsByTagName("span")[1];
										if (themorebtn !== undefined){
											let z=i;
											themorebtn.onclick = () => {openclose('moretab_' + (z-1) ,'closebtn','flex');};
										}
										document.getElementById('alert_'+i).id="alert_" + (i-1);
									}
									thealert.parentNode.removeChild(thealert);
								};
							}
							
							if(btns.more === true){
								let morebtn = document.createElement('span');
								morebtn.className = "closebtn";
								morebtn.onclick = () => {openclose('moretab_' + nmbr ,'closebtn','flex')};
								morebtn.innerText = "\u22EE"
								morebtn.style = "font-size: 24px";
								boutons.appendChild(morebtn);
							}
							
						
						thealert.appendChild(boutons);
						
						if (btns.more == true){
						
							let moretab = document.createElement('div');
							moretab.className = "morebtns";
							moretab.id = 'moretab_' + nmbr;
							moretab.style="top:" + nmbr*25 + "%;";
							
								let moretabcontent = document.createElement('a');
								moretabcontent.innerText = btns.text;
								if(btns.oc !== undefined){
									moretabcontent.onclick = btns.oc;
								}
								
								moretab.appendChild(moretabcontent);
							
							thealert.appendChild(moretab);
						}
						
						if (text.alertstyle !== undefined){
							thealert.style = text.alertstyle;
						}
						
						alertbox.appendChild(thealert);
						
					}
		
		<?php
			//annonces pour rediriger vers la page tournoi
			if (isset($tournament)) {
				for ($i=0; $i<=$lengthTournmnt; $i++) {
		?>
					newalert({text:"Le tournoi "+$tournament[$i]['name']+" est en cours! Clique ici pour le rejoindre!", target:"", href:"tournament.php"}, {close:true});	
		<?php
				}
			}
			//autres annonces
			if (isset($cardsUser['rewardLevel']) && $cardsUser['rewardLevel']>=7 ){ ?>
			newalert({text:"Vous avez la possibilité de récuperer des Boosters",target:"",href:"#"}, {close:true});
		<?php } ?>
		<?php if( !isset($userinfos['discord']) || $userinfos['discord']=='1' ){ ?>
			newalert({text:"N'hésitez pas à rejoindre notre serveur discord",href:'https://discord.gg/4Dz8WYz',target:"_blank"},{close:true,more:true,text:"Ne plus afficher",oc: () => {sendinfo('discord=0')} });
		<?php } ?>
		<?php if (!isset($userinfos['webapp']) || $userinfos['webapp']=='1') { ?>
		    window.addEventListener("beforeinstallprompt", function(){
		        newalert({text:"Installer Poképrof sous forme d'application", href:"javascript:promptInstallWebApp();"},{close:true,more:true,text:"Ne plus afficher",oc: () => {sendinfo('webapp=0')} });
		    });
		<?php } ?>
			newalert({text:"On prépare la saison 1 ! :)\nDe nouvelles cartes et fonctionnalités arrivent",href:'#',target:""},{close:true});
		</script>
		<script>
			function createPopup(properties={}, inner=[]) {
				var p = new Promise(function(resolve,reject) {
					var p = JSON.parse(JSON.stringify(properties));
					p.className = p.className ? p.className+" "+" popup" : "popup";
					document.body.appendChild(createElement("div", {className:"popup-container"}, [
						createElement("div", p, [
							createElement("span", {className:"close-button"}, "❌", {click:function(){
								this.parentElement.parentElement.parentElement.removeChild(this.parentElement.parentElement);
								resolve();
							}})
						].concat(inner), {click:function(e){
							e.stopPropagation();
						}})
					], {click:function(){
						this.parentElement.removeChild(this);
						resolve();
					}}));
				});
				return p;
			}
			
			function search(bot=false) {
				sendRequest("POST", "searching.php", bot?'bot':'').then(function(response) {
					if (response=='founded' || response=='playing') {
						window.location.replace('play.php');
					} else if (response=='nodeck') {
						alert('Aïe ! Tu n\'as pas encore de deck ou pas assez de cartes dedans, créez-en un dans le menu principal en cliquant sur "Ton jeu de cartes". Si tu n\'as pas encore de cartes, ouvre les boosters gratuits sur la page principale en cliquant dessus. Bon amusement ! ;)');
						window.location.replace('.');
					} else if (response.startsWith("searching")) {
					    let infos = response.split(" ");
					    document.getElementById("search-infos").innerText = infos[1]+" joueurs en ligne (dont "+infos[2]+" en match)";
					}
				});
			}
			function cancelSearch() {
				sendRequest("POST", "searching.php", "cancel").then(function(response) {
					if (response=='playing') {
						window.location.replace('play.php');
					} else if (response=='canceled') {
						// do nothing 'cause it's gud
					}
				});
			}
			
			function onHashChange() {
				var args = window.location.hash.replace("#", "").split("=");
				switch (args[0]) {
				case "user":
					sendRequest("GET", "getuser.php?id="+args[1]).then(function(r) {
						if (r == "not found") return;
						r = JSON.parse(r);
						let borderClass = r.tags.includes("gold-border") ? "gold-border"
								: r.tags.includes("silver-border") ? "silver-border"
								: r.tags.includes("bronze-border") ? "bronze-border"
								: r.tags.includes("rainbow-border") ? "rainbow-border"
								: ""
						createPopup({className:"user-profile"}, [
							createElement("img", {className:"pp "+borderClass, src:"/ayaya/assets/utilisateurs/unset.png"}),
							createElement("span", {className:"name"}, r.name),
							createElement("span", {className:"trophies"}, r.trophies),
							createElement("span", {className:"cards"}, r.cards),
							createElement("div", {className:"tags"}, r.tags.filter(e=>e.startsWith("@")).map(function(tag){
								return createElement("span", {}, tag.replace("@", ""));
							}))
						]).then(function() {
							window.location.hash = "";
						});
					});
					break;
				case "search":
					if (resolveTuto) return resolveTuto();
					search();
					var searchTimerId = setInterval(search, 3000);
					createPopup({className:"search-popup"}, [
						createElement("span", {}, "Recherche d'adversaire..."),
						createElement("br"),
						createElement("span", {id:"search-infos"}, ""),
						createElement("br"),
						createElement("span", {className:"loading"}, "🔍"),
						createElement("br"),
						createElement("button", {className:"bot-button"}, "Affronter l'ordinateur", {click:function(){
							search(true);
						}}),
						createElement("button", {className:"back-button"}, "Retourner au menu", {click:function(){
							this.parentElement.parentElement.click();
						}}),
					]).then(function() {
						clearInterval(searchTimerId);
						window.location.hash = "";
						cancelSearch();
					});
					break;
				case "shop?all":
				case "shop":
					sendRequest("GET", "getshop.php"+(args[0]=="shop?all"?"?all":"")).then(function(response) {
						shop = JSON.parse(response);
						let moneySpan;
						createPopup({className:"shop"}, [
							createElement("span", {className:"title"}, "Boutique"),
							moneySpan = createElement("span", {className:"money"}, shop.money+" 💳"),
							createElement("span", {className:"subtitle"}, "Cartes quotidiennes :"),
							createElement("div", {},
								shop.cards.map(function(card) {
								    let cardDiv;
									let div = createElement("div", {className:"shop-card"}, [
										cardDiv = createElement("div", {}, [], {click:function(){printCardAbout(card.id);}}),
										createElement("button", {}, card.price+" 💳", {click:function(){
										    if (parseInt(shop.money) < parseInt(card.price))
										        return alert("Tu n'as assez de 💳");
										    buyShopCard(card.id);
										    shop.money -= card.price;
										    moneySpan.innerText = shop.money+" 💳";
										}})
									]);
									setCardElementById(cardDiv, card.id);
									return div;
								})
							),
							createElement("span", {className:"subtitle"}, "Boosters :"),
							createElement("div", {},
								shop.boosters.map(function(booster) {
									return createElement("div", {className:"booster"}, [
										createElement("img", {src:"assets/boosters/"+booster.id+".png"}),
										createElement("button", {}, "Détail", {click: function() {
											createPopup({className:"booster-detail"}, [
												createElement("span", {className:"subtitle"}, booster.name),
												createElement("span", {}, booster.description+"\n"+booster.cards.length+" cartes obtensibles"),
												createElement("div", {}, booster.cards.map(function(id){
													let cardDiv = createElement("div", {}, [], {click:function(){printCardAbout(id);}});
													setCardElementById(cardDiv, id);
													return cardDiv;
												}))
											]);
										}}),
										createElement("button", {}, booster.price+" 💳", {click:function(){
										    if (parseInt(shop.money) < parseInt(booster.price))
										        return alert("Tu n'as assez de 💳");
										    openShopBooster(booster.id);
										    shop.money -= booster.price;
										    moneySpan.innerText = shop.money+" 💳";
										}})
									]);
								})
							)
						]).then(function() {
							window.location.hash = "";
						});
					});
					break;
				case "card":
				    printCardAbout(args[1]);
				    break;
				case "rules":
				    sendRequest("GET","getrules.php").then(function(rulesHTML){
				        let rulesDiv = createElement("div");
				        rulesDiv.innerHTML = rulesHTML;
				        createPopup({className:"rules"}, [rulesDiv]).then(function() {
							window.location.hash = "";
						});
				    });
				    break;
				case "history":
					sendRequest("GET", "gethistory.php").then(function(r) {
						let history = JSON.parse(r);
						createPopup({className:"history"}, [
							createElement("span", {className:"title"}, "Historique")
						].concat(history.map(function(match) {
						    return createElement("div", {}, [
						        createElement("div", {}, [
						            createElement("span", {}, match.win?"Victoire - ":"Défaite - "),
						            createElement("time", {datetime:match.date}, new Date(match.date).toLocaleString("fr-FR", {weekday:"long",day:"numeric",month:"long",year:"numeric",hour:"numeric",minute:"numeric"}))    
						        ]),
						        createElement("div", {className:"player"}, [
						            createElement("a", {href:"#user="+match.opponentId1}, match.opponentName1),
						            createElement("span", {}, " ("+(match.trophies1<0?"":"+")+match.trophies1+" 🏆)"),
						            createElement("div", {className:"deck"}, match.deck1.map(function(cardId) {
						                let cardDiv = createElement("div", {}, [], {click:()=>printCardAbout(cardId)});
						                setCardElementById(cardDiv, cardId, {}, true);
						                return cardDiv;
						            }))
						        ]),
						        createElement("div", {className:"player"}, [
						            createElement("a", {href:"#user="+match.opponentId2}, match.opponentName2),
						            createElement("span", {}, " ("+(match.trophies2<0?"":"+")+match.trophies2+" 🏆)"),
						            createElement("div", {className:"deck"}, match.deck2.map(function(cardId) {
						                let cardDiv = createElement("div", {}, [], {click:()=>printCardAbout(cardId)});
						                setCardElementById(cardDiv, cardId, {}, true);
						                return cardDiv;
						            }))
						        ])
						    ])
						}))).then(function() {
							window.location.hash = "";
						});
					});
					break;
				}
			}
			window.addEventListener("hashchange", onHashChange);
			onHashChange();
			
			function openShopBooster(boosterId) {
				let promise = new Promise((resolve, reject) =>{
					displayLoading(sendRequest("POST", "loot.php", "what=shopbooster&id="+boosterId)).then(function(response) {
						if (response == "need money") {
							aff("Tu manques de 💳");
							resolve();
						} else if (response == "not enough cards") {
						    aff("Ce booster ne contient pas encore assez de cartes, crée-en !");
						    resolve();
						} else {
							openBooster(JSON.parse(response), "assets/boosters/"+boosterId+".png").then(resolve);
						}
					});
				});
				return promise;
			}
			
			function buyShopCard(cardId) {
			    let promise = new Promise((resolve, reject) =>{
					displayLoading(sendRequest("POST", "loot.php", "what=shopcard&id="+cardId)).then(function(response) {
						if (response == "need money") {
							aff("Tu manques de 💳");
							resolve();
						} else {
							openBooster(JSON.parse(response)/*, null TODO*/).then(resolve);
						}
					});
				});
				return promise;
			}
			
			async function tuto() {
				await displayTuto("Hey ! Moi c'est Abo ! Je vais vous guider dans vos premiers pas sur Poképrofs", {button:"Ok Boumiz"});
				await displayTuto("Prenez déjà ces quelques boosters (paquets de cartes) pour bien démarrer", {button:"Ok"});
				await openRewardBooster(3);
				await displayTuto("Les cartes que vous avez obtenus sont très intéressantes, mais il vous en faudra plus pour vous battre", {button:"Ok"});
				await openRewardBooster(3);
				document.body.classList.remove("middle","right");
				document.body.classList.add("left");
				await displayTuto("Pas mal, pas mal tout ça ! Malheureusement vous n'avez plus de récompenses, il va falloir se battre pour en gagner ! Vite formons un deck", {show:[document.getElementById("deck")]});
			}
			async function tuto2() {
				await displayTuto("Très bien !", {button:"➡"});
				await displayTuto(((document.querySelector("#deck .card .title")||{innerText:"Abo gaming"}).innerText)+" est une très bonne carte !", {button:"Quel bon choix"});
				await displayTuto("D'ailleurs regardez, une carte gratuite est disponible toutes les 12 heures, prenez-la", {show:[document.getElementById("free-card")]});
				await displayTuto("Très bien maintenant que vous avez créé votre deck, c'est l'heure d'aller au combat", {button:"Go !"});
				await displayTuto("Vous n'avez qu'à cliquer sur \"Jouer\"", {show:[document.getElementById("play-button")]});
				window.location.href = "play.php?tuto";
			}
			<?= isset($_REQUEST['tuto']) ? "tuto();" : (isset($_REQUEST['tuto2']) ? "tuto2();" : "") ?>
		</script>
	</body>
</html>
