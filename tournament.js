function displayTournament(tournamentDiv, tournament) {
	tournamentDiv.innerHTML = "";
	tournamentDiv.classList.add("tournament");
	for (let bracket of tournament.split(";")) {
		var turns = bracket.split(",");
		for (let t = 0; t < turns.length; t++) {
			let turn = turns[t];
			let turnDiv = createElement("div", {className:"tournament-turn"});
			tournamentDiv.appendChild(turnDiv);
			var fighters = turn.split(".");
			var fightersNumber = Math.pow(2,turns.length-t-1);
			for (let i = 0; i < fightersNumber; i+=2) {
				if (i >= fighters.length || !fighters[i])
					turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
						createElement("div", {className:"tournament-fighters"})
					]));
				else if (fighters[i+1])
					turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
						createElement("div", {className:"tournament-fighters"}, [
							createElement("div",{className:"tournament-fighter-space"}),
							createElement("span",{className:"tournament-fighter"},fighters[i]),
							createElement("div",{className:"tournament-fighter-interspace"}),
							createElement("span",{className:"tournament-fighter"},fighters[i+1])
						]),
						createElement("div", {className:"tournament-lines"}),
						createElement("div", {className:"tournament-line"})
					]));
				else if (turn==turns[turns.length-1])
					turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
						createElement("div", {className:"tournament-fighters"}, [
							createElement("span",{className:"tournament-alone-fighter"},fighters[i])
						])
					]));
				else
					turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
						createElement("div", {className:"tournament-fighters"}, [
							createElement("span",{className:"tournament-alone-fighter"},fighters[i])
						]),
						createElement("div", {className:"tournament-line"}),
						createElement("div", {className:"tournament-line"})
					]));
			}	
		}
	}
}

function displayTournament2(tournamentDiv, tournament, names) {
	if (names!=null) {
		tournamentDiv.innerHTML = "";
		tournamentDiv.classList.add("tournament");
		let nTree=0;
		for (let bracket of tournament.split(";")) {
			var turns = bracket.split(",");
			for (let t = 0; t < turns.length; t++) {
				let turn = turns[t];
				let turnDiv = createElement("div", {className:"tournament-turn"});
				tournamentDiv.appendChild(turnDiv);
				var fighters = turn.split(".");
				var fightersNumber = Math.pow(2,turns.length-t-1);
				for (let i = 0; i < fightersNumber; i+=2) {
					if (i >= fighters.length || !fighters[i])
						turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
							createElement("div", {className:"tournament-fighters"})
						]));
					else if (fighters[i+1])
						turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
							createElement("div", {className:"tournament-fighters"}, [
								createElement("div",{className:"tournament-fighter-space"}),
								createElement("span",{className:"tournament-fighter"},names[fighters[i]]),
								createElement("div",{className:"tournament-fighter-interspace"}),
								createElement("span",{className:"tournament-fighter"},names[fighters[i+1]])
							]),
							createElement("div", {className:"tournament-lines"}),
							createElement("div", {className:"tournament-line"})
						]));
					else if (turn==turns[turns.length-1])
						turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
							createElement("div", {className:"tournament-fighters"}, [
								createElement("span",{className:"tournament-alone-fighter"},names[fighters[i]])
							])
						]));
					else
						turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
							createElement("div", {className:"tournament-fighters"}, [
								createElement("span",{className:"tournament-alone-fighter"},names[fighters[i]])
							]),
							createElement("div", {className:"tournament-line"}),
							createElement("div", {className:"tournament-line"})
						]));
				}	
			}
			nTree++;
		}
	}
}

