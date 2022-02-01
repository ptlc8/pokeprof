<?php
  
 //fonctions pour faire des tableaux corrects, Léo

function recurPrettyTable (& $Tree, $nbQualified, $i, $j) {
	if ($nbQualified<=1) {
		$Tree[$i][$j]='_';	//emplacement d'un id de joueur
	}
	else  {
		$Tree[$i][$j]=' ';
		recurPrettyTable($Tree, intdiv($nbQualified,2)+($nbQualified%2), $i-1, $j*2);
		recurPrettyTable($Tree, intdiv($nbQualified,2), $i-1, ($j*2)+1);
	}
}

function prettyTable4Tournament ($trees3) {
	$Tree=array();
	if (($trees3==null)||(!isset($trees3))) {
		return(null);	
	}
	$trees=array();
	$trees2=array();
	//$trees3=parseFighters($textFighters);
	/*$trees=explode(';',$textFighters);
	for ($i=0; $i<count($trees); $i++) {
		$trees2[$i]=explode(',',$trees[$i]);
		for ($j=0; $j<count($trees2[$i]); $j++) {
			$trees3[$i][$j]=explode('.',$trees2[$i][$j]);
		}
	}*/
	//on vérifie si le tournoi a commencé. 2 méthodes:
	//- regarde les doublons dans le tableau
	//- existance des repêchages
	//on choisit la première pour récupérer les participants au passage
	$i=0;
	$a=1;
	if (!isset($trees3[0][0][0])) {
		$trees2[0]=$trees3;
		$trees3=$trees2;
	}
	$fighters=[[$trees3[0][0][0],0]];
	//on s'intéresse seulement au tableau principal
	foreach ($trees3[0] as $branch) {
		foreach ($branch as $node) {
			$i=0;
			$j=0;
			while ($i<$a) {
				if ($fighters[$i][0]==$node) {
					$fighters[$i][1]++;
					$j=1;
				}
				$i++;
			}
			if ($j!=1) {
				$fighters[$a]=[$node,1];
				$a++;
			}
		}
	}
	$j=0;
	$a=0;
	foreach ($fighters as $fighter) {
		if ($fighter[1]>1) {
			$j=1;	
		} else {
			$a++;
		}
	}
	if ($j!=0) { //le tournoi a commencé
		return(stringifyFighters($trees3, $fighters));
	}
	//modif avec appel récursif
	recurPrettyTable ($Tree, $a, ceil(log($a,2)), 0);
	$trees3[0]=$Tree;
	$textFinal=stringifyFighters($trees3, $fighters);
	/*$textFinal="";
	$a=0;
	for ($i=0; $i<count($trees3); $i++) {
		for ($j=0; $j<count($trees3[$i]); $j++) {
			for ($k=0; $k<=max(array_keys($trees3[$i][$j])); $k++) {
				if ($k==0) {
					if (isset($trees3[$i][$j][$k])) {
						if ($trees3[$i][$j][$k]=='_') {
							$textFinal=$textFinal.$fighters[$a][0];
							$a++;
						} else {
							$textFinal=$textFinal.$trees3[$i][$j][$k];
						}
					}
				} else {
					if (isset($trees3[$i][$j][$k])) {
						if ($trees3[$i][$j][$k]=='_') {
							$textFinal=$textFinal.'.'.$fighters[$a][0];
							$a++;
						} else {
							$textFinal=$textFinal.'.'.$trees3[$i][$j][$k];
						}
					} else {
						$textFinal=$textFinal.'.';	
					}
				}
			}
			if ($j<count($trees3[$i])-1) {
				$textFinal=$textFinal.',';
			}
		}
		if ($j<count($trees3)-1) {
			$textFinal=$textFinal.';';
		}
	}*/
	return($textFinal);
}


//Join

function tournamentIncludesPlayer($fighters, $playerId) {
	return in_array($playerId, $fighters[0][0]);
}

function tournamentAddPlayer(& $fighters, $playerId, $idDB=null) {
	array_push($fighters[0][0],$playerId);
	if ($idDB!=null) {
		echo sendRequest("UPDATE TOURNAMENT SET fighters='", stringifyFighters($fighters, null), "' WHERE id='", $idDB, "'");	
	}
}

function parseFighters($fighters) {
	$fightersArray = explode(';',$fighters);
	foreach($fightersArray as &$tree) {
		$tree = explode(',',$tree);
		foreach ($tree as &$branch)
			$branch = explode('.', $branch);
	}
	return $fightersArray;
}

function stringifyFighters($trees3, $fighters) {
	$textFinal="";
	$a=0;
	for ($i=0; $i<count($trees3); $i++) {
		for ($j=0; $j<count($trees3[$i]); $j++) {
			for ($k=0; $k<=max(array_keys($trees3[$i][$j])); $k++) {
				if ($k==0) {
					if (isset($trees3[$i][$j][$k])) {
						if ($trees3[$i][$j][$k]=='_') {
							if ($fighters==null) {
								throw new Exception("La liste des combattants n'est pas définie alors qu'elle est nécessaire.");	
							}
							if (isset($fighters[$a][0])) {
								$textFinal=$textFinal.$fighters[$a][0];
							} else {
								$textFinal=$textFinal.$fighters[$a];
							}
							$a++;
						} else {
							$textFinal=$textFinal.$trees3[$i][$j][$k];
						}
					}
				} else {
					if (isset($trees3[$i][$j][$k])) {
						if ($trees3[$i][$j][$k]=='_') {
							if ($fighters==null) {
								throw new Exception("La liste des combattants n'est pas définie alors qu'elle est nécessaire.");	
							}
							if (isset($fighters[$a][0])) {
								$textFinal=$textFinal.'.'.$fighters[$a][0];
							} else {
								$textFinal=$textFinal.'.'.$fighters[$a];
							}
							$a++;
						} else {
							$textFinal=$textFinal.'.'.$trees3[$i][$j][$k];
						}
					} else {
						$textFinal=$textFinal.'.';	
					}
				}
			}
			if ($j<count($trees3[$i])-1) {
				$textFinal=$textFinal.',';
			}
		}
		if ($i<count($trees3)-1) {
			$textFinal=$textFinal.';';
		}
	}
	return($textFinal);
}
  
?>
