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

class usuarios{
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

    public static function get($peticion){  
        $idUsuario = usuarios::autorizar();
        
        if ($idUsuario == null) {
            throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave API no autorizada");
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
        //Procesar post
        //this->crear($peticion);
        if ($peticion[0] == 'crear') {
            $cuerpo = file_get_contents('php://input');
            $datosBoton = json_decode($cuerpo);
            return self::crear($datosBoton);
        }else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function put($peticion){
        $idUsuario = usuarios::autorizar();
        //Procesar put
        if ($peticion[0] == 'actualizar') {
            $cuerpo = file_get_contents('php://input');
            $datosBoton = json_decode($cuerpo);
            return self::actualizar($datosBoton);
        }else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }


    public static function crear($datosBoton)
    {
        $nombre = $datosBoton->nombre;
        $apellido1 = $datosBoton->apellido1;
        $apellido2 = $datosBoton->apellido2;
        $telefono = $datosBoton->telefono;
        $password = password_hash($datosBoton->password, PASSWORD_BCRYPT, ['cost' => 4]);
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

    public static function actualizar($datosUsuario){
        $nombre = $datosUsuario->nombre;
        $apellido1 = $datosUsuario->apellido1;
        $apellido2 = $datosUsuario->apellido2;
        $telefono = $datosUsuario->telefono;
        $idUsuario = $datosUsuario->id_usuario; // Asumiendo que el ID de usuario se pasa en $datosUsuario
    
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

            if (usuarios::validarClaveApi($claveApi)) {
                return usuarios::obtenerIdUsuario($claveApi);
            } else {
                throw new ExcepcionApi(
                    self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave de API no autorizada", 401);
            }

        } else {
            throw new ExcepcionApi(
                self::ESTADO_AUSENCIA_CLAVE_API,
                utf8_encode("Se requiere Clave del API para autenticación"));
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