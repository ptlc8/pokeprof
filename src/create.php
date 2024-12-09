<?php
include('api/init.php');
$user = login(true);
$color = dechex(random_int(0, 16777215));
?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<?php echo_head_tags("Création de carte", "Créez vos propres cartes dans Poképrof ! Donnez vie à vos héros, élèves ou terrains avec des pouvoirs uniques et des effets déjantés. Laissez libre cours à votre imagination et façonnez le jeu à votre image !"); ?>
		<link rel="stylesheet" href="style.css" />
		<link rel="stylesheet" href="create.css" />
		<script src="cards.js"></script>
	</head>
	<body>
		<span class="title">Création</span>
		<a href="gallery.php" id="gallery-button" class="button">Explorer la galerie</a>
		<a href="." id="home-button" class="button">Retourner au menu principal</a>
		<span id="logged">Vous êtes connecté en tant que <?= htmlspecialchars($user['name']) ?></span>
    	<a href="disconnect.php?back" id="log-out">Se déconnecter</a>
		<div id="main">
			<div id="view"></div>
			<fieldset id="infos"><legend>Informations de la carte</legend><form onchange="refresh(this)">
				<input type="text" name="name" placeholder="Nom" />
				<br />
				<input type="color" name="color" value="#<?php echo $color; ?>" /><label for="color">Couleur de la carte</label>
				<br />
				<!--<input type="text" name="imgSrc" placeholder="URL de l'image" value="" onchange="loadImageFromUrl(this.value, form);" />
				<br />-->
				<label for="image">Image </label><input type="file" name="image" onchange="loadImageFromFile(this.files[0], form);" />
				<br />
				<img id="image" style="height: 6em;" />
				<br />
				<select name="cardtype" onchange="for(let f of document.getElementsByClassName('forprof'))f.style.display=this.value=='prof'?'':'none'">
					<option value="prof">Carte Combattant</option>
					<option value="effect">Carte Effet</option>
					<option value="place">Carte Lieu</option>
				</select>
				<br />
				<div class="forprof" id="fightertypesmultiselect" onchange="refresh(this.parentElement)">
                    <span class="placeholder">Types</span>
                    <div class="menu">
                        <hr />
    				    <?php foreach(sendRequest("SELECT * FROM FIGHTERSCARDSTYPES")->fetch_all(MYSQLI_ASSOC) as $proftype ) echo '<span class="option" data-value="'.$proftype['id'].'">'.$proftype['name'].'</span>'; ?>
    				</div>
				</div>
				<script>initMultiSelect(document.getElementById("fightertypesmultiselect"), "proftype");</script>
				<br class="forprof" />
				<input type="number" name="hp" placeholder="Points de vie" value="80" step="10" min="0" class="forprof" />
				<br class="forprof" />
				<input type="number" name="invo" placeholder="Coût d'invoctation" value="3" min="1" max="12" />
				<br />
				<input type="text" name="atk1Name" placeholder="Nom de la 1ère attaque" />
				<br />
				<input type="text" name="atk1Desc" placeholder="Description de la 1ère attaque" />
				<br />
				<input type="text" name="atk1Dama" placeholder="Dégâts de la 1ère attaque" />
				<br />
				<input type="text" name="atk2Name" placeholder="Nom de la 2ème attaque" />
				<br />
				<input type="text" name="atk2Desc" placeholder="Description de la 2ème attaque" />
				<br />
				<input type="text" name="atk2Dama" placeholder="Dégâts de la 2ème attaque" />
				<br />
				<label for="rarity">Rareté </label>
				<select name="rarity">
				    <option value="">Non définie</option>
				    <option value="1">Commune</option>
				    <option value="2">Rare</option>
				    <option value="3">Épique</option>
				    <option value="4">Légendaire</option>
				</select>
				<br />
				<textarea name="origin" placeholder="Quelle est l'origine de cette carte ?"></textarea>
				<br />
				<input type="checkbox" id="repect" name="repect" required /><label for="repect">En créant cette carte j'en suis responsable, et je respecte le droit à l'image</label>
				<br />
				<input type="button" value="Actualiser" onclick="refresh(form)" />
				<input type="button" value="Enregister dans la galerie" onclick="save(form)" />
			</form></fieldset>
			<script>
				var view = document.getElementById('view');
				var image = document.getElementById('image');
				var imageBlob = undefined;
				window.onload = () => {
					loadImageFromUrl('assets/cards/undefined.png');
					setCardElement(view, {image:{src:"assets/cards/undefined.png"}, name:"Nom de la carte", type:"prof", color:"#<?php echo $color; ?>", cost:3, hp:"40", types:["Type"], atk1:{name:"Attaque 1", desc:"", dama:"20"}, atk2:{name:"", desc:"", dama:""}});
				}
				function refresh(form) {
					setCardElement(view, {image:image, name:form.name.value, type:form.cardtype.value, color:form.color.value, cost:form.invo.value, hp:form.hp.value, atk1:{name:form.atk1Name.value, desc:form.atk1Desc.value, dama:form.atk1Dama.value}, atk2:{name:form.atk2Name.value, desc:form.atk2Desc.value, dama:form.atk2Dama.value}, rarity:form.rarity.value, types:getMultiSelectValue(document.getElementById("fightertypesmultiselect"))});
				}
				function save(form) {
					if (!form.image.files || !form.image.files[0]) {
						alert('La carte doit comporter une image');
						return;
					}
					if (!form.repect.checked) {
						alert("Vous devez cochez la p'tite case en bas");
						return;
					}
					var reader = new FileReader();
					reader.onload = (e) => {
						sendRequest('POST', 'api/card/create.php', 'name='+form.name.value
							+'&cost='+encodeURIComponent(form.invo.value)
							+'&type='+encodeURIComponent(form.cardtype.value)
							+(form.cardtype.value=='prof'?'&hp='+encodeURIComponent(form.hp.value):'')
							+(form.cardtype.value=='prof'?'&proftype='+encodeURIComponent(form.proftype.value):'')
							+'&color='+encodeURIComponent(form.color.value)
							+'&atk1name='+encodeURIComponent(form.atk1Name.value)
							+'&atk1desc='+encodeURIComponent(form.atk1Desc.value)
							+'&atk1dama='+encodeURIComponent(form.atk1Dama.value)
							+'&atk2name='+encodeURIComponent(form.atk2Name.value)
							+'&atk2desc='+encodeURIComponent(form.atk2Desc.value)
							+'&atk2dama='+encodeURIComponent(form.atk2Dama.value)
							+'&rarity='+encodeURIComponent(form.rarity.value)
							+'&origin='+encodeURIComponent(form.origin.value)
							+'&image='+encodeURIComponent(e.target.result)).then((c)=>{
							    if (c.startsWith('success ')) {
							        alert("Ta carte a bien été sauvegardée, elle apparaîtra dans la galerie et sera en jeu quand elle aura été équilibrée :)");
							        window.location.href = 'gallery.php#'+c.replace("success ", "");
							    } else if (c=='not logged') {
							        //alert('Vous devez être connecté pour enregistrer des cartes');
							        queryConnect();
							    } else {
							        alert(c);
							    }
							});
					}
					reader.readAsDataURL(form.image.files[0]);
				}
				function queryConnect() {
			        openInNewTab('/connect.php?closeafter');
			        document.getElementById('login').innerText='Connecté(e) !';
			    }
			    function openInNewTab(url) {
				    var win = window.open(url, '_blank');
				    win.focus();
			    }
				function loadImageFromFile(file, form=undefined) {
					var reader = new FileReader();
					reader.onload = e => {
						imageBlob = e.target.result;
						image.src = imageBlob;
						if (form) refresh(form);
					}
					reader.readAsDataURL(file);
				}
				function loadImageFromUrl(url, form=undefined) {
					xhr = new XMLHttpRequest();
					xhr.open("GET", url);
					xhr.responseType = 'blob';
					xhr.onreadystatechange = function() {
						if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
							var reader = new FileReader();
							reader.readAsDataURL(xhr.response)
							reader.addEventListener('loadend', (e) => {
								imageBlob = e.srcElement.result
								image.src = imageBlob;
								if (form) refresh(form);
							});
						}
					};
					xhr.send();
				}
			</script>
		</div>
	</body>
</html>
