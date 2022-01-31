<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>Recherche d'adversaire | PokéProf</title>
		<link rel="stylesheet" href="style.css" />
		<link rel="icon" type="image/png" href="assets/icon.png" />
		<style>/* style manquant de l'ancien css */
		#deck, #play-button, #free-card {
        	position: absolute;
        	top: 50%;
        	transform: translateY(-50%);
        	border-radius: .1em;
        	text-decoration: none;
	        color: black;
        }
        #play-button {
        	left: 50%;
        	transform: translate(-50%, -50%);
        	width: 20%;
        	padding: .5em 0;
        	font-size: 2.5em;
        	background-color: yellow;
        	box-shadow: rgba(0,0,0,.2) 3px 5px 5px;
        }
		</style>
	</head>
	<body>
		<?php
		include('api/init.php');
		include('initconnect.php');
		
		?>
		<span>Recherche d'adversaire...</span>
		<br />
		<span class="loading"><!--⬤-->🔍</span>
		<a href="javascript:search(true)"><div id="play-button">
			<span>Affronter l'ordinateur</span>
		</div></a>
		<a href="index.php"><div class="mainbutton"  style="top:70%;">
			<span>Retourner au menu</span>
		</div></a> <!--Modif de Léo-->
		<script>
			window.onload = () => {
			    search();
				setInterval(search, 3000);
			}
			function search(bot=false) {
				post('api/match/oldsearch.php', bot?'bot':'', response => {
					if (response=='founded' || response=='playing') {
						window.location.replace('play.php');
					} else if (response=='nodeck') {
					    alert('Aïe ! Tu n\'as pas encore de deck, créez-en un dans le menu principal en cliquant sur "Ton jeu de cartes". Si tu n\'as pas encore de cartes, ouvre les boosters gratuits sur la page principale en cliquant dessus. Bon amusement ! ;)');
					    window.location.replace('index.php');
					}
				});
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
		</script>
	</body>
</html>
