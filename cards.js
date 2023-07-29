//window.onload = () => {
    console.log(
        "Let's play"
    + '\n┌─────┬─────┬─┐ ┌─┬────┬─────┬─────┬─────┬─────┐┌─┐'
    + '\n│J┌─┐N│E┌─┐ │A│┌┘B│O┌──┤ ┌─┐F│I┌─┐ │G┌─┐ │ ┌───┘│ │'
    + '\n│M└─┘ │J│ │ │B└┘ ┌┤U└─┐│ └─┘ │N└─┘ │Z│ │ │P└─┐  │ │'
    + '\n│A┌───┤I│ │ │O┌┐ └┤M┌─┘│ ┌───┤T┌─┐ ┤G│ │ │P┌─┘  └─┘'
    + '\n│S│   │B└─┘ │ │└┐I│Z└──┤ │   │Z│ │ │Z└─┘ │L│    ┌─┐'
    + '\n└─┘   └─────┴─┘ └─┴────┴─┘   └─┘ └─┴─────┴─┘    └─┘'
    + "\n  Si tu lis ceci, c'est que tu devrais peut-être"
    + "\n rejoindre l'équipe de developpement de Poképrofs :"
    + "\n             https://discord.gg/pbtczJs");
//}

var q = 8;

/* Les 2 fonctions toujours utiles d'Ambi ;) */
function sendRequest(method, url, body=undefined, headers={"Content-Type":"application/x-www-form-urlencoded"}) {
    var promise = new (Promise||ES6Promise)(function(resolve, reject) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url);
        for (h of Object.keys(headers))
            xhr.setRequestHeader(h, headers[h]);
        xhr.onreadystatechange = function() {
            if (this.readyState == XMLHttpRequest.DONE && this.status == 200) {
                resolve(this.response);
            }
        }
        xhr.onerror = reject;
        xhr.send(body);
    });
    return promise;
}
function createElement(tag, properties={}, inner=[], eventListeners={}) {
    let el = document.createElement(tag);
    for (let p of Object.keys(properties)) if (p != "style") el[p] = properties[p];
    if (properties.style) for (let p of Object.keys(properties.style)) el.style[p] = properties.style[p];
    if (typeof inner == "object") for (let i of inner) el.appendChild(typeof i == "string" ? document.createTextNode(i) : i);
    else el.innerText = inner;
    for (let l of Object.keys(eventListeners)) el.addEventListener(l, eventListeners[l]);
    return el
}
function sleep(ms) {
    return new (Promise||ES6Promise)(function(resolve, reject) {
        setTimeout(resolve, ms);
    });
}

/* Récupération des infos des cartes */
const allcards = {};
function post_(url, content='') { // deprecated
	var promise = new Promise(async(resolve, reject) => {
		var xhr = new XMLHttpRequest();
		xhr.open("POST", url);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.onreadystatechange = function() {
			if (this.readyState === XMLHttpRequest.DONE && this.status === 200) resolve(xhr.responseText);
		};
		xhr.send(content);
	});
	return promise;
}
async function getCardInfos(cardId, edit={}) {
    cardId = parseInt(cardId);
	var promise = new Promise(async(resolve, reject) => {
		let infos;
		if (Object.keys(allcards).includes(cardId+'')) {
			infos = allcards[cardId];
		} else {
			/**/if (sessionStorage && sessionStorage.getItem('card'+cardId)) {
				infos = JSON.parse(sessionStorage.getItem('card'+cardId));
				//console.log(cardId+" from sessionStorage");
			} else /**/{
				let json = await sendRequest("GET", "api/card/get.php?card="+cardId);
				infos = JSON.parse(json);
				//console.log(cardId+" from request")
				if (sessionStorage) sessionStorage.setItem('card'+cardId, json);
			}
			allcards[cardId] = infos;
		}
		if (edit.hp) infos.hpmax = infos.hp;
		infos = JSON.parse(JSON.stringify(infos)); // clone par JSON
		for (let k in edit) {
			if (k=="id")
			    infos[k] = parseInt(edit[k]);
			else
			    infos[k] = edit[k];
		}
		resolve(infos);
	});
	return promise;
}
async function drawCardById(cvs, cardId, edit={}) {
	return drawCard(cvs, await getCardInfos(cardId, edit));
}
async function getType(typeId) {
    var promise = new Promise(async(resolve, reject) => {
        let typeInfos;
		if (sessionStorage && sessionStorage.getItem("pokeprof.type."+typeId)) {
			typeInfos = JSON.parse(sessionStorage.getItem("pokeprof.type."+typeId));
		} else {
			let types = JSON.parse(await sendRequest("GET", "api/card/getfighterstypes.php"));
            if (sessionStorage) for (let id in types) {
    			sessionStorage.setItem("pokeprof.type."+id, JSON.stringify(types[id]));
            }
            typeInfos = types[typeId]||{name:typeId};
		}
		resolve(typeInfos);
	});
	return promise;
}

