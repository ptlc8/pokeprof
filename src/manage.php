<?php
include("api/init.php"); // init bdd and sendRequest
$user = login(true); // init $user and connect or redirect to login
if (sendRequest("SELECT * FROM CARDSUSERS WHERE id = '", $user['id'], "' AND admin=1")->num_rows <= 0) {
	exit(header('Location: .'));
}
$results = sendRequest("SELECT * FROM CARDS");
$allcards = [];
while (($result = $results->fetch_assoc()) != null) {
	$card = json_decode($result['infos']);
	$card->id = $result['id'];
	$card->name = $result['name'];
	$card->type = $result['type'];
	$card->official = $result['official'];
	$card->rarity = $result['rarity'];
	$card->atk1->script = $result['script1'];
	$card->atk2->script = $result['script2'];
	$card->winrate = $result['uses']==0 ? -1 : intval($result['wins']*100/$result['uses']);
	$card->uses = intval($result['uses']);
	$card->boosterId = $result['boosterId'];
	$card->prestigeable = $result['prestigeable']==1;
	$allcards[$result['id']] = $card;
}
$results = sendRequest("SELECT * FROM BOOSTERS");
$allboosters = [];
while (($result = $results->fetch_assoc()) != null) {
	$booster = new stdClass();
	$booster->id = $result['id'];
	$booster->name = $result['name'];
	$allboosters[$result['id']] = $booster;
}
?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<?php echo_head_tags("Gestion des cartes", ""); ?>
		<link rel="stylesheet" href="style.css" />
		<link rel="stylesheet" href="manage.css" />
		<script src="cards.js"></script>
	</head>
	<body>
		<span id="logged">Vous êtes connecté en tant que <?= htmlspecialchars($user['name']) ?></span>
    	<a href="disconnect.php?back" id="log-out">Se déconnecter</a>
		<table id="cards">
		    <tr><b><th class="name">Toutes les cartes</th><th>R</th><th>W</th><th>O</th></b></tr>
		    <tr><td>Chargement...</td></tr>
		</table>
		<div id="preview-container">
			<div id="preview" class="card" onclick="prestige=!prestige;refreshPreview();"></div>
		</div>
		<form id="inputs" onchange="onInputsEdit()">
			<input type="text" name="name" placeholder="Nom de la carte" />
			<input type="number" min="-9" max="10" name="cost" placeholder="Coût en mana" /><input type="color" name="color">
			<select name="type" onchange="for(let f of document.getElementsByClassName('forprof'))f.style.display=this.value=='prof'?'':'none'">
				<option value="prof">Carte Combattant</option>
				<option value="effect">Carte Effet</option>
				<option value="place">Carte Lieu</option>
			</select>
			<div class="forprof" id="fightertypesmultiselect">
                <!--<span class="add">➕ Types</span>-->
                <span class="placeholder">Types</span>
                <div class="menu">
                    <hr />
				    <?php foreach(sendRequest("SELECT * FROM FIGHTERSCARDSTYPES")->fetch_all(MYSQLI_ASSOC) as $proftype ) echo '<span class="option" data-value="'.$proftype['id'].'">'.$proftype['name'].'</span>'; ?>
				</div>
			</div>
			<script>initMultiSelect(document.getElementById("fightertypesmultiselect"),"types");</script>
			<input name="hp" type="number" min="0" step="10" class="forprof" />
			<fieldset>
				<legend>1ère attaque</legend>
				<input type="text" name="atk1name" placeholder="Nom de la 1ère attaque" /><input type="text" name="atk1dama" placeholder="Dégâts de la 1ère attaque" />
				<input type="text" name="atk1desc" placeholder="Description de la 1ère attaque" />
				<input type="text" name="atk1script" placeholder="Script de la 1ère attaque" onkeyup="testsScripts(1)" />
				<span id="script1-comment"></span>
			</fieldset>
			<fieldset>
				<legend>2ème attaque</legend>
				<input type="text" name="atk2name" placeholder="Nom de la 2ème attaque" /><input type="text" name="atk2dama" placeholder="Dégâts de la 2ème attaque" />
				<input type="text" name="atk2desc" placeholder="Description de la 2ème attaque" />
				<input type="text" name="atk2script" placeholder="Script de la 2ème attaque" onkeyup="testsScripts(2)" />
				<span id="script2-comment"></span>
			</fieldset>
			<select name="rarity">
				<option value="">Rareté non définie</option>
				<option value="1">Commune</option>
				<option value="2">Rare</option>
				<option value="3">Épique</option>
				<option value="4">Légendaire</option>
			</select>
			<select id="booster" name="booster"></select>
			<div>
			    <input type="checkbox" id="prestigeable" name="prestigeable" />
			    <label for="prestigeable">Disponible en version prestige ?</label>
			</div>
			<textarea name="origin" placeholder="Origine de la carte" rows="5"></textarea>
		</form>
		<div id="buttons">
		    <button id="publish-button" onclick="pushEditedCard(2)">Publier la carte</button><button id="buff-button" onclick="pushEditedCard(3)">Appliquer comme buff</button><button id="nerf-button" onclick="pushEditedCard(4)">Appliquer comme nerf</button><button id="deny-button" onclick="pushEditedCard(-3)">Refuser la carte</button><button id="reaccept-button" onclick="pushEditedCard(0)">Réaccepter la carte</button><button id="saveforlate-button" onclick="pushEditedCard(-1)">Enregistrer pour plus tard</button><button id="simpleedit-button" onclick="pushEditedCard()">Appliquer une simple correction</button>
		</div>
		<script>
		    var prestige = false;
			var allfullinfoscards = JSON.parse('<?= str_replace(array('\\', '\''), array('\\\\', '\\\''), json_encode($allcards)); ?>');
			var allboosters = JSON.parse('<?= str_replace(array('\\', '\''), array('\\\\', '\\\''), json_encode($allboosters)); ?>');
			var editing = {};
			setDisabledInputs(true);
			refreshCardsList();
			document.getElementById("publish-button").style.display = "none";
			document.getElementById("buff-button").style.display = "none";
			document.getElementById("nerf-button").style.display = "none";
			document.getElementById("deny-button").style.display = "none";
			document.getElementById("reaccept-button").style.display = "none";
			document.getElementById("saveforlate-button").style.display = "none";
			document.getElementById("simpleedit-button").style.display = "none";
			var boosterSelect = document.getElementById("booster");
			boosterSelect.appendChild(createElement("option", {value:-1}, "Dans aucun booster"));
			for (let boosterId in allboosters) {
			    boosterSelect.appendChild(createElement("option", {value:boosterId}, allboosters[boosterId].name));
			}
			function setDisabledInputs(disabled=true) {
				document.querySelectorAll("#inputs input, #inputs select, #inputs textarea").forEach(function(i) {
					i.disabled = disabled;
				});
				document.getElementById("fightertypesmultiselect").classList[disabled?"add":"remove"]("disabled");
			}
			function setInputsValues(infos) {
				var inputs = document.getElementById("inputs");
				inputs.name.value = infos.name;
				inputs.cost.value = infos.cost;
				inputs.color.value = infos.color;
				inputs.type.value = infos.type;
				//inputs.proftype.value = infos.proftype||"";
				setMultiSelectValue(document.getElementById("fightertypesmultiselect"), infos.types||[]);
				inputs.hp.value = infos.hp||"";
				inputs.atk1name.value = infos.atk1.name;
				inputs.atk1dama.value = infos.atk1.dama;
				inputs.atk1desc.value = infos.atk1.desc;
				inputs.atk1script.value = infos.atk1.script;
				inputs.atk2name.value = infos.atk2.name;
				inputs.atk2dama.value = infos.atk2.dama;
				inputs.atk2desc.value = infos.atk2.desc;
				inputs.atk2script.value = infos.atk2.script;
				inputs.rarity.value = infos.rarity;
				inputs.booster.value = infos.boosterId===null?-1:infos.boosterId;
				inputs.prestigeable.checked = infos.prestigeable;
				inputs.origin.value = infos.origin||"";
				for (let f of document.getElementsByClassName('forprof'))
				    f.style.display = inputs.type.value=='prof' ? '' : 'none';
			}
			function onInputsEdit() {
				editing.name = inputs.name.value;
				editing.cost = inputs.cost.value;
				editing.color = inputs.color.value;
				editing.type = inputs.type.value;
			    if (editing.type != "prof") {
			        //delete editing.proftype;
			        delete editing.types;
			        delete editing.hp;
			    } else {
				    //editing.proftype = inputs.proftype.value;
				    editing.types = inputs.types.value.split(",").filter(String);
				    editing.hp = inputs.hp.value;
			    }
				editing.atk1 = {name:inputs.atk1name.value, dama:inputs.atk1dama.value, desc:inputs.atk1desc.value, script:inputs.atk1script.value};
				editing.atk2 = {name:inputs.atk2name.value, dama:inputs.atk2dama.value, desc:inputs.atk2desc.value, script:inputs.atk2script.value};
				editing.rarity = inputs.rarity.value||0;
				editing.boosterId = inputs.booster.value===-1?null:inputs.booster.value;
				editing.prestigeable = inputs.prestigeable.checked;
				editing.origin = inputs.origin.value=="undefined"?"":inputs.origin.value;
				refreshPreview();
			}
			function startEditingCard(cardId) {
			    editing = JSON.parse(JSON.stringify(allfullinfoscards[cardId]));
				setInputsValues(editing);
				setDisabledInputs(false);
				refreshPreview();
				testsScripts();
				document.getElementById("publish-button").style.display = editing.official > -3 && editing.official <= 0 ? "" : "none";
				document.getElementById("buff-button").style.display = editing.official > 0 ? "" : "none";
				document.getElementById("nerf-button").style.display = editing.official > 0 ? "" : "none";
				document.getElementById("deny-button").style.display = editing.official > -3 && editing.official <= 0 ? "" : "none";
				document.getElementById("reaccept-button").style.display = editing.official == -3 ? "" : "none";
				document.getElementById("saveforlate-button").style.display = editing.official > -3 && editing.official <= 0 ? "" : "none";
				document.getElementById("simpleedit-button").style.display = editing.official > 0 ? "" : "none";
			}
			function refreshPreview() {
				var inputs = document.getElementById("inputs");
				setCardElement(document.getElementById("preview"), editing, prestige);
			}
			var sortBy = "id";
			var invertSort = false;
			function refreshCardsList() {
			    var cardsTable = document.getElementById("cards");
			    cardsTable.innerHTML = "";
			    cardsTable.appendChild(createElement("tr", {}, [
			        createElement("th", {className:"name",title:"Id de la carte"}, "Toutes les cartes", {click:function(){invertSort=sortBy=="id"?!invertSort:false;sortBy="id";}}),
			        createElement("th", {title:"Coût en mana"}, [
			            createElement("img", {src:"assets/mana.png"})
			        ], {click:function(){invertSort=sortBy=="cost"?!invertSort:false;sortBy="cost";}}),
			        createElement("th", {title:"Nombre d'utilisation"}, "U", {click:function(){invertSort=sortBy=="uses"?!invertSort:false;sortBy="uses";}}),
			        createElement("th", {title:"Taux de victoire"}, "📈", {click:function(){invertSort=sortBy=="winrate"?!invertSort:false;sortBy="winrate";}}),
			        createElement("th", {title:"Rareté"}, "📊", {click:function(){invertSort=sortBy=="rarity"?!invertSort:false;sortBy="rarity";}}),
			        createElement("th", {title:"Id du booster"}, "📁", {click:function(){invertSort=sortBy=="boosterId"?!invertSort:false;sortBy="boosterId";}}),
			        createElement("th", {title:"Statut de la carte"}, "✨", {click:function(){invertSort=sortBy=="official"?!invertSort:false;sortBy="official";}})
			    ], {click: refreshCardsList}));
			    var officialEmojis = {"-3":"❌","-2":"🧲","-1":"⌛","0":"🧩","1":"⭐","2":"🆕","3":"🔺","4":"🔻"};
			    var rarityEmojis = {"0":"🚫","1":"🟤","2":"🟠","3":"🟣","4":"🔵"};
			    var costEmojis = ["0️⃣","1️⃣","2️⃣","3️⃣","4️⃣","5️⃣","6️⃣","7️⃣","8️⃣","9️⃣","🔟"];
			    for (let card of Object.values(allfullinfoscards).sort((a,b)=>(a[sortBy]-b[sortBy])*(invertSort?-1:1))) {
			        let cardId = card.id;
			        cardsTable.appendChild(createElement("tr", {}, [
			            createElement("td", {className:"name"}, "["+card.id+"] "+card.name),
			            createElement("td", {}, card.cost>=0&&card.cost<=10?costEmojis[card.cost]:"#️⃣"),
			            createElement("td", {}, card.uses),
			            createElement("td", {}, (card.winrate==-1?"-":card.winrate)+"%"),
			            createElement("td", {}, rarityEmojis[card.rarity]),
			            createElement("td", {}, card.boosterId==null?"×":card.boosterId),
			            createElement("td", {}, officialEmojis[card.official])
			        ], {click:function(){startEditingCard(cardId)}}));
			    }
			}
			function pushEditedCard(official=undefined) {
			    onInputsEdit();
			    var original = allfullinfoscards[editing.id];
			    var pushBody = "id="+editing.id;
			    if (editing.type!="prof") {
			        //delete editing.proftype;
			        delete editing.types;
			        delete editing.hp;
			    }
			    //Modif Léo: rmplacement de original.types par original.type
			    if (editing.origin=="undefined") delete editing.origin;
			    if (official) pushBody +="&official="+official;
			    if (editing.name!=original.name) pushBody += "&name="+encodeURIComponent(editing.name);
			    if (editing.cost!=original.cost) pushBody += "&cost="+editing.cost;
			    if (editing.color!=original.color) pushBody += "&color="+encodeURIComponent(editing.color);
			    if (editing.type!=original.type) pushBody += "&type="+encodeURIComponent(editing.type);
			    if (editing.type=="prof") for (let i = 0; i < Math.max(editing.types.length,original.type.length); i++)
			        if (editing.types[i]!=original.type[i]) {
			            pushBody += "&types="+encodeURIComponent(editing.types.join(","));
			            break;
			        }
			    //if (editing.proftype!=original.proftype) pushBody += "&proftype="+encodeURIComponent(editing.proftype);
			    if (editing.hp!=original.hp && editing.hp!="") pushBody += "&hp="+editing.hp;
			    if (editing.atk1.name!=original.atk1.name) pushBody += "&atk1name="+encodeURIComponent(editing.atk1.name);
			    if (editing.atk1.desc!=original.atk1.desc) pushBody += "&atk1desc="+encodeURIComponent(editing.atk1.desc);
			    if (editing.atk1.dama!=original.atk1.dama) pushBody += "&atk1dama="+editing.atk1.dama;
			    if (editing.atk1.script!=original.atk1.script) pushBody += "&atk1script="+encodeURIComponent(editing.atk1.script);
			    if (editing.atk2.name!=original.atk2.name) pushBody += "&atk2name="+encodeURIComponent(editing.atk2.name);
			    if (editing.atk2.desc!=original.atk2.desc) pushBody += "&atk2desc="+encodeURIComponent(editing.atk2.desc);
			    if (editing.atk2.dama!=original.atk2.dama) pushBody += "&atk2dama="+editing.atk2.dama;
			    if (editing.atk2.script!=original.atk2.script) pushBody += "&atk2script="+encodeURIComponent(editing.atk2.script);
			    if (editing.rarity!=original.rarity) pushBody += "&rarity="+editing.rarity;
			    if (editing.boosterId!=original.boosterId) pushBody += "&booster="+editing.boosterId;
			    if (editing.prestigeable!=original.prestigeable) pushBody += "&prestigeable="+editing.prestigeable;
			    if (editing.origin!=original.origin) pushBody += "&origin="+encodeURIComponent(editing.origin);
			    sendRequest("post", "api/card/edit.php", pushBody).then(function(r) {
			        if (r=="success") {
			            alert("La carte a bien été enregistrée avec succès");
			            allfullinfoscards[editing.id] = JSON.parse(JSON.stringify(editing));
			            if (official) allfullinfoscards[editing.id].official = official;
			            refreshCardsList();
			            startEditingCard(editing.id);
			        } else
			            alert(r);
			    });
			}
			function testsScripts(whose=undefined) {
				var inputs = document.getElementById("inputs");
			    if (!whose || whose==1) {
			        let scriptComment = document.getElementById("script1-comment");
			        let correctness = isScriptCorrect(inputs.atk1script.value);
			        scriptComment.innerText = correctness=="correct"?"Le script est correct":correctness;
			        scriptComment.className = correctness=="correct"?"correct":"";
			    }
			    if (!whose || whose==2) {
			        let scriptComment = document.getElementById("script2-comment");
			        let correctness = isScriptCorrect(inputs.atk2script.value);
			        scriptComment.innerText = correctness=="correct"?"Le script est correct":correctness;
			        scriptComment.className = correctness=="correct"?"correct":"";
			    }
			}
			
			// Test des scripts
			var alltriggers = {
			    onplaycard:{},
			    onaction:{},
			    onturn:{},
			    onsummon:{},
			    //playcardcondition:{nobody:true},
			    ondie:{}
			};
			var allfuncs = {
              attackif:["profs","number","condition","number"],
              attack:["profs","number","?condition"],
              heal:["profs","number"],
              sleep:["profs","number"],
              wakeup:["profs"],
              seedraw:["who","number"],
              seedrawhim:["who","number"],
              kick:["profs"],
              paralyse:["profs","number"],
              affraid:["profs","number"],
              courage:["profs"],
              electrify:["profs","number"],
              diselectrify:["profs"],
              setvar:["string","number"],
              leaveplace:[],
              delmana:["who","number"],
              givemana:["who","number"],
              draw:["who"],
              drop:["who","number|random"],
              addshield:["profs","number"],
              removeshield:["profs","number"],
              addstrength:["profs","number"],
              summon:["number","number","proftype","number","number"],
              convert:["profs"],
              disengage:["profs"],
              invoc:["who","number"],
              rescue:["last|random"],
              givecard:["who", "number"],
              retreat:["profs"],
              makeprovoking:["profs","number"]
            };
            var allproftypes = <?=json_encode(array_map(function($t){return $t['id'];}, sendRequest("SELECT id FROM FIGHTERSCARDSTYPES")->fetch_all(MYSQLI_ASSOC)))?>;
            
            function isScriptCorrect(script) {
              if (script == "") return "le script est vide";
              var scriptName = getScriptName(script);
              if (!Object.keys(alltriggers).includes(scriptName))
                return "le déclencheur \""+scriptName+"\" n'est pas reconnu";
              script = script.replace(scriptName, "");
              if (!script.startsWith("{") && !alltriggers[scriptName].nobody)
                return "\"{\" attendue après \""+scriptName+"\"";
              var scriptCondition = getCondition(script);
              if (scriptCondition!=undefined) {
                script = script.substring(0, script.length-2-scriptCondition.length);
                if (!isCondition(scriptCondition))
                    return "la condition de déclencheur \""+scriptCondition+"\" n'est pas valide";
              }
              if (alltriggers[scriptName].nobody) return "correct";
              if (!script.endsWith("}"))
                return "le script de \""+scriptName+"\" ne se termine pas par \"}\"";
              script = script.substring(1, script.length-1);
              var funcs = script.split(" ");
              for (let i = 0; i < funcs.length; i++) {
                let func = funcs[i];
                if (func=="") continue;
                let funcName = getFuncName(func);
                if (!Object.keys(allfuncs).includes(funcName))
                  return "la fonction \""+funcName+"\" n'est pas reconnue";
                func = func.replace(funcName, "");
                if (!func.startsWith("("))
                  return "\"(\" attendue après \""+funcName+"\"";
                var funcCondition = getCondition(func);
                if (funcCondition!=undefined) {
                  func = func.substring(0, func.length-2-funcCondition.length);
                  if (!isCondition(funcCondition))
                    return "la condition de fonction \""+funcCondition+"\" n'est pas valide";
                }
                if (!func.endsWith(")"))
                  return "la fonction de \""+funcName+"\" ne se termine pas par \")\"";
                func = func.substring(1, func.length-1);
                var args = func==""?[]:func.split(",");
                if (args.length < allfuncs[funcName].filter(function(p){return !p.startsWith("?");}).length)
                  return "la fonction \""+funcName+"\" manque d'arguments : "+allfuncs[funcName].length+" attendus";
                if (args.length > allfuncs[funcName].length)
                  return "la fonction \""+funcName+"\" a trop d'arguments : "+allfuncs[funcName].length+" attendus";
                for (let j = 0; j < args.length; j++) {
                  let arg = args[j];
                  if (arg=="") continue;
                  if (!isOfType(allfuncs[funcName][j], arg))
                    return "\""+arg+"\" n'est pas de type "+allfuncs[funcName][j];
                }
              }
              return "correct";  
            }
            
            function getScriptName(script) {
              var scriptName = "";
              for (let l of script) {
                if (l == "{" || l == "[") break;
                scriptName += l;
              }
              return scriptName;
            }
            
            function getFuncName(func) {
              var funcName = "";
              for (let l of func) {
                if (l == "(") break;
                funcName += l;
              }
              return funcName;
            }
            
            function getCondition(scriptOrFuncOrTarget) {
              if (!scriptOrFuncOrTarget.endsWith("]")) return undefined;
              var condition = ""
              for (let i = scriptOrFuncOrTarget.length-2; i >= 0; i--) {
                if (scriptOrFuncOrTarget[i] == "[") break;
                condition = scriptOrFuncOrTarget[i] + condition;
              }
              return condition;
            }
            
            function isOfType(typeOrTypes, arg) {
              var types = typeOrTypes.split("|");
              for (let type of types) {
                if (type.startsWith("?"))
                  type = type.replace("?","");
                if (type=="number") {
                  if (isValue(arg))
                    return true;
                } else if (type=="profs") {
                  if (isProfs(arg))
                    return true;
                } else if (type=="condition") {
                  if (isCondition(arg))
                    return true;
                } else if (type=="who") {
                  if (isWho(arg))
                    return true;
                } else if (type=="string") {
                  if (isString(arg))
                    return true;
                } else if (type=="proftype") {
                  if (allproftypes.includes(arg))
                    return true;
                } else if (type==arg) {
                  return true;
                }
              }
              return false;
            }
            
            function isValue(arg) {
              if (arg!="" && !isNaN(arg))
                return true;
              if (arg.includes("+")) {
                for (let sub of arg.split("+"))
                  if (!isValue(sub))
                    return false;
                return true;
              } else if (arg.includes("-")) {
                for (let sub of arg.split("-"))
                  if (!isValue(sub))
                    return false;
                return true;
              } else if (arg.includes("*")) {
                for (let sub of arg.split("*"))
                  if (!isValue(sub))
                    return false;
                return true;
              } else if (arg.includes("%")) {
                for (let sub of arg.split("%"))
                  if (!isValue(sub))
                    return false;
                return true;
              }
              let words = arg.split("_");
              if (words.length > 1) {
                if (words[0] == "getvar" && isString(words[1]))
                  return true;
                if (words[0] == "random" && isValue(arg.replace(words[0]+"_", "")))
                  return true;
                if (words[0] == "count" && isProfs(arg.replace(words[0]+"_", "")))
                  return true;
              }
              if (["t","hp","hpmax","cost","type","place","slp","prl","efr","elc","shield","strength"].includes(arg))
                return true;
              return false;
            }
            
            function isProfs(arg) {
              var profsCondition = getCondition(arg);
              if (profsCondition!=undefined) {
                arg = arg.substring(0, arg.length-2-profsCondition.length);
                if (!isCondition(profsCondition))
                  return false;
              }
              if (!["all","allofhim","allofyou","target","targetofhim","target2ofhim","targetofyou","target2ofyou","it","you","him","summoned","randomofhim","randomofyou"].includes(arg))
                return false;
              return true;
            }
            
            function isCondition(arg) {
              if (arg.includes("|")) {
                for (let sub of arg.split("|"))
                  if (!isCondition(sub))
                    return false;
                return true;
              } else if (arg.includes("&")) {
                for (let sub of arg.split("&"))
                  if (!isCondition(sub))
                    return false;
                return true;
              } else if (arg.includes("!=")) {
                for (let sub of arg.split("!="))
                  if (!isOfType("number|string", sub))
                    return false;
                return true;
              } else if (arg.includes("=")) {
                for (let sub of arg.split("="))
                  if (!isOfType("number|string", sub))
                    return false;
                return true;
              } else if (arg.includes("!")) {
                for (let sub of arg.split("!"))
                  if (!isOfType("number|string", sub))
                    return false;
                return true;
              } else if (arg.includes(">")) {
                for (let sub of arg.split(">"))
                  if (!isValue(sub))
                    return false;
                return true;
              } else if (arg.includes("<")) {
                for (let sub of arg.split("<"))
                  if (!isValue(sub))
                    return false;
                return true;
              }
              if (["targetsleep","true","false"].includes(arg))
                return true;
              let words = arg.split("_");
              if (words.length > 1) {
                if (words[0] == "isplace" && isValue(words[1]))
                  return true;
                if (words[0] == "in" && isProfs(words[1]) && isValue(words[2]))
                  return true;
                if (words[0] == "hastype" && isOfType("proftype",words[1]))
                  return true;
                if (words[0] == "hasnottype" && isOfType("proftype",words[1]))
                  return true;
              }
              return false;
            }
            
            function isWho(arg) {
              if (arg == "you" || arg == "him")
                return true;
              return false;
            }
            
            function isString(arg) {
              for (let l of arg)
                if (!"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ".includes(l))
                  return false;
              return true;
            }
		</script>
	</body>
</html>
