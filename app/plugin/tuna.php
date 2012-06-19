<?php

if (0){ 

if (file_exists('../app/plugin/tuna_override.php')) {

  require('../app/plugin/tuna_override.php');

} else {

  define('CONVERT_TYPES', 1);

  require "/home/agrbin/share/tuna/src/tuna.php";

  tuna::init(array(
    "hostname" => "",
    "username" => "qr",
    "database" => "qr",
    "password" => "qr",
    "pconnect" => true
  ));

}

}