/* Dessin des cartes */
// Nouvelle génération des cartes
async function setCardElementById(div, cardId, edit={}, noText=false) {
    if (cardId == "back") setCardBackElement(div);
    else setCardElement(div, await getCardInfos(parseInt(cardId), edit), typeof cardId=="string"&&cardId.includes("p"), typeof cardId=="string"&&cardId.includes("s"), typeof cardId=="string"&&cardId.includes("h"), noText);
}
// Il faut que fontSize = width/25
async function setCardElement(div, infos, fullart=false, shiny=false, holo=false, noText=false) {
    let lum = calcLuminance(infos.color);
	let textColor = lum>0.25 ? 'black' : 'whitesmoke';
	let textColor2 = lum<0.75 ? 'whitesmoke' : 'black';
    let atks = [];
    if (infos.atk1 && !noText) atks.push(
        createElement("div", {}, [
            createElement("span", {className:"name"}, infos.atk1.name),
            createElement("span", {className:"damage"}, infos.atk1.dama),
            createElement("span", {className:"description"}, infos.atk1.desc)
        ])
    );
    if (infos.atk2 && !noText) atks.push(
        createElement("div", {}, [
            createElement("span", {className:"name"}, infos.atk2.name),
            createElement("span", {className:"damage"}, infos.atk2.dama),
            createElement("span", {className:"description"}, infos.atk2.desc)
        ])
    );
    if (div.getElementsByClassName("inner")[0]) div.removeChild(div.getElementsByClassName("inner")[0]);
    if (!div.classList.contains("card")) div.classList.add("card");
    if (div.classList.contains("back")) div.classList.remove("back");
    div.style.setProperty('--text-color', textColor);
    div.style.setProperty('--text-ocolor', textColor=="black"?"whitesmoke":"black");
    div.style.setProperty('--text-color2', textColor2);
    div.style.setProperty('--text-ocolor2', textColor2=="black"?"whitesmoke":"black");
    let inner;
    div.appendChild(inner = createElement("div", {className:"inner"+(shiny?" shiny":"")+(holo?" holo":""), style:fullart?{backgroundColor:infos.color,backgroundImage:"url('"+(infos.image?infos.image.src:"assets/cards/"+parseInt(infos.id)+".png")+"')"}:{backgroundColor:infos.color}}, [
        createElement("span", {className:"image", style:fullart?{}:{backgroundImage:"url('"+(infos.image?infos.image.src:"assets/cards/"+parseInt(infos.id)+".png")+"')"}}),
        noText?"":createElement("span", {className:"title"}, infos.name),
        createElement("div", {className:"attacks"}, atks),
        noText?"":createElement("span", {className:"rarity", style:{color:infos.rarity==2?"#ff7f00":infos.rarity==3?"#c000c0":infos.rarity==4?"#40e0d0":"#808080"}}, infos.rarity==1?"commune":infos.rarity==2?"rare":infos.rarity==3?"épique":infos.rarity==4?"légendaire":""),
        createElement("div", {className:"mana", style:{backgroundImage:"url('assets/mana"+(lum<0.75?"-white":"")+".svg')"}}, infos.cost)
    ]));
    if (infos.type == 'prof') {
        inner.appendChild(createElement("div", {className:"life", style:{backgroundImage:"url('assets/heart"+(lum<0.75?"-white":"")+".svg')"}}, !infos.hpmax?infos.hp:[
            createElement("span", {}, infos.hp),
            createElement("span", {className:"max"}, infos.hpmax)
        ]));
        if (infos.shield) inner.appendChild(createElement("div", {className:"shield", style:{backgroundImage:"url('assets/shield"+(lum<0.75?"-white":"")+".svg')"}}, infos.shield));
        if (infos.strength) inner.appendChild(createElement("div", {className:"strength", style:{backgroundImage:"url('assets/fist"+(lum<0.75?"-white":"")+".svg')"}}, infos.strength));
        //inner.appendChild(createElement("span", {className:"proftype"}, infos.proftype=='math'?'Maths':infos.proftype=='physik'?'Physique':infos.proftype=='info'?'Informatique':infos.proftype=='lang'?'Langue':infos.proftype=='admin'?'Administration':infos.proftype=='student'?'Élève':infos.proftype=='asso'?'Association':infos.proftype));
        if(!noText) {
            let proftypes = "";
            for (let type of (infos.types || [infos.proftype]))
                proftypes += (await getType(type)).name + " ";
            inner.appendChild(createElement("span", {className:"proftype"}, proftypes));
        }
    } else {
    	if(!noText)inner.appendChild(createElement("span", {className:"type"}, infos.type=='place'?'Lieu':infos.type=='effect'?'Effet':'Autre'));
    }
    if (infos.amount!==undefined)
        inner.appendChild(createElement("span", {className:"amount"}, infos.amount))
}
function setCardBackElement(div) {
    div.innerHTML = "";
    if (!div.classList.contains("card")) div.classList.add("card");
    if (!div.classList.contains("back")) div.classList.add("back");
    div.appendChild(createElement("div", {className:"inner"}));
}
async function setTextCardElement(div, text, color="#27d1e8", shiny=false, holo=false) {
    let lum = calcLuminance(color);
	let textColor = lum>0.25 ? 'black' : 'whitesmoke';
	let textColor2 = lum<0.75 ? 'whitesmoke' : 'black';
    div.innerHTML = "";
    if (!div.classList.contains("card")) div.classList.add("card");
    if (div.classList.contains("back")) div.classList.remove("back");
    div.style.setProperty('--text-color', textColor);
    div.style.setProperty('--text-ocolor', textColor=="black"?"whitesmoke":"black");
    div.style.setProperty('--text-color2', textColor2);
    div.style.setProperty('--text-ocolor2', textColor2=="black"?"whitesmoke":"black");
    let inner;
    div.appendChild(inner = createElement("div", {className:"inner"+(shiny?" shiny":"")+(holo?" holo":""), style:{backgroundColor:color}}, [
        createElement("span", {className:"text"}, text)
    ]));
}
async function getManaURL(color="") {
    if (window["manaURL"+color]) return window["manaURL"+color];
    var cvs = createElement("canvas", {width:144,height:144});
    var ctx = cvs.getContext("2d");
    ctx.lineWidth = 4;
    ctx.strokeStyle = color;
    manaCrystal(cvs.getContext("2d"), 12, 12, 120, 120);
    var promise = new Promise(function(resolve, reject) {
        cvs.toBlob(function(blob) {
            window["manaURL"+color] = URL.createObjectURL(blob);
            resolve(window["manaURL"+color]);
        });
    });
    return promise;
}
async function getHeartURL(color="") {
    if (window["heartURL"+color]) return window["heartURL"+color];
    var cvs = createElement("canvas", {width:144,height:132});
    var ctx = cvs.getContext("2d");
    ctx.lineWidth = 4;
    ctx.strokeStyle = color;
    heart(cvs.getContext("2d"), 12, 12, 120, 108);
    var promise = new Promise(function(resolve, reject) {
        cvs.toBlob(function(blob) {
            window["heartURL"+color] = URL.createObjectURL(blob);
            resolve(window["heartURL"+color]);
        });
    });
    return promise;
}
async function getShieldURL(color="") {
    if (window["shieldURL"+color]) return window["shieldURL"+color];
    var cvs = createElement("canvas", {width:144,height:144});
    var ctx = cvs.getContext("2d");
    ctx.lineWidth = 4;
    ctx.strokeStyle = color;
    drawShield(cvs.getContext("2d"), 12, 12, 120, 120);
    var promise = new Promise(function(resolve, reject) {
        cvs.toBlob(function(blob) {
            window["shieldURL"+color] = URL.createObjectURL(blob);
            resolve(window["shieldURL"+color]);
        });
    });
    return promise;
}
async function getStrengthURL(color="") {
    if (window["strengthURL"+color]) return window["strengthURL"+color];
    var cvs = createElement("canvas", {width:144,height:144});
    var ctx = cvs.getContext("2d");
    ctx.lineWidth = 4;
    ctx.strokeStyle = color;
    drawFist(cvs.getContext("2d"), 12, 12, 120, 120);
    var promise = new Promise(function(resolve, reject) {
        cvs.toBlob(function(blob) {
            window["strengthURL"+color] = URL.createObjectURL(blob);
            resolve(window["strengthURL"+color]);
        });
    });
    return promise;
}