function displayTournamentAdmin(tournamentDiv, tournament, names) {
	if (names!=null) {
		tournamentDiv.innerHTML = "";
		tournamentDiv.classList.add("tournament");
		let nTree=0;
		for (let bracket of tournament.split(";")) {
			var turns = bracket.split(",");
			for (let t = 0; t < turns.length; t++) {
				let turn = turns[t];
				let turnDiv = createElement("div", {className:"tournament-turn"});
				tournamentDiv.appendChild(turnDiv);
				var fighters = turn.split(".");
				var fightersNumber = Math.pow(2,turns.length-t-1);
				for (let i = 0; i < fightersNumber; i+=2) {
					if (i >= fighters.length || !fighters[i])
						turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
							createElement("div", {className:"tournament-fighters"})
						]));
					else if (fighters[i+1])
						turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
							createElement("div", {className:"tournament-fighters"}, [
								createElement("div",{className:"tournament-fighter-space"}),
								createElement("span",{className:"tournament-fighter"},[PutAPlayer (tournament, names, fighters[i], nTree, t, i, "")]),
								createElement("div",{className:"tournament-fighter-interspace"}),
								createElement("span",{className:"tournament-fighter"}, [PutAPlayer (tournament, names, fighters[i+1], nTree, t, i+1, "")])
							]),
							createElement("div", {className:"tournament-lines"}),
							createElement("div", {className:"tournament-line"})
						]));
					else if (turn==turns[turns.length-1]) {
						turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
							createElement("div", {className:"tournament-fighters"}, [
								createElement("span",{className:"tournament-alone-fighter"}, [PutAPlayer (tournament, names, fighters[i], nTree, t, i, "")])
							])
						]));
					} else {
						turnDiv.appendChild(createElement("div", {className:"tournament-match"}, [
							createElement("div", {className:"tournament-fighters"}, [
								createElement("span",{className:"tournament-alone-fighter"}, [PutAPlayer (tournament, names, fighters[i], nTree, t, i, "")])
							]),
							createElement("div", {className:"tournament-line"}),
							createElement("div", {className:"tournament-line"}),
						]));
					}
				}	
			}
			nTree++;
		}
	}
}

function displayTournament3(tournamentDiv, tournament, names, statu) {
	if (document.getElementById("actionTournament")&&(statu!=null)&&(statu==0)) {
		displayTournamentAdmin(tournamentDiv, tournament, names);
		//displayTournament2(tournamentDiv, tournament, names);
	} else {
		displayTournament2(tournamentDiv, tournament, names);
	}
}

function queryTournament(id) {
	return new Promise(function (resolve, reject){
		sendRequest("GET", "gettournament.php?id="+id).then(function(response){
			try {
				resolve(JSON.parse(response));
			} catch (e) {
				reject(e);
			}
		}).catch(reject);
	});
}

function affPlacesNButtons(nbPlaces) {
	if (nbPlaces!=null) {
		if (nbPlaces>0)
			document.getElementById("tournament-infos").innerText = nbPlaces+" places";
		else if (nbPlaces==0) {
			document.getElementById("tournament-infos").innerText = "Les inscriptions sont fermées!";
			document.getElementById("join-button").style.display="none";
		} else {
			document.getElementById("tournament-infos").innerText = "Ce tournoi est terminé.";
			document.getElementById("join-button").style.display="none";
		}
	}
}

