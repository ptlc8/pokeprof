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
    
    if (isset($_REQUEST['draftBool'])) {
        if ($_REQUEST['status']=="manual") {
            sendRequest("UPDATE TOURNAMENT SET draft=", $_REQUEST['drafts']," WHERE id='", $_REQUEST['idTournament'], "'");
        } else if ($_REQUEST['status']=="half") {
            sendRequest("UPDATE TOURNAMENT SET draft=-2 WHERE id='", $_REQUEST['idTournament'], "'");
        } else if ($_REQUEST['status']=="quarter") {
            sendRequest("UPDATE TOURNAMENT SET draft=-4 WHERE id='", $_REQUEST['idTournament'], "'");
        } else if ($_REQUEST['status']=="eight") {
            sendRequest("UPDATE TOURNAMENT SET draft=-8 WHERE id='", $_REQUEST['idTournament'], "'");
        } else if ($_REQUEST['status']=="all") {
            sendRequest("UPDATE TOURNAMENT SET draft=null WHERE id='", $_REQUEST['idTournament'], "'");
        } else {
            $erreur=1;
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