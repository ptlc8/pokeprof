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

function prettyTable4Tournament ($textFighters) {
	$Tree=array();
	if (($textFighters==null)||(!isset($textFighters))) {
		return(null);	
	}
	$trees=array();
	$trees2=array();
	$trees3=array();
	$trees=explode(';',$textFighters);
	for ($i=0; $i<count($trees); $i++) {
		$trees2[$i]=explode(',',$trees[$i]);
		for ($j=0; $j<count($trees2[$i]); $j++) {
			$trees3[$i][$j]=explode('.',$trees2[$i][$j]);
		}
	}
	//on vérifie si le tournoi a commencé. 2 méthodes:
	//- regarde les doublons dans le tableau
	//- existance des repêchages
	//on choisit la première pour récupérer les participants au passage
	$i=0;
	$a=1;
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
		return($textFighters);
	}
	//modif avec appel récursif
	recurPrettyTable ($Tree, $a, ceil(log($a,2)), 0);
	$k=0;
	foreach ($Tree as $branch) {
		foreach ($branch as $node) {
			if ($node=='_') {
				$node=$fighters[$k][0];
				$k++;
			}
		}
	}
	$trees3[0]=$Tree;
	print_r($trees3);
	$textFinal="";
	/*foreach ($trees3 as $tree) {
		foreach ($tree as $branch) {
			//$branch=implode('.',$branch);
			foreach ($branch as $node) {
		
			}
		}
		//$tree=implode(',',$tree);
	}
	//$textFinal=implode(';', $trees3);
	*/
	for ($i=0; $i<count($trees3); $i++) {
		for ($j=0; $j<count($trees3[$i]); $j++) {
			for ($k=0; $k<count($trees3[$i][$j]); $k++) {
				if ($k==0) {
					$textFinal=$textFinal.$node;	
				} else {
					$textFinal=$textFinal.'.'.$node;
				}
			}
			if ($j<count($trees3[$i])-1) {
				$textFinal=$textFinal.',';
			}
		}
		if ($j<count($trees3)-1) {
			$textFinal=$textFinal.';';
		}
	}
	return($textFinal);
}

include("init.php");

if (isset($_REQUEST['id'])) {
	$tournamentId=$_REQUEST['id'];
} else {
	exit("L iditenfiant du tournoi n est pas donne.");
}
	
// récupération du tournoi
$result = sendRequest("SELECT * FROM TOURNAMENT WHERE id = '", $tournamentId, "'");
if ($result->num_rows === 0) {
	exit("Ce tournoi n existe pas.");
}
$tournament = $result->fetch_assoc();

$tournament['fighters']=prettyTable4Tournament($tournament['fighters']);

echo json_encode($tournament);

?>
