<?php
    include('init.php');
    $user = login(false, true);
    
    $infoslist = sendRequest("SELECT infos FROM CARDSUSERS WHERE id = '", $user['id'], "'");
    $infoslist= $infoslist -> fetch_assoc();
    $infoslist= $infoslist['infos'];
    
    $newinfos = $infoslist;
    
    foreach(array_keys($_REQUEST) as $key){
        if($key != 'PHPSESSID'){
            if(preg_match('#' . $key . ':[a-zA-Z0-9]+#',$newinfos)){
                $newinfos=preg_replace('/' . $key . ':[^;]+/',$key . ':' . $_REQUEST[$key],$newinfos);
                echo('"'.$key.'"'.' a désormais pour valeur' . $_REQUEST[$key] . '<br/>' );
            }else{ 
                echo('"'.$key.'"'.' n\'existait pas, creation de la valeur<br/>' );
                $newinfos .= ';' . $key . ':' . $_REQUEST[$key];
            }
        }
    }
    
    
    sendRequest("UPDATE `CARDSUSERS` SET `infos`= '" , $newinfos , "' WHERE `id` ='" , $user['id'] , "'" );
    
    //echo '<script>window.close();</script>';
?>