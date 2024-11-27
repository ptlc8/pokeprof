<?php

include('api/init.php');

header('Location: '.PORTAL_CONNECT_URL.urlencode($_SERVER['QUERY_STRING']));

?>