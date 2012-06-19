<?
/*
 * OVAJ FAJL SLUZI SAMO DA SE REFERIRAM NA KORISTENJE
 * CONTROLLER DIREKTIVA (OVI KOMENTARI IZNAD METODA)
 */

class _Admin extends controller {

  public function __construct() {
    if (!S::role('Admin'))
      exit(header("Location: /"));
  }

  /**
   * < assign: a : &$arg#a
   */
  public function i(Admin $a) {
  }

  /**
   * < assign: a : &$arg#a
   */
  public function roles(Admin $a) {
    return array(
      "roles" => LoginAbility::get()->fetch()
    );
  }

  /**
   * < assign: a : &$arg#a
   */
  public function addOrganiser(Admin $a) {}

  /*
  public function addAdmin(Admin $a) {}
  public function doAddAdmin(Admin $a) {}
   */

  /**
   * doAddOrganiser
   * > post#login_name: min_length:3
   * > post#pwd1: options:$post#pwd2
   *
   * > session#csrf: required,csrf
   * <:redirect
   */
  public function doAddOrganiser(Admin $a) {
    LoginAbility::create($this->post())
      ->destinationRole(
        Organiser::create($this->post())
          ->hibernate()
      )
      ->hibernate();
    return "_Admin/roles/$a->admin_id";
  }

};

<?php

class Login extends controller {

  /**
   * index 
   * < assign: message:$@session#message
   */
  public function index() {
    return array(
      "where" => new LoginPage()
    );
  }

  /**
   * in
   * > session#csrf: required, csrf
   * <:redirect
   */
  public function in() {
    if ($this->post("pin")) {
      S::loginWithPin($this->post('pin'));
    } else {
      S::loginWithPassword(
        $this->post('username'), $this->post('password')
      );
    }

    return "";
  } 

  public function in_ex($ex, $ctx) {
    sleep(2);

    if ($ex instanceof WrongPINException)
      S::get()->message = "wrong or expired PIN!";

    if ($ex instanceof WrongUsernameException)
      S::get()->message = "wrong username!";

    if ($ex instanceof WrongPasswordException)
      S::get()->message = "wrong password!";

    if ($ex instanceof ValidationExp)
      S::get()->message = "your session has expired!";

    S::flash('message', 1);
    return $ctx->redirect("Login");
  }

  /**
   */
  public function qr($hash) {
    if (($LA = LoginAbility::get_by_qr_login_hash()
      ->first($hash)) === false) throw new UrlExp();

    return array(
      "hash" => $hash,
      "la" => $LA->generateTempPin()
    );
  }

  /**
   * <:redirect
   */
  public function qrHere($hash) {
    if (($LA = LoginAbility::get_by_qr_login_hash()
      ->first($hash)) === false) throw new UrlExp();

    if ($LA == false)
      throw new UrlExp();

    S::loginWithLA($LA);

    return "";
  }

  /**
   * logout
   * > session#csrf: required, csrf
   * <:redirect
   */
  public function logout() {
    S::logout();
    S::clear();
    return "";
  }

  public function debug() {
    S::debug();
    exit;
  }
};
<?

class Main extends controller {

  /**
   * <:redirect
   */
  public function index() {
    if (S::isAny() === false)
      return "Login";

    if ($role = S::role('Admin'))
      return $role->url();

    if ($role = S::role('Organiser'))
      return $role->url();

    if ($role = S::role('Salesman'))
      return $role->url();

    if ($role = S::role('Checker'))
      return $role->url();
  } 

};

<?

class _Organiser extends controller {

  public function __construct() {
    if (!S::role('Organiser'))
      exit(header("Location: /"));
  }

  /**
   * < assign: o : &$arg#o
   */
  public function i(Organiser $o) {
    $navigation = array(
      new OrganiseNewThingPage($o)
    );
    if (Event::get('organiser_id = ?')->count($o)) {
      $navigation[] = new BrowseThingsPage($o);
    }
    return array(
      "where" => new OrganiserWelcomePage(),
      "navigation" => $navigation
    );
  }

  /**
   * BrowseThingsPage
   * < assign: o : &$arg#o
   */
  public function BrowseThings(Organiser $o) {
    return array(
      "where" => new BrowseThingsPage($o),
      "es" => Event::get('organiser_id = ?')->fetch($o)
    );
  }

