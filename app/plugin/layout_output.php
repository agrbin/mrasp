<?

class layout_output implements output_if {

  private $opt;
  private $smarty;

  private function get_opt($key, $default = '') {
    array_default($this->opt[$key]);
    $t = end($this->opt[$key]);
    return empty_default($t, $default);
  }

  public function __get($key) {
    return $this->get_opt($key);
  }

  public function __construct($o = array(), $c = "") {
    $this->mobileDetect = new Mobile_Detect();
    $this->opt = $o;
    $this->smarty = new Smarty();

    foreach (array_default($o['settings']) as $key => $v)
      $this->smarty->$key = $v;

    $this->smarty->assign(array_default($o['assign']));
    $this->smarty->loadFilter('variable','htmlspecialchars');
  }

  public function error(exception $ex) {
    exit($ex);
  }

  public function execute($data) {
    if (!$this->tpl)
      throw new Exception("tpl not specified!");

    $this->smarty->assign(
      '__mobile', $this->mobileDetect->isMobile()
    );

    $this->smarty->assign(
      '__tablet', $this->mobileDetect->isTablet()
    );

    if (isset($data['where'])) {
      $this->smarty->assign('__crumbs', $data['where']->getCrumbs());
      $this->tpl = $data['where']->tpl;
    }

    //$this->smarty->assign('csrf', S::getCsrfToken());
    $this->smarty->assign($data);
    $this->smarty->assign("_template", $this->tpl.$this->tpl_suffix);
    $this->smarty->display("layout/$this->layout.layout$this->tpl_suffix");
  }

}