/* dessin des cartes sur canvas (déprécié) */
async function drawCard(cvs, infos) {
	let imgSrc = "assets/cards/"+infos.id+".png";//infos.image;
	let name = infos.name;
	let type = infos.type;
	let color = infos.color;
	let invo = infos.cost;
	let hp = infos.hp;
	let proftype = infos.proftype || '';
	let atk1 = infos.atk1 || {name:'',desc:'',dama:''};
	let atk2 = infos.atk2 || {name:'',desc:'',dama:''};
	let rarity = infos.rarity;
	let illus = infos.illus;
	let shield = infos.shield;
	let strength = infos.strength;
	//color = type=='effect'?'#ff00ff':type=='place'?'#fcf790':proftype=='math'?'#0000ff':proftype=='info'?'#09a3b5':proftype=='physik'?'#ff7f00':proftype=='lang'?'#00ff00':proftype=='admin'?'#db4f64':proftype=='student'?'#ffffff':'#000000';
	let lum = calcLuminance(color);
	let textColor = lum>0.25 ? 'black' : 'whitesmoke';
	let textColor2 = lum<0.75 ? 'whitesmoke' : 'black';
	var promise = new Promise(async(resolve, reject) => {
		let w = cvs.width = 108*q;
		let h = cvs.height = 144*q;
		let ctx = cvs.getContext('2d');
		ctx.fillArc = (x,y,r,as,ae,i=false) => {ctx.beginPath();ctx.arc(x,y,r,as,ae,i);ctx.fill();};
		ctx.clearRect(0, 0, w, h);
			// fond
		ctx.fillStyle = color;
		ctx.fillRect(4*q, 4*q, w-8*q, h-8*q);
			// bords
		ctx.fillStyle = 'whitesmoke';
		//ctx.lineWidth = 4*q;
		//ctx.strokeRect(2*q, 2*q, w-4*q, h-4*q);
		ctx.fillRect(4*q, 0, w-8*q, 4*q); // top
		ctx.fillArc(w-4*q, 4*q, 4*q, 0, Math.PI, true);
		ctx.fillRect(w-4*q, 4*q, 4*q, h-8*q); // right
		ctx.fillArc(w-4*q, h-4*q, 4*q, Math.PI, 0, true);
		ctx.fillRect(4*q, h-4*q, w-8*q, 4*q); // bottom
		ctx.fillArc(4*q, h-4*q, 4*q, Math.PI, 0, true);
		ctx.fillRect(0, 4*q, 4*q, h-8*q); // left
		ctx.fillArc(4*q, 4*q, 4*q, 0, Math.PI, true);
			// image
		var img;
		let doAfterImage = () => {
			ctx.drawImage(img, 0, 0, Math.min(img.naturalWidth, img.naturalHeight*4/3), Math.min(img.naturalHeight, img.naturalWidth*3/4), 8*q, 16*q, 93*q, 69*q);
				// contour du nom
			ctx.strokeStyle = "lightgrey";
			ctx.lineWidth = 1*q;
			ctx.strokeRect(8.5*q, 85.5*q, 92*q, 15*q);
				// nom
			ctx.fillStyle = textColor;
			ctx.font = 12*q + 'px Arial';
			ctx.textAlign = 'center';
			ctx.fillText(name, w/2, 97*q);
				////// coût d'invocation / mana
			ctx.lineWidth = 1*q;
			ctx.strokeStyle = textColor2; //'whitesmoke';
			manaCrystal(ctx, 6*q, 6*q, 20*q);
				// chiffre cout en mana / invo
			ctx.fillStyle = textColor2=='black'?'whitesmoke':'black';
			ctx.font = 10*q + 'px Arial';
			ctx.strokeText(invo, 16*q, 19.5*q);
			ctx.fillText(invo, 16*q, 19.5*q);
			/*for (let i = 0; i < invo; i++) {
				/*ctx.fillRect(8*q*(i+1), 8*q, 6*q, 6*q);
				ctx.strokeRect(8*q*(i+1), 8*q, 6*q, 6*q);* /
				ctx.beginPath();
				ctx.lineTo(8*q*(i+1)+3*q, 8*q);
				ctx.lineTo(8*q*(i+1)+6*q, 11*q);
				ctx.lineTo(8*q*(i+1)+3*q, 14*q);
				ctx.lineTo(8*q*(i+1), 11*q);
				ctx.closePath();
				ctx.fill();
				ctx.stroke();
			}*/
			ctx.lineWidth = 1*q;
			if (type == 'prof') {
					// PV
				heart(ctx, w-26*q, 7*q, 20*q, 18*q);
				ctx.fillStyle = textColor2=='black'?'whitesmoke':'black';
				ctx.font = 10*q + 'px Arial';
				ctx.strokeText(hp, w-16*q, 19.5*q);
				ctx.fillText(hp, w-16*q, 19.5*q);
					// Bouclier
				if (shield) {
					drawShield(ctx, w-26*q, 28*q, 20*q);
					ctx.fillStyle = textColor2=='black'?'whitesmoke':'black';
					ctx.font = 10*q + 'px Arial';
					ctx.strokeText(shield, w-16*q, 41*q);
					ctx.fillText(shield, w-16*q, 41*q);
				}
					// Force
				if (strength) {
					drawFist(ctx, 6*q, 28*q, 20*q, 20*q);
					ctx.fillStyle = textColor2=='black'?'whitesmoke':'black';
					ctx.font = 10*q + 'px Arial';
					ctx.strokeText(strength, 16*q, 42*q);
					ctx.fillText(strength, 16*q, 42*q);
				}
					// type de prof
				ctx.fillStyle = textColor2;
				ctx.font = 6*q + 'px Arial';
				ctx.fillText(proftype=='math'?'Maths':proftype=='physik'?'Physique':proftype=='info'?'Informatique':proftype=='lang'?'Langue':proftype=='admin'?'Administration':proftype=='student'?'Élève':infos.proftype=='asso'?'Association':proftype, w/2, 14*q);
			} else {
					// type de la carte
				ctx.textAlign = 'right';
				ctx.fillStyle = textColor2;
				ctx.font = 8*q + 'px Arial';
				ctx.fillText(type=='place'?'Lieu':type=='effect'?'Effet':'Autre', w-8*q, 14*q);
			}
				// attaque 1 titre
			ctx.fillStyle = textColor2;
			ctx.textAlign = 'center';
			ctx.font = 8*q + 'px Arial';
				//ctx.textAlign = 'left';
			ctx.fillText(atk1.name, w/2, 108*q);
				// attaque 2 titre
			ctx.fillText(atk2.name, w/2, 124*q);
				// attaque 1 dégâts
			ctx.textAlign = 'right';
			ctx.fillText(atk1.dama, w-8*q, 108*q);
				// attaque 2 dégâts
			ctx.fillText(atk2.dama, w-8*q, 124*q);
				// attaque 1 description
			ctx.textAlign = 'center';
			ctx.font = 5*q + 'px Arial';
			ctx.fillText(atk1.desc, w/2, 115*q);
				// attaque 2 description
			ctx.fillText(atk2.desc, w/2, 131*q);
				// Rareté
			ctx.textAlign = 'center';
			ctx.font = 6*q + 'px Arial';
			ctx.lineWidth = .5*q;
			let setRarity = (color, text) => {
				ctx.strokeStyle = 'whitesmoke';
				ctx.strokeText(text, w/2, h-7*q);
				ctx.fillStyle = color;
				ctx.fillText(text, w/2, h-7*q);
			}
			switch (rarity) {
				case 1:
				case "1":
					setRarity('#808080', 'commune');
					break;
				case 2:
				case "2":
					setRarity('#ff7f00', 'rare');
					break;
				case 3:
				case "3":
					setRarity('#c000c0', 'épique');
					break;
				case 4:
				case "4":
					setRarity('#40e0d0', 'légendaire');
					break;
			}
				// Nom de l'illustrateur
			// a mettre ?
				//
			resolve(cvs);
		}
		if (typeof imgSrc == 'string') {
			img = new Image();
			img.src = imgSrc;
			img.onload = doAfterImage;
		} else {
			img = imgSrc;
			if (img.complete)
				doAfterImage();
			else
				img.onload = doAfterImage;
		}
	});
	return promise;
}
function drawCardBack(cvs) {
	var back = new Image();
	back.src = 'assets/back.png';
	back.onload = () => copyCard(back, cvs);
}
function copyCard(from, to) {
	to.width = 108*q;
	to.height = 144*q;
	let ctx = to.getContext('2d');
	ctx.drawImage(from, 0, 0, 108*q, 144*q);
}
function drawAddCard(cvs, color, text=undefined, e=10, black=false) {
	var q = 4;
	let w = cvs.width = 108*q;
	let h = cvs.height = 144*q;
	let ctx = cvs.getContext('2d');
	ctx.fillArc = (x,y,r,as,ae,i=false) => {ctx.beginPath();ctx.arc(x,y,r,as,ae,i);ctx.fill();};
	ctx.clearRect(0, 0, w, h);
		// fond
	ctx.fillStyle = color;
	ctx.fillRect(4*q, 4*q, w-8*q, h-8*q);
		// bords
	ctx.fillStyle = 'whitesmoke';
	ctx.fillRect(4*q, 0, w-8*q, 4*q); // top
	ctx.fillArc(w-4*q, 4*q, 4*q, 0, Math.PI, true);
	ctx.fillRect(w-4*q, 4*q, 4*q, h-8*q); // right
	ctx.fillArc(w-4*q, h-4*q, 4*q, Math.PI, 0, true);
	ctx.fillRect(4*q, h-4*q, w-8*q, 4*q); // bottom
	ctx.fillArc(4*q, h-4*q, 4*q, Math.PI, 0, true);
	ctx.fillRect(0, 4*q, 4*q, h-8*q); // left
	ctx.fillArc(4*q, 4*q, 4*q, 0, Math.PI, true);
	ctx.fillStyle = black ? 'black' : 'whitesmoke';
	ctx.fillRect(27*q, h/2-e/2*q, 54*q, e*q);
	ctx.fillRect(w/2-e/2*q, 45*q, e*q, 54*q);
	if (text) {
		ctx.font = 12*q + 'px Arial';
		ctx.textAlign = 'center';
		ctx.fillText(text, w/2, 120*q);
	}
}

