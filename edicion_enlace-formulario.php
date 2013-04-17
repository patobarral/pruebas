<script type="text/javascript" src="/statics/uploadify-v3.1/jquery.uploadify-3.1.min.js"></script>
<script type="text/javascript">
function parseJSON(data) { return window.JSON && window.JSON.parse ? window.JSON.parse( data ) : (new Function("return " + data))(); }

$(document).ready(function() {
              $('#upload_imagen').uploadify({
                    'buttonText': 'Seleccionar archivos...',
                    'swf'      : '/statics/uploadify-v3.1/uploadify.swf',
                    'uploader' : '/file/upload/uploadify',
                    'width'    : 200,
                    'height'   : 50,
                    'preventCaching' : true,
                    'onUploadSuccess' : function(file, data, response) {
                       var datajson = parseJSON(data);
                       $("#imagen").val(datajson.filename);
                       $("#preview_imagen").attr('src','/file/user_files/'+datajson.filename);
                       $("#quitar_imagen").html('<a href="#" onclick="quitar_imagen();">Quitar Imagen</a> <a href="#" onclick="editar_imagen();">Editar imagen</a>');
                       if($("#id").val() != ''){ editar_enlace(2); }
                    }
              })
});


function editar_enlace(accion){
    $.post("/simplecms/process/enlace/edicion_enlace",
             {
              api:'salvar_enlace'
              ,tipo:'url'
              ,id:$("#id").val()
              ,nombre:$("#nombre").val()
              ,descripcion:$("#descripcion").val()
              ,url:$("#url").val()
              ,orden:$("#orden").val()
              ,imagen:$("#imagen").val()
              ,parent_id:$("#parent_id").val()
              ,target:$("#target").val()
              ,onclick:$("#onclick").val()
              ,popup_width:$("#popup_width").val()
              ,popup_height:$("#popup_height").val()
              ,color:$("#color").val()

              },
              function(data){
                  alert('Cambios guardados');
              }
        );

}
function quitar_imagen() {
  $.post("/simplecms/process/enlace/edicion_enlace",
    {
        api:'borrar_imagen',
        imagen:$("#imagen").val()
    },
    function(data){
      $("#imagen").val('');
      $("#src_imagen").attr('src','');
      $("#preview_imagen").attr('src','');
      $("#quitar_imagen").attr('style','display:none');
      editar_enlace(2);
      }
    );
}

function editar_imagen(){
  window.location.href=("/filemanager/dialog/manage_img?img=" + $("#imagen").val() + "&size=<?=$cropimg_width?>x<?=$cropimg_height?>");
}

function seleccionar_target(target){
  if(target == '_popup'){
    $("#div_popup").show();
  }else{
    $("#div_popup").hide();
  }
}
</script>

