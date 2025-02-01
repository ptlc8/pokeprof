<?php

include('api/init.php');

header('Location: '.get_config('PORTAL_CONNECT_URL').urlencode($_SERVER['QUERY_STRING']));

?>