function heart(ctx, x, y, w, h=w, color='red') {
	if (color) ctx.fillStyle = color;
	ctx.beginPath();
	ctx.moveTo(x, y+h/4);
	ctx.quadraticCurveTo(x, y, x+w/4, y);
	ctx.quadraticCurveTo(x+w/2, y, x+w/2, y+h/4);
	ctx.quadraticCurveTo(x+w/2, y, x+3*w/4, y);
	ctx.quadraticCurveTo(x+w, y, x+w, y+h/4);
	ctx.quadraticCurveTo(x+w, y+h/2, x+w/2, y+h);
	ctx.quadraticCurveTo(x, y+h/2, x, y+h/4);
	ctx.closePath();
	if (color) ctx.fill();
	ctx.stroke();
}
function manaCrystal(ctx, x, y, w, h=w) {
	// fond mana
	ctx.fillStyle = '#00cad6'; /*#00bbff*/
	ctx.beginPath();
	ctx.moveTo(x+w/2, y);
	ctx.lineTo(x+w, y+h/2);
	ctx.lineTo(x+w/2, y+h);
	ctx.lineTo(x, y+h/2);
	ctx.closePath();
	ctx.fill();
		// ombre mana
	ctx.fillStyle = '#00a1aa';
	ctx.beginPath();
	ctx.moveTo(x+w/2, y+h/2);
	ctx.lineTo(x+w/2, y+h);
	ctx.lineTo(x, y+h/2);
	ctx.closePath();
	ctx.fill();
		// reflet mana
	ctx.fillStyle = '#aae1e8';
	ctx.beginPath();
	ctx.moveTo(x+w/2, y+h/2);
	ctx.lineTo(x+w/2, y);
	ctx.lineTo(x+w, y+h/2);
	ctx.closePath();
	ctx.fill();
		// contour mana
	ctx.beginPath();
	ctx.moveTo(x+w/2, y);
	ctx.lineTo(x+w, y+h/2);
	ctx.lineTo(x+w/2, y+h);
	ctx.lineTo(x, y+h/2);
	ctx.closePath();
	ctx.stroke();
}
function drawShield(ctx, x, y, w, h=w) {
	ctx.beginPath();
	ctx.moveTo(x+w/2, y);
	ctx.quadraticCurveTo(x+2*w/3, y+h/6, x+w, y+h/12);
	ctx.quadraticCurveTo(x+w, y+3*h/4, x+w/2, y+h);
	ctx.fillStyle = '#ddd';
	ctx.fill();
	ctx.beginPath();
	ctx.moveTo(x+w/2, y+h);
	ctx.quadraticCurveTo(x, y+3*h/4, x, y+h/12);
	ctx.quadraticCurveTo(x+w/3, y+h/6, x+w/2, y);
	ctx.fillStyle = '#aaa';
	ctx.fill();
	ctx.beginPath();
	ctx.moveTo(x, y+h/12);
	ctx.quadraticCurveTo(x+w/3, y+h/6, x+w/2, y);
	ctx.quadraticCurveTo(x+2*w/3, y+h/6, x+w, y+h/12);
	ctx.quadraticCurveTo(x+w, y+3*h/4, x+w/2, y+h);
	ctx.quadraticCurveTo(x, y+3*h/4, x, y+h/12);
	ctx.closePath();
	ctx.stroke();
}
function drawFist(ctx, x, y, w, h=w, color='#F0D000') {
    if (color) ctx.fillStyle = color;
    ctx.beginPath();
    ctx.moveTo(x+5*w/9, y+5*h/6);
    ctx.quadraticCurveTo(x+5*w/9, y+3*h/5, x+7*w/20, y+h/2);
    ctx.moveTo(x+w/4, y+2*h/3);
    ctx.lineTo(x+7*w/20, y+h/2);
    ctx.lineTo(x+w/2, y+h/2);
    ctx.quadraticCurveTo(x+3*w/5, y+h/2, x+3*w/5, y+5*w/12);
    ctx.quadraticCurveTo(x+3*w/5, y+w/3, x+w/2, y+w/3);
    ctx.lineTo(x+3*w/10, y+w/3);
    ctx.quadraticCurveTo(x+w/5, y+w/3, x+w/9, y+w/2);
    ctx.lineTo(x+w/9, y+w/2);
    ctx.quadraticCurveTo(x, y+2*h/3, x+w/9, y+5*h/6);
    ctx.quadraticCurveTo(x+w/5, y+h, x+2*w/5, y+h);
    ctx.lineTo(x+4*w/5, y+h);
    ctx.quadraticCurveTo(x+w, y+h, x+w, y+h/2);
    ctx.lineTo(x+w, y+h/3);
    ctx.quadraticCurveTo(x+w, y+h/9, x+8*w/9, y+h/9);
    ctx.quadraticCurveTo(x+7*w/9, y+h/9, x+7*w/9, y+7*h/36);
    ctx.lineTo(x+7*w/9, y+h/3);
    ctx.lineTo(x+7*w/9, y+7*h/36);
    ctx.quadraticCurveTo(x+7*w/9, y+h/36, x+2*w/3, y+h/36);
    ctx.quadraticCurveTo(x+5*w/9, y+h/36, x+5*w/9, y+h/6);
    ctx.lineTo(x+5*w/9, y+h/3);
    ctx.lineTo(x+5*w/9, y+h/6);
    ctx.quadraticCurveTo(x+5*w/9, y, x+4*w/9, y);
    ctx.quadraticCurveTo(x+w/3, y, x+w/3, y+h/6);
    ctx.lineTo(x+w/3, y+h/3);
    ctx.lineTo(x+w/3, y+h/6);
    ctx.quadraticCurveTo(x+w/3, y+h/18, x+2*w/9, y+h/18);
    ctx.quadraticCurveTo(x+w/9, y+h/18, x+w/9, y+5*h/18);
    ctx.lineTo(x+w/9, y+h/2);
    ctx.fill();
    ctx.stroke();
}

