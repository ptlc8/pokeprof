<?php session_start() ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>PokéProf: Les Règles !</title>
		<link rel="stylesheet" href="style.css" />
		<link rel="stylesheet" href="rules.css" />
		<script src="cards.js"></script>
		<link rel="icon" type="image/png" href="assets/back.png" />
	</head>
	<body>
		<div id="blur-bg">
    		<?php
    		include('../init.php');
    		
			$user = login(true, false);
    		
    		?>
    		
    		<button id="gotomenu" onclick="window.location.href = './'";>Menu</button>
    		<span class="title">PokéProf: Les Règles !</span>
    		
    		
    		<!-- But du jeu -->
    		<div class="rule_block">
    		    <span class="title" style="font-size:2em; margin: 1em 0 1em 0;">But du jeu: </span>
    		    <span class="rule_text">Le but ? <strong>DÉGLINGUER TON ADVERSAIRE !</strong><br/>Mais comment ? Continue à lire pour le savoir...</span>
    		</div>
    		
    		
    		<div class="tab">
              <button id="tablink_bases" class="tablinks" onclick="opentab(event, 'bases')">Bases</button>
              <button id="tablink_deroul" class="tablinks" onclick="opentab(event, 'deroul')">Déroulement</button>
            </div>
            
            <div id="default" class="tabcontent">
                <button class="nextbut tablinks" onclick="opentab(event,'bases');document.getElementById('tablink_bases').className += ' active'">⬇</button>
            </div>

            
            <!-- Tab content: les bases -->
            <div id="bases" class="tabcontent">
    		
        		<!-- Déroulement de la partie -->
        		<div class="rule_block">
        		    <span class="title" style="font-size:2em; margin: 0 0 1em 0;">Les bases </span>
        		    <span class="rule_text">Chaque joueur possède un nombre de points de vie, abrégés PV et représentés par <span class="tooltip"><img src="assets/hp.png" height="20" style="position: relative; top:0.2em;"/><span class="tooltiptext" style="background-color:white;"><img src="assets/hp.png" height="50"/></span></span>. Le but est de faire descendre les PV de l'adversaire à zéro ou en deçà.<br/>Pour cela, chaque joueur dispose de cartes (oui c’est un jeu de cartes.) représentant <bld>les héros de l’EISTI</bld>, dont voici les détails :
        		    <ul>
        		        <li>
        		            <bld>Les Profs :</bld> Ce sont des profs. Les héros. Les légendes. Ils ont leurs noms dans le titre du jeu quand même !<br/> Ils peuvent être des profs de <span style="color:#0000DD">maths</span>, de <span style="color:#DD0000">mécanique</span>, de <span style="color:#DDDD00">physique</span> ou d’<span style="color:#10CC10">informatique</span>.
        		        </li>
        		        <br/>
        		        <li>
        		            <bld>Le Personnel Administratif :</bld> vous savez, ceux qui sont absents aux seuls moments où il nous est possible d'aller les voir…<br/>Bah ils sont là eux aussi (pour une fois).
        		        </li>
        		        <br/>
        		        <li>
        		            <bld>Les Élèves : …</bld>
    Ben c’est nous quoi ! Donc vous savez si vous êtes un <span style="color:#0000DD">branleur</span>, un <span style="color:#DD0000">gamer</span>, un <span style="color:#DDDD00">fumeur</span> ou un <span style="color:#10CC10">travailleur</span>.
                        </li>
                        <br/>
                        <li>
                            <bld>Les Effets :</bld> que ça soit des actions plus ou moins bénignes ou de simples objets auxquels une part de vie est accrochée, ils ne vous laisseront pas indemnes !
                        </li>
        		    </ul>
        		    
        		    En outre, chaque carte possède un nombre de PV, une ou deux attaques de puissance et d’effets -probablement- variés et un coût d’invocation, dont nous parlerons juste après.<br/>Quand vient votre tour (parce que c’est un jeu au tour par tour, oui… !), vous pouvez placer vos cartes.<br/><bld>SAUF QUE ce n’est pas si simple.</bld>
        		    </span>
        		</div>
        		<button class="nextbut tablinks" onclick="opentab(event,'deroul');document.getElementById('tablink_deroul').className += ' active'">➡</button> 
            </div>
            
            
            <!-- Tab content: les bases -->
            <div id="deroul" class="tabcontent">
    		
        		<!-- Déroulement de la partie -->
        		<div class="rule_block">
        		    <span class="title" style="font-size:2em; margin: 0 0 1em 0;">Le déroulement d'une partie :</span>

        		    <span class="rule_text">
        		        
        		        
        		    </span>
        		</div>
        		<button class="nextbut tablinks" onclick="opentab(event,'bases');document.getElementById('tablink_bases').className += ' active'">⬅</button> 
            </div>
		</div>
		
		<script>
		    function opentab(evt, tabid) {
                var i, tabcontent, tablinks;
            
                tabcontent = document.getElementsByClassName("tabcontent");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
            
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" active", "");
                }
            
                document.getElementById(tabid).style.display = "block";
                evt.currentTarget.className += " active";
            }
            opentab(event,'default');
		</script>
</body>



</html>