  /**
   * OrganiseNewThingPage
   * < assign: o : &$arg#o
   */
  public function newStuff(Organiser $o) {
    return array(
      "where" => new OrganiseNewThingPage($o),
      "navigation" => array(
        new OrganiseNewInfoPage($o),
        new OrganiseNewMemberCheckingPage($o),
        new OrganiseNewEventPage($o)
      )
    );
  }

  /* ------------------------------------------- */
  /* INFO ORGANISER ---------------------------- */
  /* ------------------------------------------- */

  /**
   * NewInfo
   * < assign: o : &$arg#o
   */
  public function NewInfo(Organiser $o) {
    return array(
      "where" => new OrganiseNewInfoPage($o)
    );
  }

  /**
   * do_NewInfo
   * > session#csrf: required, csrf
   * <: redirect
   */
  public function do_NewInfo(Organiser $o) {
    return new ManageInfos(
      $o,
      Event::create($o, $this->post())
        ->hibernate()
        ->createDefaultEtt($this->post('et'))
        ->hibernate()
    );
  }
  public function do_NewInfo_ex($ex, $ctx) {
    exit('doslo je do exceptiona '. $ex);
  }


  /**
   * ManageInfos
   * < assign: o : &$arg#o
   * < assign: e : &$arg#e
   */
  public function ManageInfos(Organiser $o, Event $e) {
    return array(
      "where" => new ManageInfos($o, $e),
      "navigation" => array(
        new ManageInfoTemplates($o, $e),
        new AddInfoPage($o, $e),
        new AllInfos($o, $e)
      )
    );
  }

  /**
   * Gets XSL for some event ticket type.
   * TODO tko smije vidjeti XSL za ovaj ett? (samo org.)
   * za ticket ce biti drugi.
   *
   * <:raw
   */
  public function TemplateXSL(Organiser $o, EventTicketType $ett) {
    header("Content-type: text/xml");
    return $ett->getPreparedXSL();
  }

  /**
   * PreviewInfoTemplate
   * < assign: o : &$arg#o
   * < assign: ett : &$arg#ett
   * <:raw
   */
  public function PreviewInfoTemplate(Organiser $o, EventTicketType $ett) {

    header("Content-type: text/xml");
    return $ett->getPreviewXMLData();
  }


  /**
   * ManageInfoTemplates
   * < assign: o : &$arg#o
   * < assign: e : &$arg#e
   */
  public function ManageInfoTemplates(Organiser $o, Event $e) {
    // TODO tu smo stali. javascript!
    return array(
      "where" => new ManageInfoTemplates($o, $e),
      "etts" => EventTicketType::get('event_id = ?')->fetch($e)
    );
  }

  /**
   * do_AddInfoTemplate 
   * < assign: o : &$arg#o
   * < assign: e : &$arg#e
   * <:redirect
   */
  public function do_AddInfoTemplate(Organiser $o, Event $e) {
    EventTicketType::create($e, $this->post('radni_naziv'))
      ->hibernate();
    return new ManageInfoTemplates($o, $e);
  }
  public function do_AddInfoTemplate_ex($ex, $ctx) {
    exit("doslo je do exceptiona $ex");
  }



  /**
   * AddInfo 
   * < assign: o : &$arg#o
   * < assign: e : &$arg#e
   */
  public function AddInfo(Organiser $o, Event $e) {
    return array(
      "where" => new AddInfoPage($o, $e)
    );
  }


};

<?php

class Qr extends controller {

  const CACHE_DIR = "../app/cache/";

  /**
   * test
   * url is base64_encoded url.
   *
   * <:raw
   */
  public function gen($url) {
    $file = self::CACHE_DIR . md5($url);

    if (!file_exists($file)) {
      $esc_file = escapeshellarg($file);
      $esc_url = escapeshellarg(base64_decode($url));
      system("qrencode --output=$esc_file --level=H --size=5 --margin=10 $esc_url");
    }

    header("Content-type: image/png");
    fpassthru(fopen($file,"r"));
  }

  public function rnd() {
    return $this->gen(
      base64_encode(md5("sdf"))//MRnd::generateHash())
    );
  }

};