/* calcul de la luminance d'une couleur */
function calcLuminance(hex) { // source : https://stackoverflow.com/questions/1754211/evaluate-whether-a-hex-value-is-dark-or-light
	let rgb = parseInt('0x'+hex.replace('#', ''));
	let r = (rgb & 0xff0000) >> 16;
	let g = (rgb & 0xff00) >> 8;
	let b = (rgb & 0xff);
	return (r*0.299 + g*0.587 + b*0.114) / 256;
}

/* Afichage d'un texte sur l'écran */
function aff(text, t=4000, where=document.body) {
	let el = createElement("span", {style:{position:"absolute",left:"50%",top:"50%",fontSize:"3em",opacity:1,transform:"translate(-50%,-50%)"}}, text);
	where.appendChild(el);
	el.style.transition = 'top '+t/1000+'s ease-out, opacity '+t/2000+'s ease';
	setTimeout(()=>el.style.top = '40%', 20);
	setTimeout(()=>el.style.opacity = '0', t/2);
	setTimeout(()=>where.removeChild(el), t);
	return el;
}

/* affichage des infos d'une carte */
function removeCardAbout() {
    var about = document.getElementById('card-about');
    if (about) about.parentElement.removeChild(about);
}
async function printCardAbout(cardId) {
    removeCardAbout();
    var about = document.createElement('div');
    about.id = 'card-about';
    about.onclick = () => about.parentElement.removeChild(about);
    document.body.appendChild(about);
    var card = document.createElement('div');
    about.appendChild(card);
    setCardElementById(card, cardId);
    var infos = document.createElement('div');
    infos.id = 'card-about-infos';
    about.appendChild(infos);
    var cardInfos = await getCardInfos(cardId);
    var title = document.createElement('span');
    title.className = 'title';
    title.textContent = cardInfos.name;
    infos.appendChild(title);
    for (let info of ['origin','desc','use','date','booster']) {
        if (!Object.keys(cardInfos).includes(info)) continue;
        let text = document.createElement('span');
        text.className = 'text';
        if (info=="origin"&&(/^https?:\/\/[-a-zA-Z0-9]{1,256}\.[a-zA-Z0-9]+(\S*)$/).test(cardInfos[info]))
            text.innerHTML = 'Origine : <a href="'+cardInfos[info]+'" target="_blank">cliquez ici</a>'
        else text.textContent = (info=='origin'?'Origine':info=='desc'?'Description':info=='use'?'Usage':info=='booster'?'Booster':info=='date'?'Date de création':info)+' : '
+cardInfos[info]; //Modif de Léo
        infos.appendChild(text);
    }
    var br = document.createElement('br');
    var shareButton = document.createElement('span');
    shareButton.className = 'button';
    shareButton.innerText = 'Partager';
    shareButton.onclick = function(e) {
        e.stopPropagation();
        let url = window.location.origin+window.location.pathname.split(/[^\/]*$/)[0]+"gallery.php#"+cardId;
        if (navigator.share)
            return navigator.share({url:url});
        if (navigator.clipboard)
            navigator.clipboard.writeText(url);
        else {
            let input = document.createElement("input");
            input.value = url;
            document.body.appendChild(input);
            input.focus();
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        }
        setTimeout(() => alert("L'url a bien été copiée !"), 100);
    };
    infos.appendChild(br);
    infos.appendChild(shareButton);
}

