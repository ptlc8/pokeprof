<?php

include('api/init.php');

header('Location: '.POKEPROF_CONNECT_URL.urlencode($_SERVER['QUERY_STRING']));

?>