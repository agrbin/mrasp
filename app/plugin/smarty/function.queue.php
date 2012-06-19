<?php

function smarty_function_queue($params, &$smarty)
{
  /* CACHE DISABLED */
  $smarty->assign($params);
  $ret = $smarty->fetch('queue.tpl');
  foreach ($params as $key => $null)
    $smarty->assign($key, null);
  if (isset($params['assign'])) {
    $smarty->assign($params['assign'], $ret);
  } else {
    echo $ret;
  }
  return;



  $cpy = $params;
  unset($cpy['this']);
  $mkey = "queue_"
    . ($params['this']->getId())
    . md5(json_encode($cpy));

  if (($ret = moj_mcache::get($mkey)) == false) {
    $smarty->assign($params);
    $ret = $smarty->fetch('queue.tpl');
    foreach ($params as $key => $null)
      $smarty->assign($key, null);
  }

  moj_mcache::set($mkey, $ret, 300);
  if (isset($params['assign'])) {
    $smarty->assign($params['assign'], $ret);
  } else {
    echo $ret;
  }
}
