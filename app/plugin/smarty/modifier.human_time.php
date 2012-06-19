<?

class _rasp_time_helper {
  
  private static  $dani = array(
      "Nedjelja","Ponedjeljak","Utorak","Srijeda",
      "Četvrtak","Petak","Subota"
    );

  static function humanTime($t) {return date("j.n.Y, H:i", $t);}
  static function humanTimeSecond($t) {return date("j.n.Y, H:i:s", $t);}

  static function humanDate($t) {return date("j.n.Y", $t);}
  static function humanDan($t) {return self::$dani[ date("w", $t) ];}
  static function getPodne($t) {return strtotime(date('Y-m-d', $t)) + 12*3600;}
  // ovo radi samo u jednoj godini, lol.
  static function d2int($t) { return idate('z', $t); }

  static function prijeK($t) {
    $time = time();
    $danas = self::d2int($time);
    $podne = self::getPodne($time);
    $unixtojd = self::d2int($t);

    if ($time < $t) return self::humanTime($t);
    if ($time - $t < 15) return "sada";
    if ($time - $t < 60) return "ovu minutu";
    if ($time - $t < 60*4) return "prije 2-3 min";
    if ($time - $t < 60*6) return "prije " . intval(($time-$t)/60) . " min";
    if ($time - $t < 2*3600) return "prije 1 sat";
    if ($time - $t < 2.5*360) return "prije sat ipo";
    if ($time - $t < 3*3600) return "prije 2 sata";
    if ($time - $t < 4*3600) return "prije 3 sata";
    if ($time - $t < 5*3600) return "prije 4 sata";
    if ($time - $t < 6*3600) return "prije 5 sati";
    if ($time - $t < 7*3600) return "prije 6 sati";
    if ($time - $t < 8*3600) return "prije 7 sati";

    if ($danas == $unixtojd && $t < $podne - 7*3600)
      return "sinoć, ". date("H:i", $t );
    if ($danas == $unixtojd && $t < $podne - 1*3600)
      return "jutros, ". date("H:i", $t );
    if ($danas == $unixtojd)
      return "danas, ". date("H:i", $t );

    if ($danas - $unixtojd == 1)
      return "jučer, " . date("H:i", $t );
    if ($danas - $unixtojd < 7)
      return self::humanDan($t) . ", " . date("H:i", $t );
    if (date("Y", $t) == date("Y"))
      return date("j.n H:i", $t );

    return self::humanTime($t);
  }

  static function prijeKoliko($t = null, $title = true) {
    if ($title)
      return "<span class=\"prije_koliko\" title=\""
      . self::humanTime($t) . "\">" . self::prijeK($t) . "</span>";
    else return self::prijeK($t);
  }

}

function smarty_modifier_human_time($t, $abs = false) {
  if (is_callable(array($t, 'getTimestamp')))
    $t = $t->getTimestamp();

  if ($abs == 1)
    return _rasp_time_helper::humanTime($t);

  if ($abs == 2)
    return _rasp_time_helper::humanTimeSecond($t);

  return _rasp_time_helper::prijeKoliko($t, true);
}

