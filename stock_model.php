<?php
class Stock_model extends CI_Model {

	public function __construct()
	{
		// Databases
		$this->db = $this->load->database('default', TRUE);

		// Libraries
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->library("defaultclass");

		$this->domain = getenv('HTTP_HOST');
		$this->user_id = $this->tank_auth->get_user_id();
	}


	public function guardar_almacen($id,$nombre,$codigo,$direccion,$geolocalizacion){
		$valores = array
			(
				'nombre'          => $nombre,
				'codigo'          => $codigo,
				'direccion'       => $direccion,
				'geolocalizacion' => $geolocalizacion,
			);
		($id == NULL ? $this->db->insert('CRM_almacen', $valores) : $this->db->update('CRM_almacen', $valores, array('id' => $id)) );
	}

	public function get_almacen_byid($id){
		if($id){
			$sql = "SELECT * FROM CRM_almacen WHERE id='$id'";
			$result = $this->db->query($sql);
			return $result->row_array();
		}else{
			return 0;
		}
	}

	public function delete_almacen($id){
		$sql = "SELECT id,stock FROM CRM_producto_almacen_stock WHERE almacen_id='$id'";
		$res = $this->db->query($sql);
		$num = $res->num_rows();
		if($num > 0){
			$crm_prod = $res->result_array();
			foreach($crm_prod as $item){
				if($item["stock"] > 0){
					return false;
				}
			}
			$query = "DELETE FROM CRM_producto_almacen_stock WHERE almacen_id ='$id'";
			$this->db->query($query);

		}
		$query = "DELETE FROM CRM_almacen WHERE id='$id'";
		$this->db->query($query);
		return true;
	}

	public function get_stock_almacen_list($almacen_id = NULL){
		if($this->almacen_term) {
			$this->db->like('nombre',$this->almacen_term,'none');
		}
		if($almacen_id > 0) {
			$query = $this->db->get_where('CRM_almacen',array('id' => $almacen_id));
		} else {
			$query = $this->db->get('CRM_almacen');
		}
		return $query->result_array();

	}

