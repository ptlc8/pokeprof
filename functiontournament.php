<?php
  
function recupFighters($tree) {
	$a=0;
	$fighters=[];
	//on s'intéresse seulement au tableau principal
	foreach ($tree as $branch) {
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
			if (($j!=1)&&($node!='')&&($node!=' ')) {
				$fighters[$a]=[$node,1];
				$a++;
			}
		}
	}
	return($fighters);
}

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
	$fighters=recupFighters($trees3[0]);
	/*$fighters=[[$trees3[0][0][0],0]];
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
	}*/
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
    if ($a>0) {
	   recurPrettyTable ($Tree, $a, ceil(log($a,2)), 0);
    }
	$trees3[0]=$Tree;
	$textFinal=stringifyFighters($trees3, $fighters);
	return($textFinal);
}


//Join

function tournamentIncludesPlayer($fighters, $playerId) {
    foreach ($fighters[0] as $branch) {
        $bool=in_array($playerId, $branch);
        if ($bool) {
            return $bool;
        }
    }
    return (false);
}

function tournamentAddPlayer(& $fighters, $playerId, $idDB=null, $nbPlaces=null) {
	if ((count($fighters[0])<3)&&(count(recupFighters($fighters[0]))<1)) { //première condition sert à alléger la compléxité
		$fighters[0]=array();
		$fighters[0][0][0]=$playerId;
	} else {
		array_push($fighters[0][0],$playerId);
	}
	if ($idDB!=null) {
		sendRequest("UPDATE TOURNAMENT SET fighters='", prettyTable4Tournament($fighters, null), "' WHERE id='", $idDB, "'");
		if ($nbPlaces!=null) {
			sendRequest("UPDATE TOURNAMENT SET nbPlaces=", $nbPlaces-1, " WHERE id='", $idDB, "'");
		}
	}
}

function tournamentDelPlayer(& $fighters, $playerId, $idDB=null, $nbPlaces=null) {
	$index=array();
	$temp=array();
	$nbDel=0;
	$index[0]=array_search($playerId, $fighters[0][0]);
	if (isset($fighters[0][1])) {
		$index[1]=array_search($playerId, $fighters[0][1]);
	} else {
		$index[1]=false;
	}
	if (($index[0]!==false)||($index[1]!==false)) {
		for ($i=0; $i<count($index); $i++) {
			if ($index[$i]!==false) {
				$temp=array_slice($fighters[0][$i], 0, $index[$i], true);
				$fighters[0][$i]=array_slice($fighters[0][$i], $index[$i]+1);
				$fighters[0][$i]=array_merge($temp, $fighters[0][$i]);
                if (sizeof($fighters[0][$i])<=0) {
                    $fighters[0][$i][0]='';
                }
				$nbDel++;
			}
		}
		if ($idDB!=null) {
			sendRequest("UPDATE TOURNAMENT SET fighters='", stringifyFighters($fighters, null), "' WHERE id='", $idDB, "'");
			if ($nbPlaces!=null) {
				sendRequest("UPDATE TOURNAMENT SET nbPlaces=", $nbPlaces+$nbDel, " WHERE id='", $idDB, "'");
			}
		}
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
        if ((isset($trees3[$i]))&&($trees3[$i]!=[])) {
            for ($j=0; $j<count($trees3[$i]); $j++) {
                if ((isset($trees3[$i][$j]))&&($trees3[$i][$j]!=[])) {
                    for ($k=0; $k<=max(array_keys($trees3[$i][$j])); $k++) {
                        if ($k==0) {
                            if (isset($trees3[$i][$j][$k])) {
				                if ($trees3[$i][$j][$k]=='_') {
				                    if ($fighters==null) {
                                        throw new Exception("La liste des combattants n'est pas définie alors qu'elle est nécessaire.");	
                                    }
                                    if ((is_array($fighters[$a]))&&(isset($fighters[$a][0]))) {
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
                                    if ((is_array($fighters[$a]))&&(isset($fighters[$a][0]))) {
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
                }
                if ($j<count($trees3[$i])-1) {
                    $textFinal=$textFinal.',';
                }
            }
        }
		if ($i<count($trees3)-1) {
			$textFinal=$textFinal.';';
		}
	}
	return($textFinal);
}

function namesFighters ($fightersId) {
    $names=array();
    if (($fightersId!=null)&&(isset($fightersId[0]))) {
        $request="SELECT id, name FROM USERS WHERE id IN (";
        if (is_array($fightersId[0])) {
            $request=$request."'".$fightersId[0][0]."'";
        } else {
            $request=$request."'".$fightersId[0]."'";
        }
        for ($i=1; $i<count($fightersId); $i++) {
            if (is_array($fightersId[$i])) {
                $request=$request.", '".$fightersId[$i][0]."'";
            } else {
                $request=$request.", '".$fightersId[$i]."'";
            }
        }
        $request=$request.");";
        $result=sendRequest($request)->fetch_all();
        foreach ($result as $line) {
            $names[$line[0]]=$line[1];
        }
        if (count($names)<count($fightersId)) {
            foreach ($fightersId as $id) {
                if (is_array($id)) {
                    if (!in_array($id[0], array_keys($names))) {
                        $names[$id[0]]=$id[0];
                    }
                } else {
                    if (!in_array($id, array_keys($names))) {
                        $names[$id]=$id;
                    }   
                }
            }
        }   
    }
    $names['']='';
    $names[' ']=' ';
    $names['_']='ERREUR: name="_"';
    return ($names);
}

?>
