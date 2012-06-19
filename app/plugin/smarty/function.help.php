<?php


function smarty_function_help($params, &$smarty)
{
  $tema_id = $params['tema_id'];

  echo "<a href=\"".
     app()->config('router/doc_root') . 
      "temsija/thread/$tema_id"
  ."\" class=\"hlp\" target=\"_blank\">yelp!</a>";
}
