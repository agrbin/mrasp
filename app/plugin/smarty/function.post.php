<?php

function smarty_function_post($params, &$smarty)
{
  $smarty->assign($params);
  $ret = $smarty->fetch('post.tpl');
  foreach ($params as $key => $null)
    $smarty->assign($key, null);
  echo $ret;
  return;

  $cpy = $params;
  unset($cpy['this']);
  $mkey = "post_"
    . ($params['this']->getId())
    . md5(json_encode($cpy))
    . $params['this']->zasto_nevidim()
    . $params['this']->glasan_od_mene();

  if (($ret = moj_mcache::get($mkey)) == false) {
    $smarty->assign($params);
    $ret = $smarty->fetch('post.tpl');
    foreach ($params as $key => $null)
      $smarty->assign($key, null);
  }

  moj_mcache::set($mkey, $ret, 300);
  echo $ret;
}
