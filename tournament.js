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
