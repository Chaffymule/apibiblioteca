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
require_once "ConexionBD.php";
require_once "ExceptionApi.php";

class usuario{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "usuario";
    const ID_USUARIO = "id_usuario";
    const NOMBRE = "nombre";
    const APELLIDO1 = "apellido1";
    const APELLIDO2 = "apellido2";
    const TELEFONO = 'telefono';
    const PASSWORD = "password";
    const TOKEN = "token";
    const ESTADO_CREACION_EXITOSA  = "Creación con éxito";
    const ESTADO_CREACION_FALLIDA = "Creación fallida";
    const ESTADO_UPDATE_EXITOSA = "Modificacion exitosa";
    const ESTADO_UPDATE_FALLIDA = "Mofidicacion fallida";
    const ESTADO_DELETE_EXITOSA = "Eliminacion exitosa";
    const ESTADO_DELETE_FALLIDA = "Eliminacion fallida";
    const ESTADO_ERROR_BD = -1;
    const ESTADO_CLAVE_NO_AUTORIZADA = 410;
    const ESTADO_AUSENCIA_CLAVE_API = 411;
    const ESTADO_URL_INCORRECTA = 400;
    const ESTADO_PARAMETRO_INCORRECTO = 500;
    const ESTADO_PARAMETRO_FALTANTE = 501;
    const ESTADO_DATOS_INCORRECTOS = 601;
    const ESTADO_PARAMETRO_NO_ENCONTRADO = 600;
    const ESTADO_METODO_NO_PERMITIDO  = 602;

    public static function get($peticion){  
        $idUsuario = usuario::autorizar();
        
        if ($idUsuario == null) {
            throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado");
        }

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            // Preparar la sentencia SQL
            $sentencia = $pdo->prepare("SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_USUARIO . " = ?");

            $sentencia->bindParam(1, $idUsuario);
            
            if ($sentencia->execute()) {
                // Recuperar los detalles del usuario
                $resultado = $sentencia->fetch(PDO::FETCH_ASSOC);
                return $resultado;
            } else {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, "Se ha producido un error al recuperar los detalles del usuario.");
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }  

    public static function post($peticion){
        if (!empty($peticion)) {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL no necesita segmentos adicionales");
        }
        //Procesar post
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($peticion) || $peticion[0] == 'crear') {
                $cuerpo = file_get_contents('php://input');
                $datosBoton = json_decode($cuerpo);
                return self::crear($datosBoton);
            } else {
                throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Método no permitido. Debe ser POST.");
        }
    }

    public static function put($peticion){
         $idUsuario = usuario::autorizar();
        
        if ($idUsuario == null) {
            throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado");
        }
        //Procesar put
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            if (!empty($peticion) && isset($peticion[0]) && is_numeric($peticion[0])) {
            $idUsuario = $peticion[0];
            $cuerpo = file_get_contents('php://input');
            $datosUsuario = json_decode($cuerpo);
            if ($datosUsuario != null) {
                return self::actualizar($idUsuario, $datosUsuario);
            } else {
                throw new ExcepcionApi(self::ESTADO_DATOS_INCORRECTOS, "Faltan datos del usuario.");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada. Se requiere ID del usuario.");
        }
    } else {
        throw new ExcepcionApi(self::ESTADO_METODO_NO_PERMITIDO, "Método no permitido. Debe ser PUT.");
    }
}


    public static function crear($datosBoton)
    {
        $requiredParams = ['nombre', 'apellido1', 'apellido2', 'telefono', 'password'];
    
        foreach ($requiredParams as $param) {
            if (!isset($datosBoton->$param)) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios: $param");
            }
        }

        $nombre = $datosBoton->nombre;
        $apellido1 = $datosBoton->apellido1;
        $apellido2 = $datosBoton->apellido2;
        $telefono = $datosBoton->telefono;
        $password = password_hash($datosBoton->password, PASSWORD_BCRYPT, ['cost' => 4]);

        if (
            empty($nombre) ||
            empty($apellido1) ||
            empty($apellido2) ||
            empty($telefono) ||
            empty($password) 
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios");
        }

