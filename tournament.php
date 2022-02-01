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
		<?php
		
		include("init.php");
		login(true,false);
		
		?>
		
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
			var tournamentId=params.get("id");
			queryTournament(tournamentId).then(function(tournament) {
				displayTournament(document.getElementById("tournament"), tournament.fighters);
				document.getElementById("tournament-name").innerText = tournament.name;
				sendRequest("POST", "jointournament.php", "id="+tournamentId).then(function(response) {
					if (response=="already in tournament") {
						document.getElementById("join-button").style.display="none";
					}
				});
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
			document.getElementById("join-button").addEventListener("click", function(e) {
				sendRequest("POST", "jointournament.php", "id="+tournamentId).then(function(response) {
					if (response=="not logged") {
						window.location.replace("connect.php?go="+encodeURIComponent(window.location.pathname));
					} else if (response=="already in tournament") {
						alert("Vous êtes déjà inscrit!");
						document.getElementById("join-button").style.display="none";
					} else {
						var newTab=JSON.parse(response);
						displayTournament(document.getElementById("tournament"), newTab.fighters);
					}
				});
			});
		</script>
	</body>
</html>