function affGraphTournament(tournamentId, action="") {
	if (action=="join") {
		sendRequest("POST", "jointournament.php", "id="+tournamentId+"&add=1").then(function(response) {
			if (response=="not logged") {
				window.location.replace("connect.php?go="+encodeURIComponent(window.location.pathname)+encodeURIComponent(window.location.search));
			} else if (response=="already in tournament") {
				alert("Vous êtes déjà inscrit!");
				location.reload();
			} else if (response.includes("invalid")) {
				alert(response);
				window.location.href='.';
			} else {
				var newTab=JSON.parse(response);
				displayTournament3(document.getElementById("tournament"), newTab.fighters, newTab.names, newTab.nbPlaces);
				document.getElementById("join-button").style.display="none";
				document.getElementById("leave-button").style.display="inline-block";
				adminBord2(newTab);
				affPlacesNButtons(newTab.nbPlaces);
			}
		});
	} else if (action=="leave") {
		sendRequest("POST", "jointournament.php", "id="+tournamentId+"&del=1").then(function(response) {
			if (response=="not logged") {
				window.location.replace("connect.php?go="+encodeURIComponent(window.location.pathname)+encodeURIComponent(window.location.search));
			} else if (response=="not in tournament") {
				alert("Vous n'êtes pas encore inscrit!");
				location.reload();
			} else if (response.includes("invalid")) {
				alert(response);
				window.location.href='.';
			} else {
				var newTab=JSON.parse(response);
				displayTournament3(document.getElementById("tournament"), newTab.fighters, newTab.names, newTab.nbPlaces);
				document.getElementById("leave-button").style.display="none";
				document.getElementById("join-button").style.display="inline-block";
				adminBord2(newTab);
				affPlacesNButtons(newTab.nbPlaces);
			}
		});
	} else {
		queryTournament(tournamentId).then(function(tournament) {
			affPlacesNButtons(tournament.nbPlaces);
			adminBord2(tournament);
			document.getElementById("tournament-name").innerText = tournament.name;
			if ((typeof tournament)=="string") {
				if (tournament=="not logged") {
					displayTournament3(document.getElementById("tournament"), tournament.fighters, tournament.names, tournament.nbPlaces);
					document.getElementById("join-button").style.display="inline-block";
					document.getElementById("leave-button").style.display="none";
				} else if (response=="ended tournament") {
					displayTournament3(document.getElementById("tournament"), tournament.fighters, tournament.names, tournament.nbPlaces);
					document.getElementById("join-button").style.display="none";
					document.getElementById("leave-button").style.display="none";
				} else {
					alert(tournament);
					window.location.href='.';
				}
			} else if ((typeof tournament)=="object") {
				if ((tournament.include==true)) {
					displayTournament3(document.getElementById("tournament"), tournament.fighters, tournament.names, tournament.nbPlaces);
					document.getElementById("join-button").style.display="none";
					if ((tournament.nbPlaces!=null)&&(tournament.nbPlaces>0)) {
						document.getElementById("leave-button").style.display="inline-block";
					} else {
						document.getElementById("leave-button").style.display="none";
					}
				} else {
					displayTournament3(document.getElementById("tournament"), tournament.fighters, tournament.names, tournament.nbPlaces);
					document.getElementById("leave-button").style.display="none";
					document.getElementById("join-button").style.display="inline-block";
					adminBord2(tournament);
					affPlacesNButtons(tournament.nbPlaces);
				}
			} else {
				alert("Une données n'a pas le bon type! Ouvre vite la console de la page!");
				console.log(tournament);
			}
		});
		/*.catch(function(){
			//alert("Impossible d'afficher ce tournoi");
			//window.location.href='.';
		});*/
	}
}

function adminBord (tournamentId) {
	let tournamentAction=document.getElementById("actionTournament");
	let tournamentPlayers=document.getElementById("players");
	if ((tournamentAction)&&(tournamentPlayers)) {
		queryTournament(tournamentId).then(function(tournament) {
			let tabPlayers=document.getElementById("list-players");
			tabPlayers.innerHTML="";
			for (let index of Object.keys(tournament.names)) {
				if ((index!=' ')&&(index!='')&&(index!="_")&&(index!="-")) {
					let playerLine = createElement("tr", {className:"tournament-players"}, [
						createElement("th", {className:"tournament-players-name"}, tournament.names[index])
					]);
					if ((tournament.nbPlaces!=null)&&(tournament.nbPlaces>=0)) {
						playerLine.appendChild(
							createElement("td", {className: "button"}, "Désinscrire")
						).onclick= function() {
							sendRequest("POST", "jointournament.php", "id="+tournamentId+"&player="+index);
							adminBord(tournamentId);
							affGraphTournament(tournamentId);
						}
					}
					tabPlayers.appendChild(playerLine);
				}
			}
			//tournamentPlayers.appendChild(tabPlayers);
			tournamentAction.innerHTML="Démarrer tournoi, modifier nombre de places, supprimer tournoi, cloturer tournoi";
		}).catch(function(){
			alert("Impossible d'afficher le tableau de bord");
		});
	}
}

