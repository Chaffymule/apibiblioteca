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
require_once "usuario.php";

class editorial
{
    // Datos de la tabla "editorial"
    const NOMBRE_TABLA = "editorial";
    const ID_EDITORIAL = "id_editorial";
    const NOMBRE = "nombre";
    const DIRECCION = "direccion";
    const NOMBRE_TABLAU = "usuario";
    const ID_USUARIO = "id_usuario";
    const TOKEN = "token";
    const ESTADO_CREACION_EXITOSA = "Creación con éxito";
    const ESTADO_CREACION_FALLIDA = "Creación fallida";
    const URL_FALLIDO = "URL Falliado";    
    const MENSAJE_EXITO_GET = "Obtención exitosa";
    const MENSAJE_EXITO_POST = "Creación exitosa";
    const MENSAJE_EXITO_DELETE = "Eliminación exitosa";
    const MENSAJE_EXITO_PUT = "Modificación exitosa";
    const MENSAJE_FALLA_POST = "Creación fallida";
    const MENSAJE_FALLA_DELETE = "Error al intentar eliminar el editorial";
    const MENSAJE_FALLA_PUT = "Error al intentar modificar el editorial";
    const ESTADO_ERROR_BD = -1;
    const ESTADO_CLAVE_NO_AUTORIZADA = 410;
    const ESTADO_AUSENCIA_CLAVE_API = 411;
    const ESTADO_PARAMETRO_INCORRECTO = 500;
    const ESTADO_PARAMETRO_FALTANTE = 501;
    const ESTADO_URL_INCORRECTA = 400;
    const ESTADO_NO_ENCONTRADO = 404;
    const ESTADO_DATOS_INCORRECTOS = 601;
    const ESTADO_PARAMETRO_NO_ENCONTRADO = 600;
    const ESTADO_METODO_NO_PERMITIDO  = 602; 

    public static function get($peticion)
    {
        $idUsuario = self::autorizar();
        
        if ($idUsuario == null) {
            throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado");
        }
        // Si hay parámetros en la solicitud
        if (!empty($peticion)) {
            // Si hay dos parámetros en la solicitud
            if (count($peticion) == 2) {
                // Obtener los valores de inicio y fin
                $inicio = intval($peticion[0]);
                $fin = intval($peticion[1]);

                // Verificar si el inicio es menor o igual al fin
                if ($inicio <= $fin) {
                    // Obtener los botones en el rango especificado
                    return self::obtenerEditorialesRango($inicio, $fin);
                } else {
                    // Si el inicio es mayor que el fin, devolver un mensaje de error
                    return self::ESTADO_CREACION_FALLIDA;
                }
            } else {
                // Si no hay exactamente dos parámetros, intentar obtener un botón por su ID
                $idBoton = $peticion[0];
                return self::obtenerEditorial($idBoton);
            }
        } else {
            // Si no hay parámetros en la solicitud, devolver todos los botones
            return self::obtenerEditoriales();
        }
    }


    private static function obtenerEditorialesRango($inicio, $fin)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Consulta SQL para obtener los botones en el rango especificado
            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_EDITORIAL . " BETWEEN ? AND ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $inicio, PDO::PARAM_INT);
            $sentencia->bindParam(2, $fin, PDO::PARAM_INT);
            $sentencia->execute();

            $editoriales = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $editoriales;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerEditoriales()
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA;

            $sentencia = $pdo->prepare($comando);
            $sentencia->execute();

