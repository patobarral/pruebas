<?php
class Www extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('tank_auth');
		$this->load->library('session');
		$this->load->model('/dashboard/dashboard_model');
		$this->load->library('defaultclass');
		$this->load->helper('url');

		error_reporting(E_ALL & ~ (E_NOTICE | E_WARNING));
	}

	public function index()
	{
			$this->session->set_userdata('pagina_anterior', current_url()."?".http_build_query($_GET));

			include_once(ABSOLUTE_PATH."/application/controllers/admin/functions.php");

			$url = uri_string();
			$url_explode = explode("/",$url);


			$_PARAMS = Array();

			// Parametros de url friendly
			$cont = 1;
			foreach($url_explode AS $item){
				if($cont == 1){
					$page_controller = $item;
				}else if($cont == 2){
					$page = $item;
				}else if($cont == sizeof($url_explode)){
					$page_urlfriendly = $item;
				}else{
					if(($cont % 2) != 0){$item_label = $item;}else{$_PARAMS["$item_label"] = $item;}
				}
				$cont ++;
			}
			if((!$page) && (($page_controller == 'www') || ($page_controller =='')) ){ header("Location: /www/index"); exit();}

			// Parametros de usuario
			$_PARAMS['system_user_id'] = $this->tank_auth->get_user_id();
			$_PARAMS['system_user_name'] = $this->tank_auth->get_username();


			$this->dashboard_model->page_params = $_PARAMS;

			// Imprimimos la pagina
			$this->dashboard_model->page_print($page);
			exit();
	}

}
