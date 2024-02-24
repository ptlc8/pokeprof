<?php

session_start();
unset($_SESSION['pokeprof_token']);

if (isset($_REQUEST['go'])) {
    echo("<script>window.location.replace(decodeURIComponent('".$_REQUEST['go']."'))</script>");
} else if (isset($_REQUEST['closeafter'])) {
    echo("<script>window.close();</script>");
} else if (isset($_REQUEST['back'])) {
    echo("<script>window.location.replace(document.referrer);</script>");
} else {
    echo("<script>window.location.replace('.');</script>");
}

?>