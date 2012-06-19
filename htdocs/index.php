<?php

if (file_exists("index_override.php")) {
  require "index_override.php";
  exit;
}

require "/home/agrbin/share/moj/src/moj.php";
define('HTTP_ROOT', 'http://p4.tel.fer.hr/~agrbin/mrasp');

function app() {return app::get_instance();}

chdir(implode("/",array_slice(explode("/", __FILE__), 0, 
-1)));
// sada je current dir je /htdocs/ (dir od ove skripte)

$config = (array(
  
  "lib" => array(
    "path" => array(
      "../app/plugin/",
      "../app/model/"
    ),
    "require" => array(
      "../aapp/plugin/tuna.php"
    )
  ),

  "session_start" => true,

  "router" => array(
    "doc_root" => "/~agrbin/mrasp/",
    "http_root" => HTTP_ROOT
  ),

  "loader" => array(
    "explicit" => array(
      "Smarty" => "/usr/share/php/smarty3/Smarty.class.php"
    )
    
  ),

  "log" => array(
    "default" => array(
      "echo_format" => false,
      "file_append" => false,
      "file_silent" => 1,
      "file_dir" => "../app/cache/",
      "file_name" => "log.txt",
      "file_format" => "%u %l: %s"
    ),
  ),

  "controller" => array(
    "path" => "../app/controller/",
    "default" => "landing",
    "default_action" => "index",

    "prepend_directives" => array(
      '< tpl: $ctrl#class . / . $ctrl#method',
      '< err: $ctrl#method . _ex'
    ),

    "append_directives" => array(
    )
  ),

  "validator" => array(
    "custom" => array("rasp_valid")
  ),

  "output" => array(

    "defaults" => array(
      "output" => "layout"
    ),

    "layout" => array(

      "assign" => array(
        "root" => "/",
        "__css" => array(
          "static/css/mobile.css",
          "static/css/generic.css",
          "static/css/layout.css",
          "static/css/table.css",
          "static/css/form.css",
          "static/css/header.css"
        ),
        "__js" => array(
          "static/js/jquery-1.6.2.min.js",
          "static/js/main.js"
        ),
        
        "__js_code" => "function root(){return '/';}"
      ),

      "settings" => array(
        "error_reporting" => E_ALL,
        "template_dir" => "../app/view/",
        "compile_dir" => "../app/cache/",
      ),
      "layout" => "main",
      "tpl_suffix" => ".tpl"
    )
  )
));

function out($ex) {
  // ova provjera stoji ovdje da developeru ispise
  // vise detalja o pogresci.
  if (0 && S::role('Admin')) {
    return (string) $ex;
  } else {
    return $ex->getMessage();
  }
}

try {
  new App($config);
} catch (UrlExp $ex) {
  echo "<h1>Not found</h1>";
  echo "<pre>".out($ex)."</pre>";
} catch (ValidationExp $ex) {
  echo "<h1>Input validation failed</h1>";
  echo "<pre>".out($ex)."</pre>";
} catch (SqlErr $ex) {
  echo "<h1>Ali!</h1>";
  echo "<pre>".out($ex);
  echo "\n". (string) $ex->getQuery();
  echo "</pre>";
} catch (exception $ex) {
  echo "<h1>Ali!</h1>";
  echo "<pre>".out($ex)."</pre>";
}