<div class="container">

    <div class="mws-panel grid_8">
        <div class="mws-tabs">

            <ul>
                <li><a href="#tab-1">Enlace</a></li>
                <li><a href="#tab-2">Opciones avanzadas</a></li>
            </ul>


            <form action='#' class='mws-form' id='mws-validate'>
             <!-- TAB 1 -->
            <div id="tab-1">
                <div class="mws-panel-body">
                   <input type="hidden" name="id" id="id" value="<?=$enlace["id"]?>">
                        <div class="mws-form-inline">
                            <div class="mws-form-row">
                                <label>Nombre</label>
                                <div class="mws-form-item large">
                                    <input name="nombre" id="nombre" value="<?=$enlace["nombre"]?>" type="text" class="mws-textinput" />
                                </div>
                            </div>

                            <div class="mws-form-row">
                                <label>Url</label>
                                <div class="mws-form-item large">
                                    <input name="url" id="url" value="<?=$enlace["url"]?>" type="text" class="mws-textinput" />
                                </div>
                            </div>

                            <div class="mws-form-row">
                                <label>Descripcion</label>
                                <div class="mws-form-item large">
                                    <textarea name="descripcion" id="descripcion" class="mws-textinput" ><?=$enlace["descripcion"]?></textarea>
                                </div>
                            </div>


                          <div class="mws-form-row">
                            <label>Imagen relacionada</label>
                            <div class="mws-form-item large">
                                <input type="file" name="upload_imagen" id="upload_imagen" />
                                <input type="hidden" name="imagen" id="imagen" value='<?=$enlace["imagen_url"]?>' />
                                <img src='' id="preview_imagen" style="max-width:300px;max-height:120px;">
                                <?if(!empty($enlace["imagen_url"])) {?>
                                <img src='/file/user_files/<?=$enlace["imagen_url"]?>' id="src_imagen" style="max-width:300px;max-height:120px;">
                                <div id="quitar_imagen">
                                  <a href="#" onclick="quitar_imagen();">Quitar Imagen</a>

                                  <?if($enlace["imagen_url"] != ''){?>
                                <input type="button" onclick='window.location.href=("/filemanager/dialog/manage_img?img=<?=$enlace["imagen_url"]?>");' value="Opciones de la imagen" class="mws-button blue mws-i-24 i-image-2 large" style="float:right;margin:20px 0 0;">
                                <? } ?>


                                </div>
                                <? } else { ?>
                                <div id="quitar_imagen"></div>
                                <? } ?>
                            </div>
                        </div>


                            <div class="mws-form-row">
                                <label>Orden</label>
                                <div class="mws-form-item small">
                                    <select class="chzn-select" name='orden' id='orden'>
                                        <?
                                        for($a=0;$a<200;$a++){
                                            if($enlace["orden"] == $a){$sel='selected';}else{$sel='';}
                                            echo "<option $sel value=$a>$a</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mws-form-row">
                                <label>Depende de:</label>
                                <div class="mws-form-item small">
                                    <select class="chzn-select" id='parent_id'>
                                        <option value=''></option>
                                        <?
                                        function recursivo($array,$parent_id,$separador){
                                            $new_array = array();
                                            foreach($array AS $item){
                                                if($parent_id == $item["id"]) { $sel=' selected ';}else{$sel='';}
                                                echo "<option $sel value='".$item["id"]."'>$separador ".$item["nombre"]."</option>";
                                                if(sizeof($item["childs"]) > 0){
                                                    recursivo($item["childs"]["enlaces_list"],$parent_id,$separador.">>");
                                                }
                                            }
                                        }
                                        recursivo($listado_enlaces["enlaces_list"],$enlace["enlace_id"],"");
                                        ?>
                                    </select>
                                </div>
                            </div>



                             <div class="mws-button-row">
                                <input type="button" onclick='editar_enlace(1);' value="Guardar" class="mws-button green" />
                            </div>

                            <div style="clear:both;"></div>
                        </div>

                </div>


            </div>

             <!-- TAB 2 -->
            <div id="tab-2">
                <div class="mws-panel-body">
                        <div class="mws-form-inline">
                            <div class="mws-form-row">
                                <label>Abrir enlace en:</label>
                                <div class="mws-form-item small">
                                  <select name="target" id="target" onchange='seleccionar_target(this.value);' >
                                      <option <? if($enlace["target"] == ''){ echo "selected"; } ?> value=''>Misma ventana</option>
                                      <option <? if($enlace["target"] == '_blank'){ echo "selected"; } ?> value='_blank'>Nueva ventana</option>
                                      <option <? if($enlace["target"] == '_popup'){ echo "selected"; } ?> value='_popup'>Popup</option>
                                  </select>
                                </div>
                            </div>

                            <?
                            if($enlace["target"] != '_popup'){ $style_display = "display:none"; }
                            ?>
                          <div id='div_popup' style='<?=$style_display?>'>
                                  <div class="mws-form-row">
                                      <label>Configuracion del popup:</label>
                                      <div class="mws-form-item small">
                                              Ancho:
                                              <input name="popup_width" id="popup_width" value="<?=$enlace["popup_width"]?>" type="text" class="mws-textinput" style='width:100px;' /> Px.
                                              &nbsp;&nbsp;  &nbsp;&nbsp;
                                              Alto:
                                              <input name="popup_height" id="popup_height" value="<?=$enlace["popup_width"]?>" type="text" class="mws-textinput" style='width:100px;' /> Px.
                                      </div>
                                  </div>
                          </div>

                            <div class="mws-form-row">
                                      <label>Color especifico para el menu</label>
                                      <div class="mws-form-item small">
                                          <input name="color" id="color" value="<?=$enlace["color"]?>" type="text" class="mws-textinput mws-colorpicker" style="background-color:#<?=$enlace["color"]?>" />
                                      </div>
                                  </div>

                            <div class="mws-form-row">
                                <label>Accion OnClick</label>
                                <div class="mws-form-item small">
                                    <input name="onclick" id="onclick" value="<?=$enlace["onclick"]?>" type="text" class="mws-textinput " />
                                </div>
                            </div>
                        </div>
                  </div>

                  <div class="mws-button-row">
                                <input type="button" onclick='editar_enlace(1);' value="Guardar" class="mws-button green" />
                            </div>

                            <div style="clear:both;"></div>

            </div><!-- END DIV TAB 2-->
            </form>


        </div>
    </div>




</div>
<div id="popupmensaje" style="display:none;"></div>