	public function set_stock($variables) {

		$almacen_id  = $variables['almacen_id'];
		$producto_id = $variables['producto_id'];
		$cantidad    = $variables['stock_cantidad'];
		$stock_id    = $variables['stock_id'];
		$ref_tipo    = $variables['ref_tipo'];
		$ref_id      = $variables['ref_id'];
		$ref_item_id = $variables['ref_item_id'];
		$descripcion = $variables['descripcion'];
		$atributos   = $variables['atributos'];

		// Solo para asignacion de stock en documentos
		$venta_detalle_id           = $variables['venta_detalle_id'];
		$presupuesto_presupuesto_id = $variables['presupuesto_presupuesto_id'];

		// Chequeos necesarios
		if( (!$producto_id) OR ($producto_id <= 0) ) { // Si no tengo el producto id regresar
			return -1;
		}
		if( (!$almacen_id) OR ($almacen_id <= 0)) { // Si no tengo el almacen id trato de averiguarlo
			if($stock_id > 0) {
				$busqueda = $this->db->get_where('CRM_producto_almacen_stock',array('id' => $stock_id),1);
				if( $busqueda->num_rows() > 0) {
					$busqueda = $busqueda->row();
					$almacen_id = $busqueda->almacen_id;
				} else {
					return -2;
				}
			} else {
				return -2;
			}
		}
		if( (!$stock_id) OR ($stock_id < 0) ) { // Si el stock_id viene vacio, lo pongo en 0
			$stock_id = 0;
		}
		if( !$ref_tipo ) { // Si no tengo el tipo de movimiento regresar
			return -5;
		}
		if( (!$ref_id) OR ($ref_id <= 0) ) { // Si no tengo alguna id de referencia, no grabarla
			$ref_id = NULL;
		}
		if( (!$ref_item_id) OR ($ref_item_id <= 0) ) { // Si no tengo alguna id de detalle, no grabarla
			$ref_item_id = NULL;
		}
		if( !$descripcion ) { // Si no tengo alguna descripcion, no grabarla
			$descripcion = NULL;
		}

		$time = time();

		// Si encuentro stock_id, actualizo el registro de stock (cantidad de producto)
		if($busqueda = $this->check_stock_id($stock_id)) {

			$cantidad_anterior = $busqueda[0]['stock'];
			$cantidad_nueva = $cantidad_anterior + $cantidad;

			if ($cantidad_nueva < 0 ) { return -3; } // La nueva cantidad no puede quedar en negativo

			$stock_info = array
				(
					'stock'       => ( $cantidad_nueva > 0 ? $cantidad_nueva : 0 ),
					'time'        => $time,
					'user_id'     => $this->user_id,
				);

			$this->db->update('CRM_producto_almacen_stock',$stock_info,array('id' => $stock_id));


		} else { // Si no tengo stock_id, creo uno nuevo y guardo los atributos nuevos.

			$cantidad_anterior = 0;

			if($cantidad <= 0) { return -4; } // La cantidad no puede ser menor o igual a 0

			$stock_info = array
				(
					'stock'       => ( $cantidad > 0 ? $cantidad : 0 ),
					'time'        => $time,
					'producto_id' => $producto_id,
					'almacen_id'  => $almacen_id,
					'user_id'     => $this->user_id,
				);

			$this->db->insert('CRM_producto_almacen_stock',$stock_info);
			$stock_id = $this->db->insert_id();

			//Guardo los nuevos atributos
			if( sizeof($atributos) > 0 ) {

				foreach ($atributos as $key => $value) {

					if($value['valor_id'] > 0) { // Si tengo valor de atributo guardar

						$stock_atributos = array
							(
								'CRM_producto_almacen_stock_id' => $stock_id,
								'stock_atributo_id'             => $value['atributo_id'],
								'stock_valor_id'                => $value['valor_id'],
							);

						$this->db->insert('CRM_producto_almacen_stock_atributo',$stock_atributos);

					}

				}

			}

		}

		$stock_movimiento = array
			(
				'ref_tipo'       => $ref_tipo,
				'ref_id'         => $ref_id,
				'time'           => $time,
				'user_id'        => $this->user_id,
				'stock_movido'   => $cantidad,
				'stock_anterior' => $cantidad_anterior,
				'producto_id'    => $producto_id,
				'almacen_id'     => $almacen_id,
				'stock_id'       => $stock_id,
				'descripcion'    => $descripcion,
			);

		$this->db->insert('CRM_producto_mov_stock',$stock_movimiento);

		if( ($ref_tipo == 'venta') OR ($ref_tipo == 'compra')) {

			$this->db->from('CRM_producto_almacen_stock_asignado AS SA');
			$this->db->where('SA.ref_id',$ref_item_id);
			$this->db->where('SA.ref_tipo',$ref_tipo);
			$this->db->where('SA.stock_id',$stock_id);
			$resultado = $this->db->get();

			$resultado = $resultado->row_array();

			$nueva_cantidad_asignada = ( ($resultado['stock_asignado']) ? $resultado['stock_asignado'] : 0 ) + ($cantidad * -1);

			$asignar_stock = array
				(
					'ref_tipo'       => $ref_tipo,
					'ref_id'         => $ref_item_id, // Guarda el detalle id
					'stock_asignado' => $nueva_cantidad_asignada,
					'stock_id'       => $stock_id,
				);
			if(sizeof($resultado) > 0) {

				if($nueva_cantidad_asignada > 0) {

					$this->db->update('CRM_producto_almacen_stock_asignado',$asignar_stock,array('id' => $resultado['id']));

				} else { // Si la cantindad total de stock asignado queda en 0, borro el registro

					$this->db->delete('CRM_producto_almacen_stock_asignado',array('id' => $resultado['id']));
				}

			} else {

				$this->db->insert('CRM_producto_almacen_stock_asignado',$asignar_stock);
			}
		}

		return TRUE;
	}