/* affichage d'une explication de tuto */
var resolveTuto = undefined;
function displayTuto(message, data={}) { // message, {button=undefined, show=[], showlock=[], speaker="abo", left=false, mini=false}
    if (!data.show) data.show = [];
    if (!data.showlock) data.showlock = [];
    return new Promise(function(resolve, reject) {
        var tutorialDiv;
        (new Promise(function(resolve, reject) { 
            tutorialDiv = document.getElementById("tutorial");
            if (tutorialDiv) {
                tutorialDiv.parentElement.removeChild(tutorialDiv);
            }
            document.body.appendChild(tutorialDiv = createElement("div", {id:"tutorial", className:"tutorial"+(data.left?" left":"")}, [
                createElement("img", {src:"assets/tuto-"+(data.speaker||"abo")+".png",className:"speaker"}),
                message?createElement("div", {className:"bubble"}, [
                    createElement("span", {className:"message"}, message),
                    data.button?createElement("span", {className:"button"}, data.button, {click:function(){resolve();}}):""
                ]):""
            ]));
            for (let el of data.show)
                el.classList.add("show");
            for (let el of data.showlock)
                el.classList.add("showlock");
            resolveTuto = resolve;
        })).then(function() {
            resolveTuto = undefined;
            tutorialDiv.parentElement.removeChild(tutorialDiv);
            for (let el of data.show)
                el.classList.remove("show");
            for (let el of data.showlock)
                el.classList.remove("showlock");
            resolve();
        });
    });
}

