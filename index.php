
<?php
if($_POST){
    require('/fpdf/fpdf.php');
    define("UPLOAD_DIR", "./temp/");

    function getNombres( $file ){
        $arr = array();
        $aux = file_get_contents($file);
        $arr = explode("\n",$aux);
        return $arr;
    }

    function processFile($myFile){

        if (!empty($myFile)) {

            if ($myFile["error"] !== UPLOAD_ERR_OK) {
                echo "<p>An error occurred.</p>";
                exit;
            }

            // ensure a safe filename
            $name = preg_replace("/[^A-Z0-9._-]/i", "_", $myFile["name"]);

            // don't overwrite an existing file
            $i = 0;
            $parts = pathinfo($name);
            while (file_exists(UPLOAD_DIR . $name)) {
                $i++;
                $name = $parts["filename"] . "-" . $i . "." . $parts["extension"];
            }

            // preserve file from temporary directory
            $success = move_uploaded_file($myFile["tmp_name"], UPLOAD_DIR . $name);
            if (!$success) { 
                echo "<p>Unable to save file.</p>";
                exit;
            }

            // set proper permissions on the new file
            //chmod(UPLOAD_DIR . $name, 0644);
            return UPLOAD_DIR . $name;
        }
    }

    /* creates a compressed zip file */
    function create_zip($files = array(),$destination = '',$overwrite = false) {

        if(file_exists($destination) && !$overwrite) { return false; }

        $valid_files = array();
        if(is_array($files)) {
            foreach($files as $file) {
                if(file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        //if we have good files...
        if(count($valid_files)) {
            $zip = new ZipArchive();
            if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                return false;
            }
            foreach($valid_files as $file) {
                $zip->addFile($file,($file));
            }
            //debug
            //echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

            $zip->close();
            
            return file_exists($destination);
        }else{
            return false;
        }
    }


    $folder = $_POST['destino']."/";
    $source = processFile($_FILES['source']);
    $bg = processFile($_FILES['bg']);
    $dir = 'resultado/'.$folder.'';
    $formato = array($_POST['ancho'] , $_POST['alto']);
    $archivos = array();

    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $arr = getNombres($source);
    $cont = 0; 

    $pos = array(
        'x' => $_POST['posx']? $_POST['posx']       : 40, 
        'y' => $_POST['posy']? $_POST['posy'] - 10  : 40
    );

    switch ($_POST['textAlign']) {
        case 'C':
            $pos['x'] = $pos['x']-20;
            break;
        case 'R':
            $pos['x'] = $pos['x']-40;
            break;
    }

    foreach($arr as $file ){

        $file = trim($file);
        if( $file == '' ) continue;

        $filename = str_replace(array('.',','),'',$file);
        $filename = strtolower($file);

        if($_POST['unArchivo'] == 0 || $cont == 0){
            $pdf=new FPDF("L","mm",$formato);
        }
        $pdf->AddPage();
        $pdf->SetMargins(0,0,0);
        $pdf->Image($bg, 0, 0,$_POST['ancho'],$_POST['alto']);
        $pdf->SetFont($_POST['fontFamily'],'B',$_POST['fontSize']);
        $pdf->SetXY($pos['x'], $pos['y']);
        $pdf->Cell(40,10,$file,0,0,$_POST['textAlign']);

        if($_POST['unArchivo'] == 0){
            $filename = $dir.$filename.".pdf";
            $pdf->Output($filename,"F");
            $archivos[] = $filename;
        }
        $cont++;
    }

    if($_POST['unArchivo'] == 1){
        $pdf->Output($dir."/certificados.pdf","F");
        $archivos[] = $dir."/certificados.pdf";
    }

    //create_zip($archivos, UPLOAD_DIR.'resultado.zip', true);
    unlink(UPLOAD_DIR.$_FILES['source']['name']);
    unlink(UPLOAD_DIR.$_FILES['bg']['name']);

    //header('Location: download.php?f='.UPLOAD_DIR.'resultado.zip');

    $msj = $cont." Certificados generados satisfactoriamente";
}
?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="author" content="@estebanlopeza">

        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/bootstrap-theme.min.css">
        <link rel="stylesheet" href="css/estilos.css">

        <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->
    </head>
    <body>
        <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

        <div class="container">
            <h1>Generador de certificados</h1>
            <?php if ($msj) {
                echo '<div class="alert alert-success" role="alert">'.$msj.'</div>';
            } ?>
            <div class="row">
                <form role="form" method="post" enctype="multipart/form-data">
                    <div class="col-md-6">
                        
                        <h3>General</h3>

                        <div class="form-group">
                            <label for="formato">Formato</label>
                            <p class="help-block">Formato del certificado</p>
                            <div class="row">
                                <div class="col-md-5">
                                    <select class="form-control" name="formato" id="formato">
                                        <option  value="" data-ancho="" data-alto="">Personalizado</option>
                                        <?php 
                                        $arrFormatos = array(
                                            'A3' => array(420, 297),
                                            'A4' => array(297, 210),
                                            'A5' => array(210, 148),
                                            'Letter' => array(279, 216),
                                            'Legal' => array(356, 216)
                                        );
                                        foreach ($arrFormatos as $formato => $medidas) {
                                            echo '<option value="'.$formato.'" data-ancho="'.$medidas[0].'" data-alto="'.$medidas[1].'" ';
                                            if($formato == $_POST['formato']){
                                                echo 'selected ';
                                            }
                                            echo '>'.$formato.'</option>'."\n";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <p>ó</p>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="ancho" id="ancho" placeholder="ancho" value="<?php echo $_POST['ancho']; ?>">
                                                <div class="input-group-addon">mm</div>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="alto" id="alto" placeholder="alto" value="<?php echo $_POST['alto']; ?>">
                                                <div class="input-group-addon">mm</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="fondo">Archivo orígen</label>
                            <p class="help-block">Listado de Nombres a certificar (archivo .txt. Un renglón por certificado).</p>
                            <input type="file" name="source" id="source" accept="text/plain">
                        </div>
                        <div class="form-group">
                            <label for="fondo">Fondo de certificado</label>
                            <p class="help-block">Imagen de fondo del certificado (debería tener las mismas medidas que el formato).</p>
                            <input type="file" name="bg" id="bg" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="destino">Carpeta destino</label>
                            <div class="input-group">
                                <div class="input-group-addon">resultado/</div>
                                <input class="form-control" type="text" name="destino" placeholder="" value="<?php echo $_POST['destino'] ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">

                        <h3>Texto</h3>
                        <div class="form-group">
                            <label for="formato">Fuente</label>
                            <p class="help-block">Fuente del texto</p>
                            <div class="row">
                                <div class="col-md-5">
                                    <select class="form-control" name="fontFamily" id="fontFamily">
                                        <?php 
                                        $arrFamilies = array('Arial', 'Times');
                                        foreach ($arrFamilies as $family) {
                                            echo '<option value="'.$family.'" ';
                                            if($family == $_POST['fontFamily']){
                                                echo 'selected ';
                                            }
                                            echo '>'.$family.'</option>'."\n";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <div class="row">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="fontSize" id="fontSize" placeholder="12" value="<?php echo $_POST['fontSize'] ?>">
                                            <div class="input-group-addon">pt</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default" data-value="L"><span class="glyphicon glyphicon-align-left"></span></button>
                                        <button type="button" class="btn btn-default" data-value="C"><span class="glyphicon glyphicon-align-center"></span></button>
                                        <button type="button" class="btn btn-default" data-value="R"><span class="glyphicon glyphicon-align-right"></span></button>
                                    </div>
                                    <input type="hidden" name="textAlign" id="textAlign" value="L">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="formato">Posición</label>
                            <p class="help-block">Especifica la posición del texto en el certificado</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="posx" id="posx" placeholder="x" value="<?php echo $_POST['posx'] ?>">
                                        <div class="input-group-addon">mm</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="posy" id="posy" placeholder="y" value="<?php echo $_POST['posy'] ?>">
                                        <div class="input-group-addon">mm</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-info" id="popoverPos"><span class="glyphicon glyphicon-info-sign"></span> ¿Cómo posicionar ?</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="unArchivo">Formato de salida</label>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="unArchivo" id="unArchivo" value="1" value="1" <?php if( $_POST['unArchivo'] == '1') echo 'checked' ?>>
                                    Agrupar todos los certificados en un archivo
                                </label>
                            </div>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="unArchivo" id="unArchivo" value="0" <?php if( $_POST['unArchivo'] == '0') echo 'checked' ?>>
                                    Generar cada certificado en un archivo separado
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <br/>
                            <button type="submit" class="btn btn-default pull-right">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
            <footer>
                <p class="align-right">2014 &bull; <a href="http://www.estebanlopeza.com.ar" target="_blank">Esteban López Adriano</a></p>
            </footer>
        </div>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/jquery-1.10.2.min.js"><\/script>')</script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/global.js"></script>
    </body>
</html>