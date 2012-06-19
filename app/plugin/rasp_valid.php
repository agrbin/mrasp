<?php

class rasp_valid implements validator_if {

  private $ex;

  /*
   * public $value, $validation_name, $validation_args;
   * public $validation_desc, $context_desc;
   *
   **/
  private function throw_me() {
    throw $this->ex;
  }

  function test($validation_name, &$value, $args = array()) {

    $this->ex = new ValidationExp("validation failed", "qr_validation");
    $this->ex->value = $value;
    $this->ex->validation_name = $validation_name;
    $this->ex->validation_args = $args;

    if (method_exists($this, $validation_name)) {
      call_user_func_array(
        array($this, $validation_name),
        array_merge(array(&$value), $args)
      );
      return true;
    }
  }

  function csrf($csrf) {
    if (isset($_GET[$csrf]))
      return true;
    if (isset($_POST[$csrf]))
      return true;
    if (isset($_GET['csrf']) && $_GET['csrf'] === $csrf)
      return true;
    if (isset($_POST['csrf']) && $_POST['csrf'] === $csrf)
      return true;

    throw $this->ex;
  }

}