/* Création, actualisation et placement des cartes absolues (.placed-card) */
function createPlacedCard(cardId, data={}) { // id, {x, y, edit, dataset, noflip, targetPos, parent, onclick, classList}
	let div = document.createElement("div");
	div.className = "placed-card";
	for (let classs of (data.classList||[]))
	    div.classList.add(classs);
	(data.parent||document.body).appendChild(div);
	if (data.onclick) div.addEventListener("click", data.onclick);
    data.id = cardId;
    data.noflip = true;
	updatePlacedCard(div, data);
	return div;
}

function updatePlacedCard(div, data={}) { // div, {x, y, id, edit, dataset, noflip, targetPos}
    data.edit = data.edit || {};
	if (data.id!==undefined) {
	    if (data.id!=div.dataset.id) {
	        if (data.noflip) {
    	        setCardElementById(div, data.id, data.edit).then(function() {
    	            updateCardEffects(div, data.edit);
    	        });
    		    div.dataset.id = data.id;
    	    } else {
    		    div.classList.add("flip");
    		    setTimeout(function() {
    				setCardElementById(div, data.id, data.edit).then(function() {
    		            updateCardEffects(div, data.edit);
    		        });
    			    div.dataset.id = data.id;
    		        div.classList.remove("flip");
    		    }, 500);
    	    }
    	} else {
            setCardElementById(div, data.id, data.edit).then(function() {
                updateCardEffects(div, data.edit);
            });
    	}
	}
	div.classList[data.edit.eg||data.edit.mi?"add":"remove"]("engaged");
	if (data.x!==undefined) div.style.left = data.x + "%";
	if (data.y!==undefined) div.style.top = data.y + "%";
	if (data.targetPos) {
	    let rect = data.targetPos.getBoundingClientRect();
	    div.style.left = rect.left + rect.width/2 + "px";
	    div.style.top = rect.top + rect.height/2 + "px";
	}
	for (let key in data.dataset)
	    if (div.dataset[key] != data.dataset[key])
	        div.dataset[key] = data.dataset[key];
}

