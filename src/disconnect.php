<?php

session_start();
unset($_SESSION['pokeprof_token']);

if (isset($_REQUEST['go'])) {
    header('Location: '.urldecode($_REQUEST['go']));
} else if (isset($_REQUEST['closeafter'])) {
    echo("<script>window.close();</script>");
} else if (isset($_REQUEST['back'])) {
    echo("<script>window.location.replace(document.referrer);</script>");
} else {
    header('Location: .');
}

?>