        if (
            !is_string($nombre) ||
            !is_string($apellido1) ||
            !is_string($apellido2) ||
            !is_numeric($telefono)
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_INCORRECTO, "Los valores no son del tipo correcto o faltan campos obligatorios");
        }
        $token = substr(uniqid(rand(), true), 0, 11);
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . "," .
                self::APELLIDO1 . "," .
                self::APELLIDO2 . "," .
                self::TELEFONO . "," .
                self::PASSWORD . "," .
                self::TOKEN . ")" .
                " VALUES(?,?,?,?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $apellido1);
            $sentencia->bindParam(3, $apellido2);
            $sentencia->bindParam(4, $telefono);
            $sentencia->bindParam(5, $password);
            $sentencia->bindParam(6, $token);
            

            $resultado = $sentencia->execute();

            if ($resultado) {
                return self::ESTADO_CREACION_EXITOSA;
            } else {
                return self::ESTADO_CREACION_FALLIDA;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }

    } 

    public static function actualizar($idUsuario,$datosUsuario){
        $requiredParams = ['nombre', 'apellido1', 'apellido2', 'telefono', 'password'];
        $receivedParams = array_keys((array) $datosUsuario);

        // Verificar si faltan parámetros obligatorios
        foreach ($requiredParams as $param) {
            if (!in_array($param, $receivedParams)) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Falta el campo obligatorio: $param");
            }
        }

        // Verificar el orden de los parámetros
        if ($requiredParams !== $receivedParams) {
            throw new ExcepcionApi(self::ESTADO_ORDEN_PARAMETROS_INCORRECTO, "El orden de los parámetros es incorrecto");
        }

        $nombre = $datosUsuario->nombre;
        $apellido1 = $datosUsuario->apellido1;
        $apellido2 = $datosUsuario->apellido2;
        $telefono = $datosUsuario->telefono;
        $password = $datosUsuario->password;
        
        if (
            empty($idUsuario) ||
            empty($nombre) ||
            empty($apellido1) ||
            empty($apellido2) ||
            empty($telefono) ||
            empty($password) 
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios");
        }

        if (
            !is_string($nombre) ||
            !is_string($apellido1) ||
            !is_string($apellido2) ||
            !is_numeric($telefono)
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_INCORRECTO, "Los valores no son del tipo correcto o faltan campos obligatorios");
        }

    
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
    
            // Sentencia UPDATE
            $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " .
                self::NOMBRE . " = ?," .
                self::APELLIDO1 . " = ?," .
                self::APELLIDO2 . " = ?," .  // Aquí se añade la coma que falta
                self::TELEFONO . " = ?" .
                " WHERE " . self::ID_USUARIO . " = ?";
    
            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $apellido1);
            $sentencia->bindParam(3, $apellido2);
            $sentencia->bindParam(4, $telefono);
            $sentencia->bindParam(5, $idUsuario);
    
            $resultado = $sentencia->execute();
    
            if ($resultado) {
                return self::ESTADO_UPDATE_EXITOSA;
            } else {
                return self::ESTADO_UPDATE_FALLIDA;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
    

    public static function autorizar(){
        $cabeceras = apache_request_headers();

        if (isset($cabeceras["Authorization"])) {

            $claveApi = $cabeceras["Authorization"];

            if (usuario::validarClaveApi($claveApi)) {
                return usuario::obtenerIdUsuario($claveApi);
            } else {
                throw new ExcepcionApi(
                    self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado");
            }

        } else {
            throw new ExcepcionApi(
                self::ESTADO_AUSENCIA_CLAVE_API,
                utf8_encode("Se requiere Token para autenticación"));
        }
    }

    private static function validarClaveApi($claveApi){
        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
        $comando = "SELECT COUNT(" . self::ID_USUARIO . ")" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::TOKEN . "=?";
    
        $sentencia = $pdo->prepare($comando);
        $sentencia->bindParam(1, $claveApi);
        $sentencia->execute();
    
        return $sentencia->fetchColumn(0) > 0;
    }
    
    private static function obtenerIdUsuario($claveApi){
        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
        $comando = "SELECT " . self::ID_USUARIO .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::TOKEN . "=?";
    
        $sentencia = $pdo->prepare($comando);
        $sentencia->bindParam(1, $claveApi);
    
        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado[self::ID_USUARIO];
        } else
            return null;
    }
    
}