<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<script src="cards.js"></script>
		<script src="tournament.js"></script>
		<link rel="stylesheet" href="tournament.css" />
		<link rel="icon" href="assets/icon.png" />
	</head>
	<body>
		<div id="tournament"></div>
		<script>
			var params = new URLSearchParams(window.location.search);
			queryTournament(params.get("id")).then(function(tournament) {
				displayTournament(document.getElementById("tournament"), tournament.fighters);
			}).catch(function(){
				alert("Impossible d'afficher ce tournoi");
			});
		</script>
	</body>
</html>