            $editoriales = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $editoriales;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerEditorial($id_editorial)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_EDITORIAL . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $id_editorial);
            $sentencia->execute();

            $editorial = $sentencia->fetch(PDO::FETCH_ASSOC);

            if (!$editorial) {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO, "El botón con ID $id_editorial no existe");
            }

            return $editorial;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    public static function post($peticion)
    {
        $idUsuario = self::autorizar();
        
        if ($idUsuario == null) {
            throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado");
        }
        //Procesar post
        //this->crear($peticion);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($peticion) || $peticion[0] == 'crear') {
                $cuerpo = file_get_contents('php://input');
                $datosEditorial = json_decode($cuerpo);
                return self::crear($datosEditorial);
            } else {
                throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Método no permitido. Debe ser POST.");
        }
    }

    public static function crear($datosEditorial)
    {
        $requiredParams = ['nombre', 'direccion'];
    
        foreach ($requiredParams as $param) {
            if (!isset($datosEditorial->$param)) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios: $param");
            }
        }

        $nombre = $datosEditorial->nombre;
        $direccion = $datosEditorial->direccion;

        if (
            empty($nombre) ||
            empty($direccion) 
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios");
        }

        if (
            !is_string($nombre) ||
            !is_string($direccion) 
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_INCORRECTO, "Los valores no son del tipo correcto o faltan campos obligatorios");
        }


        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . "," .
                self::DIRECCION . ")" .
                " VALUES(?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $direccion);

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

    public static function delete($peticion)
    {
        $idUsuario = self::autorizar();
        
        if ($idUsuario == null) {
            throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado");
        }
    
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            // Si hay parámetros en la solicitud
            if (!empty($peticion)) {
                // Si hay dos parámetros en la solicitud
                if (count($peticion) == 2) {
                    // Obtener los valores de inicio y fin
                    $inicio = intval($peticion[0]);
                    $fin = intval($peticion[1]);
    
                    // Verificar si el inicio es menor o igual al fin
                    if ($inicio <= $fin) {
                        // Eliminar los libros en el rango especificado
                        return self::eliminarEditorialesRango($inicio, $fin);
                    } else {
                        // Si el inicio es mayor que el fin, devolver un mensaje de error
                        throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "El parámetro de inicio debe ser menor o igual al parámetro de fin");
                    }
                } else {
                    // Si no hay exactamente dos parámetros, intentar eliminar un libro por su ID
                    $id_editorial = $peticion[0];
                    if (is_numeric($id_editorial)) {
                        return self::eliminarEditorial($id_editorial);
                    } else {
                        throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "El ID de la Categoria debe ser un número");
                    }
                }
            } else {
                throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Método no permitido. Debe ser DELETE.");
        }
    }

    private static function eliminarEditorialesRango($inicio, $fin)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE para eliminar los botones en el rango especificado
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_EDITORIAL . " BETWEEN ? AND ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $inicio, PDO::PARAM_INT);
            $sentencia->bindParam(2, $fin, PDO::PARAM_INT);
            $resultado = $sentencia->execute();


            if ($resultado) {
                return self::MENSAJE_EXITO_DELETE;
            } else {
                return self::MENSAJE_FALLA_DELETE;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    public static function eliminarEditorial($id_editorial)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_EDITORIAL . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $id_editorial);
            $resultado = $sentencia->execute();

            if ($resultado) {
                return self::MENSAJE_EXITO_DELETE;
            } else {
                return self::MENSAJE_FALLA_DELETE;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
    public static function put($peticion)
    {
        $idUsuario = self::autorizar();
        
        if ($idUsuario == null) {
            throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado");
        }
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            if (!empty($peticion) && isset($peticion[0]) && is_numeric($peticion[0])) {
                $idEditorial = $peticion[0];
                $cuerpo = file_get_contents('php://input');
                $datosEditorial = json_decode($cuerpo);
                if ($datosEditorial != null) {
                    return self::modificarCategoria($idEditorial, $datosEditorial);
                } else {
                    throw new ExcepcionApi(self::ESTADO_DATOS_INCORRECTOS, "Faltan datos de la editorial.");
                }
                } else {
                    throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada. Se requiere ID de la auditorial.");
                }
            } else {
                throw new ExcepcionApi(self::ESTADO_METODO_NO_PERMITIDO, "Método no permitido. Debe ser PUT.");
            }
        }

    public static function modificarEditorial($idEditorial,$datosEditorial)
    {
        $requiredParams = ['nombre', 'direccion'];
        $receivedParams = array_keys((array) $datosEditorial);

        foreach ($requiredParams as $param) {
            if (!in_array($param, $receivedParams)) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Falta el campo obligatorio: $param");
            }
        }

        if ($requiredParams !== $receivedParams) {
            throw new ExcepcionApi(self::ESTADO_ORDEN_PARAMETROS_INCORRECTO, "El orden de los parámetros es incorrecto");
        }

        $nombre = $datosEditorial->nombre;
        $direccion = $datosEditorial->direccion;

        if (
            empty($nombre) ||
            empty($direccion)
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios");
        }

        if (
            !is_string($nombre) ||
            !is_string($direccion)
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_INCORRECTO, "Los valores no son del tipo correcto o faltan campos obligatorios");
        }


        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia UPDATE
            $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " .
                self::NOMBRE . " = ?," .
                self::DIRECCION . " = ?" .
                " WHERE " . self::ID_EDITORIAL . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $direccion);
            $sentencia->bindParam(3, $idEditorial);

            $resultado = $sentencia->execute();

            if ($resultado) {
                return self::MENSAJE_EXITO_PUT;
            } else {
                return self::MENSAJE_FALLA_PUT;
            }

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    public static function autorizar() {
        $cabeceras = apache_request_headers();

        if (isset($cabeceras["Authorization"])) {
            $claveApi = $cabeceras["Authorization"];

            if (self::validarClaveApi($claveApi)) {
                return self::obtenerIdUsuario($claveApi);
            } else {
                throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_AUSENCIA_CLAVE_API, "Se requiere Token para autenticación");
        }
    }

    private static function validarClaveApi($claveApi) {
        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
        $comando = "SELECT COUNT(" . self::ID_USUARIO . ") FROM " . self::NOMBRE_TABLAU . " WHERE " . self::TOKEN . " = ?";
        $sentencia = $pdo->prepare($comando);
        $sentencia->bindParam(1, $claveApi);
        $sentencia->execute();

        return $sentencia->fetchColumn(0) > 0;
    }

    private static function obtenerIdUsuario($claveApi) {
        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
        $comando = "SELECT " . self::ID_USUARIO . " FROM " . self::NOMBRE_TABLAU . " WHERE " . self::TOKEN . " = ?";
        $sentencia = $pdo->prepare($comando);
        $sentencia->bindParam(1, $claveApi);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado[self::ID_USUARIO];
        } else {
            return null;
        }
    }
}