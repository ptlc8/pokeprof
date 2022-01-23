<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8" />
		<title>Deck de cartes</title>
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link rel="stylesheet" href="style.css?2" />
		<link rel="stylesheet" href="deck.css?<?=time()?>" />
		<script src="cards.js?8410"></script>
		<link rel="icon" type="image/png" href="assets/icon.png" />
	</head>
	<body>
		<?php
		include('init.php');
		$user = login(true,true);
		
		// récupération des cartes et du deck du joueur ~
		$result = sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "'");
		if ($result->num_rows === 0) {
			exit("<script>window.location.replace('');</script>");
		}
		$cardsUser = $result->fetch_assoc();
		
		?>
		<span class="title">|</span>
		<a href=".<?=isset($_REQUEST['tuto'])?"?tuto2":""?>"><div style="position:absolute; top:4%; left:1%;">
	        <span class="button">Retourner au menu principal</span>
	    </div></a> <!--Modif de Léo-->
		<div id="main">
		    <div class="deck-view">
		    	<span class="subtitle">Ton jeu de cartes : Deck <span id="deck-name"></span> (<span id="deck-cost"></span><img class="emote" src="assets/mana.svg" />)</span>
		    	<div id="deck"></div>
		    </div>
		    <div id="deck-chooser" class="deck-chooser"></div>
		    <div class="all-cards-view">
		    	<span class="subtitle">Ta collection de cartes (<span id="cards-amount"><?=count(array_filter(json_decode($cardsUser['cards'], true), function($id){return intval($id)===$id;}, ARRAY_FILTER_USE_KEY))?>/<?=sendRequest("SELECT id FROM CARDS WHERE official > 0")->num_rows?>+<?=count(array_filter(json_decode($cardsUser['cards'], true), function($id){return intval($id)!==$id;}, ARRAY_FILTER_USE_KEY))?></span>)</span>
		    	<label for="sort-by">Trier par</label>
		    	<select id="sort-by" onchange="sortBy=this.value;refreshCards();">
		    	    <option value="id">Id</option>  
		    	    <option value="rarity">Rareté</option>
		    	    <option value="type">Type</option>
		    	    <option value="name">Nom</option>
		    	    <option value="cost">Coût</option>
		    	    <option value="color">Couleur</option>
		    	    <option value="amount">Quantité</option>
		    	</select>
		    	<input type="checkbox" id="invert-sort" onchange="invertSort=this.checked;refreshCards();" />
		    	<label for="invert-sort">Inverser le tri</label>
		    	<br />
		    	<input type="text" placeholder="🔍 Rechercher" onkeyup="search=this.value;refreshCards();" style="min-width:60%" />
		    	<br />
		    	<input type="checkbox" id="normals" checked onchange="showNormals=this.checked;refreshCards();" />
		    	<label for="normals">Normales</label>
		    	<input type="checkbox" id="fullarts" checked onchange="showFullarts=this.checked;refreshCards();" />
		    	<label for="fullarts">Fullart</label>
		    	<input type="checkbox" id="shinies" checked onchange="showShinies=this.checked;refreshCards();" />
		    	<label for="shinies">Shiny</label>
		    	<input type="checkbox" id="holographics" checked onchange="showHolographics=this.checked;refreshCards();" />
		    	<label for="holographics">Holographique</label>
		    	<br />
		    	<div id="all-cards"></div>
			    <hr style="width:98%;height:6em;display:grid;">
		    </div>
		</div>
		<script>
		    var cards = [];
		    var decks = [];
		    var choosenDeck = 0;
		    var sortBy = "id";
		    var invertSort = false;
		    var search = "";
		    var showNormals = true;
		    var showFullarts = true;
		    var showShinies = true;
		    var showHolographics = true;
		    (async function() {
    		    cards = Object.entries(<?=$cardsUser['cards']?>).map(c=>({id:c[0],amount:c[1]}));
    		    decks = <?=$cardsUser['deck']?>;
    		    choosenDeck = <?=$cardsUser['choosenDeck']?>;
    		    showDeck(choosenDeck);
    		    deckChooserDiv = document.getElementById("deck-chooser");
		        for (let i = 0; i < decks.length; i++)
		            deckChooserDiv.appendChild(createElement("span", {className:"button"}, "Deck "+String.fromCharCode(65+i), {click:function(){
		                chooseDeck(i);
		            }}));
    		    for (let i in cards) {
    		        let card = cards[i];
    		        cards[i] = await getCardInfos(card.id);
    		        cards[i].id = card.id;
    		        cards[i].amount = card.amount;
    		    }
    		    refreshCards();
    		})();
		    async function refreshCards() {
		        var allCardsDiv = document.getElementById("all-cards");
		        allCardsDiv.innerHTML = "";
		        for (let card of cards.filter(function(c){
		            return c.name.toLowerCase().includes(search.toLowerCase())
		                && (showNormals||(c.id.includes("p")||c.id.includes("s")||c.id.includes("h")))
		                && (showFullarts||!c.id.includes("p"))
		                && (showShinies||!c.id.includes("s"))
		                && (showHolographics||!c.id.includes("h"));
		        }).sort(function(a,b){
		            let order = 0;
		            if (["id","amount","rarity","cost"].includes(sortBy)) order = parseInt(a[sortBy])-parseInt(b[sortBy]);
		            if (["name"].includes(sortBy)) order = a[sortBy]<b[sortBy]?-1:1;
		            if ("type"==sortBy) order = a.type!=b.type?(a.type<b.type?-1:1):(a.type=="prof"?(a.types[0]==b.types[0]?0:a.types[0]<b.types[0]?-1:1):0);
		            if ("color"==sortBy) order = new Number(a.color.replace("#","0x"))-new Number(b.color.replace("#","0x"))
		            return invertSort ? -order : order;
		        })) {
		            let finalId = card.id;
		            let finalAmount = card.amount;
		            let cardDiv;
		            allCardsDiv.appendChild(createElement("div", {className:"card-view no-shadow"}, [
		                cardDiv = createElement("div", {id:"all-cards-"+card.id}, [], {click:function(e){
		                    select(finalId, e);
		                    e.stopPropagation();
		                }}),
		                createElement("div", {className:"menu"}, [
		                    createElement("span", {className:"button"}, "Utiliser", {click:function(e){
		                        select(finalId, e);
		                        e.stopPropagation();
		                        scrollTo({top:0, behavior:'smooth'})
		                    }}),
		                    card.amount>3?createElement("span", {className:"button"}, "Échanger", {click:function(e){
		                        printSellMenu(finalId, card.amount);
		                    }}):"",
		                    createElement("span", {className:"button"}, "Infos", {click:function(e){
		                        printCardAbout(finalId);
		                    }})
		                ])
		            ]));
		            setCardElement(cardDiv, card, card.id.includes("p"), card.id.includes("s"), card.id.includes("h"));
		        }
		    }
		    function showDeck(n) {
		        var deckDiv = document.getElementById("deck");
		        deckDiv.innerHTML = "";
		    	for (let i = 0; i < decks[n].length; i++) {
		    	    let fixedI = i;
		    		deckDiv.appendChild(createElement("div", {className:"card-view"}, [
		    		    createElement("div", {id:"deck-"+i, className:"card"}, [], {click:function(event){place(this, fixedI, event)}})
		    		]));
		    		if (decks[n][i])
		    		    setCardElementById(document.getElementById("deck-"+i), decks[n][i]);
		    		else setCardBackElement(document.getElementById("deck-"+i));
		    	}
		    	document.getElementById("deck-name").innerText = String.fromCharCode(65+n);
		    	let cost = 0;
		    	Promise.all(decks[n].map(id=>getCardInfos(id))).then(function(cards){
		    	    document.getElementById("deck-cost").innerText = cards.reduce((acc,card)=>acc+parseInt(card.cost), 0) / decks[n].length;
		    	});
		    }
		    function chooseDeck(n) {
		        sendRequest("POST", "choosedeck.php", "deck="+n);
		        choosenDeck = n;
		        showDeck(n);
		    }
			var movingId = -1;
			var movingCard = document.createElement('div');
			movingCard.className = 'moving-card card';
			document.body.addEventListener('mousemove', function(e) {
				if (movingId == -1) return;
				movingCard.style.left = (e.pageX||e.clientX)+'px';
				movingCard.style.top = (e.pageY||e.clientY)+'px';
			});
			document.body.addEventListener("click", function(e) {
			    if (movingId==-1) return;
				movingId = -1;
				movingCard.parentElement.removeChild(movingCard);
				for (let el of document.querySelectorAll('.deck-view .card-view'))
					el.classList.remove('stress');
			});
			function select(id, e) {
			    if (resolveTuto) resolveTuto();
				movingId = id;
				setCardElementById(movingCard, id);
				document.body.appendChild(movingCard);
				movingCard.style.left = e.clientX+'px';
				movingCard.style.top = e.clientY+'px';
				for (let el of document.querySelectorAll('.deck-view .card-view'))
					el.classList.add('stress');
			}
			function place(cardEl, index, e) {
				if (movingId == -1) {
				    if (cardEl.dataset.id)
				        printCardAbout(cardEl.dataset.id);
				    return;
				}
				if (resolveTuto) resolveTuto();
				let id = movingId;
				post('usecard.php', 'deck='+choosenDeck+'&id='+movingId+'&index='+index, (response) => {
					switch (response.split(" ")[0]) {
					case 'success':
					    decks[choosenDeck][index] = id;
						setCardElementById(cardEl, id);
						break;
					case 'maxamountindeck':
						aff("Cette carte est limitée à "+response.split(" ")[2]+" occurences par jeu de cartes");
						break;
					case 'notenoughofthis':
					    let n = response.split(" ")[1];
					    aff("Tu n'as que "+n+" exemplaire"+(n>1?"s":"")+" de cette carte");
					    break;
					default:
						alert(response);
					}
				});
				movingId = -1;
				movingCard.parentElement.removeChild(movingCard);
				for (let el of document.querySelectorAll('.deck-view .card-view'))
					el.classList.remove('stress');
			}
			async function printSellMenu(cardId, amount) {
                var div = document.getElementById('sell-menu');
                if (div) div.parentElement.removeChild(div);
                div = createElement("div", {id:"sell-menu"});
                var menu = document.createElement('div');
                var close = createElement("span", {className:"close"}, "X", {click: () => {
                    div.parentElement.removeChild(div);
                    about.parentElement.removeChild(about);
                }});
                menu.appendChild(close);
                var text = createElement("span", {});
                text.innerHTML = 'Combien de cartes <b>'+(await getCardInfos(cardId)).name+'</b> voulez-vous vendre ?';
                menu.appendChild(text);
                var input = createElement("input", {type:"number", id:"sell-amount", min:1, max:amount-3, value:amount-3});
                menu.appendChild(input);//Modif de Léo
                removeCardAbout();
                var about = createElement("div", {id:"card-about"});
                document.body.appendChild(about);
                var cvs = createElement("div");
                about.appendChild(cvs);
                setCardElementById(cvs, cardId);
                var infos = createElement("div", {id:"card-about-infos"});
                about.appendChild(infos);
                var cardInfos = await getCardInfos(cardId);
                var title = createElement("span", {className:"title"}, cardInfos.name);
                infos.appendChild(title);
                var button = createElement("span", {className:"button"}, "Vendre", {click: () => {
                    sendRequest("POST", 'sell.php', 'card='+cardId+'&amount='+input.value).then((response)=>{
                        switch (response) {
                            case 'card doesn\'t have/exist':
                                aff("Vous ne pouvez pas vendre une carte que vous ne possédez pas.");
						        break;
                            case 'notenoughofthis':
                                aff("ERREUR!");
                                break;
                            case 'can\'t sell':
                                aff("Cette carte ne peut pas être vendue.");
                                break;
                            case 'success':
                                aff("Vous avez récupéré des points de récompenses!");
                                cards.find((c)=>c.id==cardId).amount -= input.value;
                                refreshCards();
                                break;
                            default:
                                aff("Vous n'avez pas assez d'exemplaires de cette carte.");
                                break;
                        }
                    });
                    div.parentElement.removeChild(div);
                    about.parentElement.removeChild(about);
                }});
                menu.appendChild(button);
                div.appendChild(menu);
                document.body.appendChild(div);
                return div;
            }
            async function tuto() {
			    await displayTuto("Bienvenue dans la page de création de deck", {button:"➡"});
			    await displayTuto("Vous allez pouvoir placer les quelques cartes que vous venez d'obtenir", {button:"➡"});
			    await displayTuto("Prenez l'une de ces cartes en cliquant sur \"Utiliser\"", {show:[...document.querySelectorAll(".all-cards-view .card-view")].slice(0, 5)});
			    await displayTuto("Ensuite placez-la ici à droite", {show:[...document.querySelectorAll(".deck-view .card-view")].slice(0,5)});
			    await displayTuto("C'est très bien tout ça, je vous laisse compléter ce deck.\nQuand vous avez fini cliquez sur \"Retourner au menu principal\" en haut à gauche, je vous y attends", {button:"C'est parti !"});
			}
			<?=isset($_REQUEST['tuto'])?"tuto();":""?>
			function post(url, content='', onResponse=()=>{}) {
				var xhr = new XMLHttpRequest();
				xhr.open("POST", url);
				xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				xhr.onreadystatechange = function() {
					if (this.readyState === XMLHttpRequest.DONE && this.status === 200) onResponse(xhr.responseText);
				};
				xhr.send(content);
			}
			function aff(text, t=4000, where=document.body) {
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
		</script>
	</body>
</html>
<!-- // Euuhhh... C'est quoi ça ???
    async function exchangeCard(cardId) {
    removeCardAbout();
    var about = document.createElement('div');
    about.id = 'card-about';
    about.onclick = () => about.parentElement.removeChild(about);
    document.body.appendChild(about);
    var cvs = document.createElement('canvas');
    about.appendChild(cvs);
    drawCardById(cvs, cardId);
    var infos = document.createElement('div');
    infos.id = 'card-about-infos';
    about.appendChild(infos);
    var cardInfos = await getCardInfos(cardId);
    var title = document.createElement('span');
    title.className = 'title';
    title.textContent = cardInfos.name;
    infos.appendChild(title);
    for (let info of ['origin','desc','use']) {
        if (!Object.keys(cardInfos).includes(info)) continue;
        let text = document.createElement('span');
        text.className = 'text';
        //text.textContent = 'Êtes-vous sûr de vouloir vendre cette carte ? Vous ne pourrez plus l'utiliser.'; //C'est cette ligne qui fait tout planter.
        infos.appendChild(text);
    }
} //Modif de Léo-->
