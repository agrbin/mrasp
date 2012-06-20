<?php

class file_output implements output_if {

  private $o;

  public function __construct($o = array(), $c = "") {
    $this->o = $o;
  }

  public function error(exception $ex) {
    header(isset_default(
      $o['error_header'],
      "Status: 500 Exception"
    ));
    exit($ex);
  }

  public function execute($path) {
    $mtime = ($mtime = filemtime($path)) ? $mtime : gmtime();
		$size = intval(sprintf("%u", filesize($path)));
		
    $headers = array(
      "Content-Description" => "File Transfer",
      "Content-Type" => "application/octet-stream",
      "Content-Transfer-Encoding" => "binary",
      "Expires" => 0,
      "Cache-Control" =>"must-revalidate, post-check=0, pre-check=0",
      "Pragma" => "public",
      "Content-Length" => $size
    );

    if (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE") !== FALSE)
      $headers["Content-Disposition:"] =
        "attachment; filename=" . urlencode($name).
        '; modification-date="' . date('r', $mtime).'";';
    else
      $headers["Content-Disposition"] =
        "attachment; filename=\"".$name.
        '"; modification-date="' . date('r', $mtime) . '";';
		
    $headers = array_merge(
      $this->o['headers'],
      $headers
    );

    foreach ($headers as $key => $value)
      header("$key: $value");
		
    $chunksize = 1 * (1024 * 1024);
		
    set_time_limit(300);
    ob_clean();
		
		if ($size > $chunksize) {
			$handle = fopen($path, 'rb');
      for ($buffer = ''; !feof($handle); ob_flush())
        echo fread($handle, $chunksize);
      fclose($handle);
    } else {
      readfile($path);
    }
  }

}


?>