	// en uso
	public function check_stock_id($id) {
		if($id > 0 ) {
			$busqueda = $this->db->get_where('CRM_producto_almacen_stock',array('id' => $id));
			if($busqueda->num_rows() > 0 ) {
				return $busqueda->result_array();
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}

	//en uso
	public function remove_stock_id($stock_id) {
		// @TODO NO PERMITIR EL BORRADO DE STOCK ID SI TENEMOS ASIGNADO DE ESE STOCK ID

		$time = time();

		$busqueda = $this->db->get_where('CRM_producto_almacen_stock',array('id' => $stock_id),1);
		$busqueda = $busqueda->row_array();

		$cantidad_anterior = $busqueda['stock'];
		$almacen_id        = $busqueda['almacen_id'];
		$producto_id       = $busqueda['producto_id'];

		$stock_movimiento = array
			(
				'ref_tipo'       => 'eliminacion',
				'ref_id'         => NULL,
				'time'           => $time,
				'user_id'        => $this->user_id,
				'stock_movido'   => $cantidad_anterior * -1,
				'stock_anterior' => $cantidad_anterior,
				'producto_id'    => $producto_id,
				'almacen_id'     => $almacen_id,
				'stock_id'       => NULL,
				'descripcion'    => 'Eliminacion de Stock',
			);

		$this->db->insert('CRM_producto_mov_stock',$stock_movimiento);

		$this->db->delete('CRM_producto_almacen_stock',array('id' => $stock_id));
		$this->db->delete('CRM_producto_almacen_stock_atributo',array('CRM_producto_almacen_stock_id' => $stock_id));
		if($this->db->affected_rows() > 0) {
			return 1;
		} else {
			return -1;
		}
	}

	//en uso nueva
	public function return_stock_by_prod_id($producto_id,$almacen_id) {
		$resultado = $this->db->get_where('CRM_producto_almacen_stock',array('producto_id' => $producto_id, 'almacen_id' => $almacen_id));
		return $resultado->row_array();
	}

	//en uso nueva
	public function return_stock_by_product($atributo_id,$producto_id,$stock_id) {
		$this->db->from('CRM_producto_almacen_stock as AStock');
		if(sizeof($producto_id) > 0) {
			$this->db->where('AStock.producto_id',$producto_id);
		}
		if(sizeof($stock_id) > 0) {
			$this->db->where('AStock.id',$stock_id);
		}
		if(sizeof($atributo_id) > 0) {
			$this->db->where('SAtributo.stock_atributo_id',$atributo_id);
		}
		$this->db->join('CRM_producto_almacen_stock_atributo AS SAtributo', 'AStock.id = SAtributo.CRM_producto_almacen_stock_id','LEFT');

		$resultado = $this->db->get();
		return $resultado->result_array();
	}
	//en uso nueva
	public function return_stock_list($parametros) {
		$this->db->select('AStock.*, AStock.id AS stock_id, SAtributo.CRM_producto_almacen_stock_id AS stock_atributo, SAtributo.stock_atributo_id AS atributo_id,
			SAtributo.stock_valor_id AS valor_id, Atributo.nombre AS A_nombre, Atributo.descripcion AS A_descripcion, Atributo.orden AS A_orden,
			Atributo.tipo AS A_tipo, Atributo.modo_busqueda AS A_modo_busqueda, Valor.atributo_id AS V_atributo_id, Valor.valor AS V_valor,
			Valor.orden AS V_orden, Valor.diferencia AS V_diferencia, Valor.tipo_dif AS V_tipo_dif');

		$this->db->from('CRM_producto_almacen_stock AS AStock');

		if(sizeof($parametros['almacen_id']) > 0) {
			$this->db->where('AStock.almacen_id', $parametros['almacen_id']);
		}
		if(sizeof($parametros['producto_id']) > 0) {
			$this->db->where('AStock.producto_id',$parametros['producto_id']);
		}
		if(sizeof($parametros['stock_id']) > 0) {
			$this->db->where('AStock.id',$parametros['stock_id']);
		}
		if(sizeof($parametros['atributo_id']) > 0) {
			$this->db->where('SAtributo.stock_atributo_id',$parametros['atributo_id']);
		}
		$this->db->join('CRM_producto_almacen_stock_atributo AS SAtributo', 'AStock.id = SAtributo.CRM_producto_almacen_stock_id','LEFT');
		$this->db->join('CRM_atributo AS Atributo','SAtributo.stock_atributo_id = Atributo.id', 'LEFT');
		$this->db->join('CRM_atributo_valor AS Valor','SAtributo.stock_valor_id = Valor.id', 'LEFT');
		$resultado = $this->db->get();
		$resultado = $resultado->result_array();

		$return_array = array();

		foreach ($resultado as $key => $value) {
			$stock_id = $value['stock_atributo'];
			if(!isset($return_array[$stock_id])) {
				$return_array[($stock_id > 0 ? $stock_id : $key)] = array();
				$return_array[($stock_id > 0 ? $stock_id : $key)]['stock_id'] = $value['stock_id'];
				$return_array[($stock_id > 0 ? $stock_id : $key)]['almacen_id']  = $value['almacen_id'];
				$return_array[($stock_id > 0 ? $stock_id : $key)]['producto_id'] = $value['producto_id'];
				$return_array[($stock_id > 0 ? $stock_id : $key)]['stock'] = $value['stock'];
				if($stock_id > 0) {
					$return_array[$stock_id]['atributos'] = array();
				}
			}
			if($stock_id > 0) {
				$return_array[$stock_id]['atributos'][$key]['atributo_id']     = $value['atributo_id'];
				$return_array[$stock_id]['atributos'][$key]['valor_id']        = $value['valor_id'];
				$return_array[$stock_id]['atributos'][$key]['A_nombre']        = $value['A_nombre'];
				$return_array[$stock_id]['atributos'][$key]['A_descripcion']   = $value['A_descripcion'];
				$return_array[$stock_id]['atributos'][$key]['A_orden']         = $value['A_orden'];
				$return_array[$stock_id]['atributos'][$key]['A_tipo']          = $value['A_tipo'];
				$return_array[$stock_id]['atributos'][$key]['A_modo_busqueda'] = $value['A_modo_busqueda'];
				$return_array[$stock_id]['atributos'][$key]['V_atributo_id']   = $value['V_atributo_id'];
				$return_array[$stock_id]['atributos'][$key]['V_valor']         = $value['V_valor'];
				$return_array[$stock_id]['atributos'][$key]['V_orden']         = $value['V_orden'];
				$return_array[$stock_id]['atributos'][$key]['V_diferencia']    = $value['V_diferencia'];
				$return_array[$stock_id]['atributos'][$key]['V_tipo_dif']      = $value['V_tipo_dif'];
			}

		}

		return $return_array;
	}
	// en uso nueva
	public function calculate_item_stock_price($precio_producto,$atributos = array()) {

		if($precio_producto > 0) {
			if(sizeof($atributos) > 0) {
				foreach ($atributos as $key => $attr) {
					switch ($attr['tipo_dif']) {
                            case 'suma':
                                $precio_producto = $precio_producto + $attr['diferencia'];
                                break;
                            case 'resta':
                                $precio_producto = $precio_producto - $attr['diferencia'];
                                break;
                            case 'porcentajesuma':
                                $precio_producto = $precio_producto + ( ($precio_producto * $attr['diferencia']) / 100 );
                                break;
                            case 'porcentajeresta':
                                $precio_producto = $precio_producto - ( ($precio_producto * $attr['diferencia']) / 100 );
                                break;
                        }
				}
			}
		}

		return $precio_producto;

/* Este es el formato del array que debe recibir
Array
(
    [0] => Array
        (
            [atributo_id] => 7
            [valor_id] => 9
            [diferencia] => 0
            [tipo_dif] => suma
        )

    [1] => Array
        (
            [atributo_id] => 8
            [valor_id] => 12
            [diferencia] => 20
            [tipo_dif] => resta
        )

)*/
	}

	public function get_stock($params){
		$sql = "SELECT stock FROM CRM_producto_almacen_stock WHERE";

		if($params["almacen_id"]){
			$almacen_id = $params["almacen_id"];
			$sql.= " almacen_id = '$almacen_id'";
		}

		if(($params["producto_id"])&&($params["almacen_id"])){
			$sql.= " AND";
		}

		if($params["producto_id"]){
			$producto_id = $params["producto_id"];
			$sql.= " producto_id ='$producto_id'";
		}

		if((!$params["producto_id"])&&(!$params["almacen_id"])){
			return -1;
		}

		$result = $this->db->query($sql);
		$num = $result->num_rows();
		if($num == 0){
			return 0;
		}else{
			if($num == 1){
				$row = $result->row_array();
				return $row["stock"];
			}else{
				$arr_aux = $result->result_array();
				$stock = 0;
				foreach ($arr_aux as $row){
					$stock += $row["stock"];
				}
				return $stock;

			}

		}


	}
	public function __get_movimientos_stock($parametro = array()) {

		$this->db->select();
		$this->db->from('CRM_producto_mov_stock as MS');

		if(sizeof($parametro['almacen_id']) > 0) {
			$this->db->where('MS.almacen_id',$parametro['almacen_id']);
		}

		if(sizeof($parametro['producto_id']) > 0) {
			$this->db->where('MS.producto_id',$parametro['producto_id']);
		}

		if(sizeof($parametro['time_desde']) > 0) {
			$this->db->where('MS.time >',$parametro['time_desde']);
		}

		if(sizeof($parametro['time_hasta']) > 0) {
			$this->db->where('MS.time <',$parametro['time_hasta']);
		}

		$this->db->join('CRM_almacen as A','MS.almacen_id = A.id');
		$this->db->join('CRM_producto as P','MS.producto_id = P.id');
		$this->db->join('users as U','MS.user_id = U.id');
		$this->db->order_by('MS.time','DESC');

		$resultado = $this->db->get();
		return $resultado->result_array();

	}
	// Obsoleto - REMOVER
	public function get_movimientos_stock($params = null){
		$where = "1";
		if(isset($params["almacen_id"])){
			$where = " MS.almacen_id = '".$params["almacen_id"]."'";
		}
		if(isset($params["producto_id"])){
			if($where != 1){
				$where .= "AND ";
			}
			$where.= " MS.producto_id = '".$params["producto_id"]."'";
		}
		if(isset($params["time_desde"])){
			if($where != 1){
				$where .= "AND ";
			}
			$where.= " MS.time > '".$params["time_desde"]."'";
		}
		if(isset($params["time_hasta"])){
			if($where != 1){
				$where .= "AND ";
			}
			$where.= " MS.time < '".$params["time_hasta"]."'";
		}
		//(stock_anterior,movimiento,time,user_id,almacen_id,producto_id)
		$sql = "SELECT U.username,P.etiqueta,A.nombre,MS.* FROM CRM_producto_mov_stock MS
		INNER JOIN CRM_almacen A ON MS.almacen_id = A.id
		INNER JOIN CRM_producto P ON MS.producto_id = P.id
		INNER JOIN users U ON MS.user_id = U.id
		WHERE $where ORDER BY time DESC";
		$result = $this->db->query($sql);
		return $result->result_array();
	}

}