function adminBord2 (tournament) {
	let tournamentAction=document.getElementById("actionTournament");
	let tournamentPlayers=document.getElementById("tournament-players");
	if ((tournamentAction)&&(tournamentPlayers)) {
		let tabPlayers=document.getElementById("list-players");
		tabPlayers.innerHTML="";
		var opponents=0;
		for (let index of Object.keys(tournament.names)) {
			if ((index!=' ')&&(index!='')&&(index!="_")&&(index!="-")) {
				let playerLine = createElement("tr", {className:"tournament-players"}, [
					createElement("th", {className:"tournament-players-name"}, tournament.names[index])
				]);
				if ((tournament.nbPlaces==null)||(tournament.nbPlaces>=0)) {
					playerLine.appendChild(
						createElement("td", {className: "button"}, "Désinscrire")
					).onclick= function() {
						sendRequest("POST", "jointournament.php", "id="+tournamentId+"&player="+index);
						adminBord(tournamentId);
						affGraphTournament(tournamentId);
					};
				}
				tabPlayers.appendChild(playerLine);
				opponents=opponents+1;
			}
		}
		//tournamentPlayers.appendChild(tabPlayers);
		//tournamentAction.innerHTML="Démarrer tournoi, modifier nombre de places, supprimer tournoi, cloturer tournoi <br /><br />Etat du tournoi: ";
	}
	let selectStatus=document.getElementById("tournament-status");
	let boxLimit=document.getElementById("tournament-limit");
	let boxDrafts=document.getElementById("tournament-draft");
	if ((selectStatus)&&(boxLimit)&&(boxDrafts)) {
		let places=document.getElementById("tournament-places");
		places.value=Object.keys(tournament.names).length-4;
		if (tournament.nbPlaces<0) {
			selectStatus.value="ended";
			boxLimit.parentNode.style.display="none";
			boxDrafts.parentNode.style.display="none";
		} else {
			let drafts=document.getElementById("tournament-drafts-number");
			let typeDraft=document.getElementById("tournament-drafts-type");
			drafts.value=tournament.draft;
			if (opponents-2<0) {
				opponents=0;
			} else {
				opponents-=2;
			}
			if (tournament.draft==null) {
				typeDraft.value="all";
				drafts.value=opponents;
				drafts.min=opponents;
				drafts.max=opponents;
			} else if (tournament.draft==-2) {
				typeDraft.value="half";
				drafts.min=2;
				drafts.max=2;
				drafts.value=2;
			} else if (tournament.draft==-4) {
				typeDraft.value="quarter";
				if (opponents>=6) {
					drafts.min=6;
					drafts.max=6;
					drafts.value=6;
				} else {
					drafts.value=opponents;
					drafts.min=opponents;
					drafts.max=opponents;
				}
			} else if (tournament.draft==-8) {
				typeDraft.value="eight";
				if (opponents>=14) {
					drafts.value=14;
					drafts.min=14;
					drafts.max=14;
				} else {
					drafts.value=opponents;
					drafts.min=opponents;
					drafts.max=opponents;
				}
			} else if (tournament.draft>0) {
				typeDraft.value="manual";
				drafts.min=2;
				drafts.max=opponents;
			} else {
				drafts.value=0;
				typeDraft.value="manual";
				drafts.min=0;
				drafts.max=opponents;
			}
			if (drafts.value!=0) {
				boxDrafts.checked=true;
			} else {
				boxDrafts.checked=false;
			}
			if (tournament.nbPlaces==0) {
				selectStatus.value="started";
				boxLimit.parentNode.style.display="none";
			} else if (tournament.nbPlaces==null) {
				selectStatus.value="inscriptions";
				boxLimit.checked=false;
			} else {
				selectStatus.value="inscriptions";
				boxLimit.checked=true;
				places.value=tournament.nbPlaces;
			}
		}
	}
}

function PutAPlayer (tournament, names, val, tree, turn, place, classSelect) {
	var list=createElement("select");
	if (classSelect!="") {
		list.className=classSelect;
	}
	for (let idName in names) {
		if ((idName!='')&&(idName!='-')&&(idName!='_')) {
			list.appendChild(createElement("option", {value: idName}, names[idName])).onclick=function() {
				//alert("Ajout de "+names[idName]+" au repêchages");
				sendRequest("POST", "modiftournament.php", "idTournament="+tournamentId+"&player="+idName+"&tree="+tree+"&turn="+turn+"&place="+place);
			};
		}
	}
	list.value=val;
	//for (let span of document.getElementsByTagName("span").getElementsByClassName("tournament-fighter")) {
	//	span.appendChild(list);
	//}
	return(list);
	//return(createElement("span",{className:"tournament-fighter"},"allo"));
}
