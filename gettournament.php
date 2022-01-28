<?php

//fonctions pour faire des tableaux corrects, Léo

function recurPrettyTable ($tree, $nbQualified, $i, $j) {
	if ($nbQualified<=1) {
		$tree[$i][$j]='_';	//emplacement d'un id de joueur
	}
	else  {
		$tree[$i][$j]=' ';
		recurPrettyTable($tree, intdiv($nbQualified,2)+1, $i-1, $j*2);
		recurPrettyTable($tree, intdiv($nbQualified,2), $i-1, ($j*2)+1);
	}
}

function prettyTable4Tournament ($textFighters) {
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
	$j=0;
	$a=1;
	$fighters=array();
	$fighters[0]=-10;
	//on s'intéresse seulement au tableau principal
	while (($i<count($trees3[0]))&&($j==0)) {
		$k=0;
		while (($k<$a)&&($j==0)) {
			$l=0;
			while (($l<count($trees3[0][$i]))&&($j==0)) {
				if ($fighters[$k]!=$trees3[0][$i][$l]) {
					$fighters[$a]=$trees3[0][$i][$l];
					$a++;
				} else {
					$j=1; //on a un doublon
				}
				$l++;
			}
			$k++;
		}
		$i++;
	}
	if ($j!=0) { //le tournoi a commencé
		return($trees3);
	}
	//modif avec appel récursif
	$trees=array();
	recurPrettyTable ($trees, count($fighters), ceil(log(count($fighters),2)), 0);
	$k=0;
	foreach ($trees as $branch) {
		foreach ($branch as $node) {
			if ($node=='_') {
				$node=$fighters[$k];
				$k++;
			}
		}
	}
	$trees3[0]=$trees;
	return($trees3);
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
