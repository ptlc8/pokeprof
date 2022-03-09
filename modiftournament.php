<?php

include('init.php');
include("functiontournament.php");

$erreur=0;

if ((isset($_REQUEST))&&($_REQUEST!=null)) {
    if (isset($_REQUEST['limitBool'])) {
        sendRequest("UPDATE TOURNAMENT SET nbPlaces=", $_REQUEST['nb_places'], " WHERE id='", $_REQUEST['idTournament'], "'");
    } else {
        sendRequest("UPDATE TOURNAMENT SET nbPlaces=NULL WHERE id='", $_REQUEST['idTournament'], "'");
    }
    
    $draftType='0';
    if (isset($_REQUEST['draftBool'])) {
        if ($_REQUEST['status']=="manual") {
            $draftType=$_REQUEST['drafts'];
        } else if ($_REQUEST['status']=="half") {
            $draftType=-2;
        } else if ($_REQUEST['status']=="quarter") {
            $draftType=-4;
        } else if ($_REQUEST['status']=="eight") {
            $draftType=-8;
        } else if ($_REQUEST['status']=="all") {
            $draftType=null;
        } else {
            $erreur=1;
        }
        if ($draftType!=null) {
            sendRequest("UPDATE TOURNAMENT SET draft=", $draftType," WHERE id='", $_REQUEST['idTournament'], "'");
        } else {
            sendRequest("UPDATE TOURNAMENT SET draft=NULL WHERE id='", $_REQUEST['idTournament'], "'");
        }
    } else {
        sendRequest("UPDATE TOURNAMENT SET draft=0 WHERE id='", $_REQUEST['idTournament'], "'");
    }
    
    if ($_REQUEST["now"]=="inscriptions") {
        if ($_REQUEST['nb_places']<=0) {
            sendRequest("UPDATE TOURNAMENT SET nbPlaces=NULL WHERE id='", $_REQUEST['idTournament'], "'");
        } else {
            sendRequest("UPDATE TOURNAMENT SET nbPlaces=", $_REQUEST['nb_places'], " WHERE id='", $_REQUEST['idTournament'], "'");
        }
    } else if ($_REQUEST["now"]=="started") {
        $tree = sendRequest("SELECT fighters FROM TOURNAMENT WHERE id = '", $_REQUEST['idTournament'], "'")->fetch_assoc()['fighters'];
        $tree=parseFighters(prettyTable4Tournament(parseFighters($tree)));
        //afficher les VS
        $tree=prettyDraft4Tournament($tree, $draftType);
        sendRequest("UPDATE TOURNAMENT SET fighters='", $tree,"' WHERE id='", $_REQUEST['idTournament'], "'");
        
        sendRequest("UPDATE TOURNAMENT SET nbPlaces=0 WHERE id='", $_REQUEST['idTournament'], "'");
    } else if ($_REQUEST["now"]=="ended") {
        sendRequest("UPDATE TOURNAMENT SET nbPlaces=-1 WHERE id='", $_REQUEST['idTournament'], "'");
    } else {
        $erreur=1;
    }
    
    if ($erreur==0) {
        echo "Modifications faites avec succès ! <br /><br /><a href='tournament.php?id=".$_REQUEST['idTournament']."'>Retourner à la page du tournoi</a>";
    } else {
        echo "Une erreur s'est produite! <br /><br />";
        var_dump($_REQUEST);
        echo "<br /><br /><a href='tournament.php?id=".$_REQUEST['idTournament']."'>Retourner à la page du tournoi</a>";
    }
    
    
}

?>