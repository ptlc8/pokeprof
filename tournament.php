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
		$user = login(true,false);
		
		?>
		
		<span id="tournament-name" class="title"></span>
		<br />
		<span id="tournament-infos"></span>
		<div id="actions">
			
			<button id="join-button" class="button">Rejoindre</button>
			
			<button id="leave-button" class="button">Se désinscrire</button>
			
			<a href='.' ><button class="button">Revenir au menu</button></a>
		</div>
		<div id="tournament"></div>
        
        <?php
        if (in_array($user["id"], array("0", "19", "59", "-72", "73"))) {
        ?>
        <div id="managementBord">
            <h3>Administration du tournoi</h3>
            
            <div id="actionTournament">
                Démarrer tournoi, modifier nombre de places, supprimer tournoi, cloturer tournoi
            </div>
            <div id="players">A remplir avec du js (action sur les joueurs: suppr, si tournoi pas démarré)</div>
                
        </div>
        
        <?php
        }
        ?>
        
		<script>
			var params = new URLSearchParams(window.location.search);
			var tournamentId=params.get("id");
            affGraphTournament(tournamentId);
			document.getElementById("join-button").addEventListener("click", function(e) {
				affGraphTournament(tournamentId, "join");
			});
			document.getElementById("leave-button").addEventListener("click", function(e) {
				affGraphTournament(tournamentId, "leave");
			});
		</script>
	</body>
</html>
