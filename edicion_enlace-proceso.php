<?
if($this->input->get_post("id")){
	$data["enlace"] = $this->simplecms_model->enlace_info($this->input->get_post("id"));
}

if($this->input->get_post("api") == 'salvar_enlace'){
	$this->simplecms_model->enlace_nombre      = $this->input->get_post("nombre");
	$this->simplecms_model->enlace_descripcion = $this->input->get_post("descripcion");
	$this->simplecms_model->enlace_url         = $this->input->get_post("url");
	$this->simplecms_model->enlace_orden       = $this->input->get_post("orden");
	$this->simplecms_model->enlace_imagen      = $this->input->get_post("imagen");
	$this->simplecms_model->enlace_tipo        = $this->input->get_post("tipo");
	$this->simplecms_model->enlace_parent_id   = $this->input->get_post("parent_id");
	$this->simplecms_model->enlace_target      = $this->input->get_post("target");
	$this->simplecms_model->enlace_onclick     = $this->input->get_post("onclick");
	$this->simplecms_model->enlace_popup_width     = $this->input->get_post("popup_width");
	$this->simplecms_model->enlace_popup_height     = $this->input->get_post("popup_height");
	$this->simplecms_model->enlace_color     = $this->input->get_post("color");
	$this->simplecms_model->enlace_editar($this->input->get_post("id"));
	exit();
}


if($this->input->get_post("api") == 'eliminar_enlace'){
	$this->simplecms_model->enlace_eliminar($this->input->get_post("id"));
	exit();
}

if($this->input->get_post('api') == 'borrar_imagen'){
	$this->load->model('file/file_model');
	$this->file_model->delete_file($this->input->get_post('imagen'));
}

$this->load->model('/dashboard/dashboard_model');
$data["listado_enlaces"] = $this->simplecms_model->enlace_listado_arbol();
if($this->input->get_post("cropimg_width") != ''){ $data["cropimg_width"] = $this->input->get_post("cropimg_width"); } else{ $data["cropimg_width"] = 20; }
if($this->input->get_post("cropimg_height") != ''){ $data["cropimg_height"] = $this->input->get_post("cropimg_height"); } else{ $data["cropimg_height"] = 10; }
?>