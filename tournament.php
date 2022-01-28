<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<script src="cards.js"></script>
		<script src="tournament.js"></script>
		<link rel="stylesheet" href="style.css" />
		<link rel="stylesheet" href="tournament.css" />
		<link rel="icon" href="assets/icon.png" />
	</head>
	<body>
		<span id="tournament-name" class="title"></span>
		<br />
		<span id="tournament-infos"></span>
		<div id="actions">
			<button id="join-button" class="button">Rejoindre</button>
			<a href="." class="button">Revenir au menu</a>
		</div>
		<div id="tournament"></div>
		<script>
			var params = new URLSearchParams(window.location.search);
			queryTournament(params.get("id")).then(function(tournament) {
				displayTournament(document.getElementById("tournament"), tournament.fighters);
				document.getElementById("tournament-name").innerText = tournament.name;
				if (tournament.nbPlaces!=null) {
					if (tournament.nbPlaces>0)
						document.getElementById("tournament-infos").innerText = tournament.nbPlaces+" places";
					else if (tournament.nbPlaces==0)
						document.getElementById("tournament-infos").innerText = "Les inscriptions sont fermées!";
					else
						document.getElementById("tournament-infos").innerText = "Ce tournoi est terminé.";
				}
			}).catch(function(){
				alert("Impossible d'afficher ce tournoi");
			});
			document.getElementById("join-button").addEventListener("click", function(e) {
				sendRequest("POST", "jointournament.php").then(function(response) {
					if (response=="not logged")
						window.location.replace("connect.php?go="+encodeURIComponent(window.location.pathname));
					else alert('TODO');
				});
			});
		</script>
	</body>
</html>
