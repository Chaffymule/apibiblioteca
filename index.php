<?php
    /* 
        Proyecto: API Restfull
        Integrantes: 
        Angel Alexis Nolasco Acosta, 
        Julio Manuel Guzman Zarrabal,
        Mitzue Michelle Castañeda Esquibel
        Grupo: 18U
        Maestro: Esquivel Pat Agustin
    */
    require_once "VistaJson.php";
    require_once "VistaXML.php";
    require_once "usuarios.php";
    require_once "prestamos.php";
    require_once "reporte.php";
    require_once "autor.php";
    require_once "categoria.php";
    require_once "libro.php";
    require_once "editorial.php";
    

    // Constantes de estado
    const ESTADO_URL_INCORRECTA = 2;
    const ESTADO_EXISTENCIA_RECURSO = 3;
    const ESTADO_METODO_NO_PERMITIDO = 4;

    $vista = new VistaJson();
    
    set_exception_handler(function ($exception) use ($vista) {
        $cuerpo = array(
            "estado" => $exception->estado,
            "mensaje" => $exception->getMessage()
        );
        if ($exception->getCode()) {
            $vista->estado = $exception->getCode();
        } else {
            $vista->estado = 500;
        }   
    
        $vista->imprimir($cuerpo);
    }
    );
    // Extraer segmento de la url
    if (isset($_GET['PATH_INFO']))
    $peticion = explode('/', $_GET['PATH_INFO']);
    else
    throw new ExcepcionApi(ESTADO_URL_INCORRECTA, utf8_encode("No se reconoce la petición"));

    // PATH_INFO = "/botones/agregar/1";

    // $peticion = ["botones" , "agregar", "1"];
    // $recurso = "botones";
    // $peticion = ["agregar", "1"];

    // Obtener recurso
    $recurso = array_shift($peticion);
    $recursos_existentes = array('contactos', 'usuarios', 'botones', 'clima', 'autor', 'libro', 'categoria', 'editorial', 'prestamos', 'reportepdf', 'reporte');

    // Comprobar si existe el recurso
    if (!in_array($recurso, $recursos_existentes)) {
    throw new ExcepcionApi(ESTADO_EXISTENCIA_RECURSO,
        "No se reconoce el recurso al que intentas acceder " . $recurso);
    }

    $metodo = strtolower($_SERVER['REQUEST_METHOD']);

    // Filtrar método
    switch ($metodo) {
        case 'get':
        case 'post':
        case 'put':
        case 'delete':
            if (method_exists($recurso, $metodo)) {
                $respuesta = call_user_func(array($recurso, $metodo), $peticion);
                $vista->imprimir($respuesta);
                break;
            }
        default:
            // Método no aceptado
            $vista->estado = 405;
            $cuerpo = [
                "estado" => ESTADO_METODO_NO_PERMITIDO,
                "mensaje" => utf8_encode("Método no permitido " . $metodo)
            ];
            $vista->imprimir($cuerpo);

    }
/*
exit;
    $jsonBoton = '{
        "material" : "lino",
        "color" : "blanco",
        "ojales" : "6"
    }';
    $objBoton = json_decode($jsonBoton);
    $boton = new botones();
    $respuesta = $boton->crear($objBoton);

    // {
    //     "estado": 400,
    //     "datos": "Creacion con exito"
    // }
    
    // $respuestaJSon = new VistaJson();

    // $arreglo = ["estado"=>$respuestaJSon->estado,
    //             "datos"=>$respuesta];

    // $respuestaJSon->imprimir($arreglo);

    //Leer queryparams formato para definir el formato de la respuesta http
    $formato = "";
    if (isset($_GET["format"])) {
        $formato = $_GET["formato"];
    }

    
    if(!isset($formato) || $formato == "JSON"){
        $respuestaJSon = new VistaJson();
        $objeto = new stdclass();
        $objeto->estado = $respuestaJSon->estado;
        $objeto->datos = $respuesta;  
    
        $respuestaJSon->imprimir($objeto);
    }else{
        $respuestaXML = new VistaXML();

    // $arreglo = ["estado"=>$respuestaXML->estado,
    // "datos"=>$respuesta];
    
        $objeto = new stdclass();
        $objeto->estado = $respuestaXML->estado;
        $objeto->datos = $respuesta;        
    
        $respuestaXML->imprimir($objeto);
    }

*/

    
?>