function updateCardEffects(cardDiv, card) {
    let div = cardDiv.getElementsByClassName("particles")[0];
    if (!div) cardDiv.appendChild(div = createElement("div", {className:"particles"}));
	let effects = {slp:"💤", elc:"⚡", efr:"😱", prl:"🚫"/*, eg:{text:'⏳'}*/};
	let n = Object.keys(effects).filter(effect=>card[effect]>0).length;
	let i = 0;
	for (let effect of Object.keys(effects)) {
	    if (card[effect]>0) {
			setTimeout(function() {
				for (let j = 0; j < 3; j++) {
			        let particle = div.getElementsByClassName(effect+j)[0];
					if (!particle) {
						particle = document.createElement("span");
						particle.className = effect+j;
						particle.innerText = effects[effect];
						div.appendChild(particle);
					}
			    }
			}, 2000*i/n);
		} else {
		    let particle;
			for (let j = 0; (particle = div.getElementsByClassName(effect+j)[0])!=null; j++)
			    div.removeChild(particle);
		}
		if (card[effect]) i++;
	}
}


// Chargement à appeler pour afficher un logo de chargement ou le retirer
function displayLoading(promise=undefined) {
    if (!document.getElementById("loading"))
        document.body.appendChild(createElement("img", {id:"loading", src:"assets/arel.svg"}));
    if (promise)
        promise.finally(removeLoading).catch(console.error);
    return promise;
}
function removeLoading() {
    let loading = document.getElementById("loading")
    if (loading) {
        loading.id = "loading-removing";
        loading.style.opacity = 0;
        setTimeout(function(){
            loading.parentElement.removeChild(loading);
        }, 500);
    }
}


// fonction utilisée dans le popup des règles
function extendsNext(el) {
    el.classList[el.classList.contains("extended")?"remove":"add"]("extended");
}


// fonction pour initialisé un sélect à choix multiple
function initMultiSelect(multiselect, name) {
    multiselect.classList.add("multiselect");
    var input = document.createElement("input");
    input.value = "";
    if (name) input.name = name;
    input.style.display = "none";
    multiselect.appendChild(input);
    (multiselect).addEventListener("mouseover", function () {
        for (let menu of multiselect.getElementsByClassName("menu")) menu.classList.remove("hide");
    });
    (multiselect).addEventListener("mouseout", function () {
        for (let menu of multiselect.getElementsByClassName("menu")) menu.classList.add("hide");
    });
    input.addEventListener("change", function(){
        setMultiSelectValue(multiselect, input.value.split(',').filter(String));
    });
    for (let menu of multiselect.getElementsByClassName("menu")) {
        menu.classList.add("hide");
        for (let option of menu.getElementsByClassName("option")) {
            option.addEventListener("click", function () {
                let value = input.value.split(",").filter(String);
                value.push(option.dataset.value);
                input.value = value.join(",");
                option.style.display = "none";
                let selectedOption = document.createElement("span");
                selectedOption.className = "selected-option";
                selectedOption.appendChild(document.createTextNode(option.innerText));
                selectedOption.addEventListener("click", function () {
                    let value = input.value.split(",").filter(String);
                    value.splice(value.indexOf(selectedOption.dataset.value), 1);
                    input.value = value.join(",");
                    for (let o of menu.children)
                        if (o.dataset.value == selectedOption.dataset.value) o.style.display = "";
                    multiselect.removeChild(selectedOption);
                    if (multiselect.dispatchEvent) multiselect.dispatchEvent(new InputEvent("change", {inputType:"multiselect",data:input.value.split(',').filter(String)}))
                });
                selectedOption.dataset.value = option.dataset.value;
                multiselect.insertBefore(selectedOption, multiselect.firstChild);
                menu.classList.add("hide");
                if (multiselect.dispatchEvent) multiselect.dispatchEvent(new InputEvent("change", {inputType:"multiselect",data:input.value.split(",").filter(String)}))
            });
        }
    }
}
function refreshMultiSelect(multiselect) {
    var input = multiselect.getElementsByTagName("input")[0];
    value = input.value.split(",").filter(String);
    input.value = "";
    for (let selectedOption of Array.from(multiselect.getElementsByClassName("selected-option"))) {
        selectedOption.parentElement.removeChild(selectedOption);
    }
    for (let option of multiselect.getElementsByClassName("option")) {
        option.style.display = "";
        if (value.includes(option.dataset.value))
            option.click();
    }
}
function setMultiSelectValue(multiselect, value) {
    multiselect.getElementsByTagName("input")[0].value = typeof value!="string" ? value.join(",") : value;
    refreshMultiSelect(multiselect);
}
function getMultiSelectValue(multiselect) {
    return multiselect.getElementsByTagName("input")[0].value.split(",").filter(String);
}