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
		}
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

function affGraphTournament(tournamentId, action="") {
	queryTournament(tournamentId).then(function(tournament) {
		adminBord2(tournament);
		document.getElementById("tournament-name").innerText = tournament.name;
		if (action=="join") {
			sendRequest("POST", "jointournament.php", "id="+tournamentId+"&add=1").then(function(response) {
				if (response=="not logged") {
					window.location.replace("connect.php?go="+encodeURIComponent(window.location.pathname));
				} else if (response=="already in tournament") {
					alert("Vous êtes déjà inscrit!");
					location.reload();
				} else {
					var newTab=JSON.parse(response);
					displayTournament2(document.getElementById("tournament"), newTab.fighters, newTab.names);
					document.getElementById("join-button").style.display="none";
					document.getElementById("leave-button").style.display="inline-block";
					adminBord2(newTab);
				}
			});
			tournament.nbPlaces--;
		} else if (action=="leave") {
			sendRequest("POST", "jointournament.php", "id="+tournamentId+"&del=1").then(function(response) {
				if (response=="not logged") {
					window.location.replace("connect.php?go="+encodeURIComponent(window.location.pathname));
				} else if (response=="not in tournament") {
					alert("Vous n'êtes pas encore inscrit!");
					location.reload();
				} else {
					var newTab=JSON.parse(response);
					displayTournament2(document.getElementById("tournament"), newTab.fighters, newTab.names);
					document.getElementById("leave-button").style.display="none";
					document.getElementById("join-button").style.display="inline-block";
					adminBord2(newTab);
				}
			});
			tournament.nbPlaces++;
		} else {
			sendRequest("POST", "jointournament.php", "id="+tournamentId).then(function(response) {
				if ((response=="already in tournament")) {
					displayTournament2(document.getElementById("tournament"), tournament.fighters, tournament.names);
					document.getElementById("join-button").style.display="none";
					document.getElementById("leave-button").style.display="inline-block";
				} else {
					var newTab=JSON.parse(response);
					displayTournament2(document.getElementById("tournament"), newTab.fighters, newTab.names);
					document.getElementById("leave-button").style.display="none";
					document.getElementById("join-button").style.display="inline-block";
					adminBord2(newTab);
				}
			});
		}
		if (tournament.nbPlaces!=null) {
			if (tournament.nbPlaces>0)
				document.getElementById("tournament-infos").innerText = tournament.nbPlaces+" places";
			else if (tournament.nbPlaces==0) {
				document.getElementById("tournament-infos").innerText = "Les inscriptions sont fermées!";
				document.getElementById("join-button").style.display="none";
			} else {
				document.getElementById("tournament-infos").innerText = "Ce tournoi est terminé.";
				document.getElementById("join-button").style.display="none";
			}
		}
	}).catch(function(){
		alert("Impossible d'afficher ce tournoi");
		window.location.href='.';
	});
}

function adminBord (tournamentId) {
	let tournamentAction=document.getElementById("actionTournament");
	let tournamentPlayers=document.getElementById("players");
	if ((tournamentAction)&&(tournamentPlayers)) {
		queryTournament(tournamentId).then(function(tournament) {
			let tabPlayers=document.getElementById("list-players");
			tabPlayers.innerHTML="";
			for (let index of Object.keys(tournament.names)) {
				if ((index!=' ')&&(index!='')&&(index!="_")) {
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
			for (let index of Object.keys(tournament.names)) {
				if ((index!=' ')&&(index!='')&&(index!="_")) {
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
	}
}
