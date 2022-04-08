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
        if (($user!=null)&&(sendRequest("SELECT admin FROM CARDSUSERS WHERE id='".$user['id']."'")->fetch_assoc()['admin']==1)) {
        ?>
        <div id="managementBord">
            <h3>Administration du tournoi</h3>
            
            <div id="actionTournament">
                <form id="tournament-form1" class="tournament-form" method="POST" action="modiftournament.php">
                    
                    
                    <?php
                        echo '<input type="hidden" name="idTournament" value='.$_REQUEST['id'].'>';
                    ?>
                           
                           
                    <div class="tournament-form-div">
                        <label for="tournament-status">Etat du tournoi : </label>
                        <select name="now" id="tournament-status" form="tournament-form1">
                            <option value="inscriptions">Inscriptions ouvertes</option>
                            <option value="started">Tournoi lancé</option>
                            <option value="ended">Tournoi terminé</option>
                        </select>
                    </div>
                    
                    <div class="tournament-form-div">
                        <label for="tournament-limit">Limiter le nombre de places : </label>
                        <input type="checkbox" id="tournament-limit" name="limitBool" />
                        <input id="tournament-places" name="nb_places" type="number" min="0"/>
                    </div>
                    
                    <div class="tournament-form-div">
                        <label for="tournament-draft">Repêchages :</label>
                        <input type="checkbox" id="tournament-draft" name="draftBool" />
                        <div id="tournament-drafts">
                            <label for="tournament-drafts-type">Type de repêchage : </label>
                            <select name="status" id="tournament-drafts-type" form="tournament-form1">
                                <option value="manual">Manuel</option>
                                <option value="half">Demi-finale</option>
                                <option value="quarter">Quart-de-finale</option>
                                <option value="eight">Huitième-de-finale</option>
                                <option value="all">Tous repéchés</option>
                            </select>
                            
                            <label for="tournament-drafts-number">Nombre de repéchés :</label>
                            <input id="tournament-drafts-number" name="drafts" type="number" min="1"/>
                        </div>
                    </div>
                    
                    <button class="button" action="">Appliquer les modifications</button>
                </form>
            </div>
            
            <div id="tournament-players">
                <table id="list-players"></table>
            </div>
                
        </div>
        
        <?php
        }
        ?>
        
		<script>
			var params = new URLSearchParams(window.location.search);
			var tournamentId=params.get("id");
            affGraphTournament(tournamentId);
            setInterval(()=>affGraphTournament(tournamentId),60000);
			document.getElementById("join-button").addEventListener("click", function(e) {
				affGraphTournament(tournamentId, "join");
			});
			document.getElementById("leave-button").addEventListener("click", function(e) {
				affGraphTournament(tournamentId, "leave");
			});
		</script>
	</body>
</html>
