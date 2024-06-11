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

class categoria
{
    // Datos de la tabla "categoria"
    const NOMBRE_TABLA = "categoria";
    const ID_CATEGORIA = "id_categoria";
    const NOMBRE = "nombre";
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
    const MENSAJE_FALLA_DELETE = "Error al intentar eliminar el botón";
    const MENSAJE_FALLA_PUT = "Error al intentar modificar el botón";
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
                    // Obtener los categorias en el rango especificado
                    return self::obtenerCategoriasRango($inicio, $fin);
                } else {
                    // Si el inicio es mayor que el fin, devolver un mensaje de error
                    return self::ESTADO_CREACION_FALLIDA;
                }
            } else {
                // Si no hay exactamente dos parámetros, intentar obtener una categoria por su ID
                $idCategoria = $peticion[0];
                return self::obtenerCategoria($idCategoria);
            }
        } else {
            // Si no hay parámetros en la solicitud, devolver todos los categorias
            return self::obtenerCategorias();
        }
    }


    private static function obtenerCategoriasRango($inicio, $fin)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Consulta SQL para obtener los botones en el rango especificado
            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_CATEGORIA . " BETWEEN ? AND ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $inicio, PDO::PARAM_INT);
            $sentencia->bindParam(2, $fin, PDO::PARAM_INT);
            $sentencia->execute();

            $categorias = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $categorias;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerCategorias()
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA;

            $sentencia = $pdo->prepare($comando);
            $sentencia->execute();

            $categorias = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $categorias;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerCategoria($idCategoria)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_CATEGORIA . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idCategoria);
            $sentencia->execute();

            $categoria = $sentencia->fetch(PDO::FETCH_ASSOC);

            if (!$categoria) {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO, "La Categoría con ID $idCategoria no existe");
            }

            return $categoria;
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
                $datosCategoria = json_decode($cuerpo);
                return self::crear($datosCategoria);
            } else {
                throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Método no permitido. Debe ser POST.");
        }
    }

    public static function crear($datosCategoria)
    {
        $requiredParams = ['nombre'];
    
        foreach ($requiredParams as $param) {
            if (!isset($datosCategoria->$param)) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios: $param");
            }
        }

        $nombre = $datosCategoria->nombre;

        if (
            empty($nombre)
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios");
        }

        if (
            !is_string($nombre)
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_INCORRECTO, "Los valores no son del tipo correcto o faltan campos obligatorios");
        }


        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . ")" .
                " VALUES(?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre);

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
                        return self::eliminarCategoriasRango($inicio, $fin);
                    } else {
                        // Si el inicio es mayor que el fin, devolver un mensaje de error
                        throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "El parámetro de inicio debe ser menor o igual al parámetro de fin");
                    }
                } else {
                    // Si no hay exactamente dos parámetros, intentar eliminar un libro por su ID
                    $idCategoria = $peticion[0];
                    if (is_numeric($idCategoria)) {
                        return self::eliminarCategoria($idCategoria);
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

    private static function eliminarCategoriasRango($inicio, $fin)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE para eliminar los botones en el rango especificado
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_CATEGORIA . " BETWEEN ? AND ?";

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


    public static function eliminarCategoria($idCategoria)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_CATEGORIA . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idCategoria);
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
            $idCategoria = $peticion[0];
            $cuerpo = file_get_contents('php://input');
            $datosCategoria = json_decode($cuerpo);
            if ($datosCategoria != null) {
                return self::modificarCategoria($idCategoria, $datosCategoria);
            } else {
                throw new ExcepcionApi(self::ESTADO_DATOS_INCORRECTOS, "Faltan datos del autor.");
            }
            } else {
                throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada. Se requiere ID del autor.");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_METODO_NO_PERMITIDO, "Método no permitido. Debe ser PUT.");
        }
    }

    public static function modificarCategoria($idCategoria,$datosCategoria)
    {
        $requiredParams = ['nombre'];
        $receivedParams = array_keys((array) $datosCategoria);

        foreach ($requiredParams as $param) {
            if (!in_array($param, $receivedParams)) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Falta el campo obligatorio: $param");
            }
        }

        if ($requiredParams !== $receivedParams) {
            throw new ExcepcionApi(self::ESTADO_ORDEN_PARAMETROS_INCORRECTO, "El orden de los parámetros es incorrecto");
        }

        $nombre = $datosCategoria->nombre;

        if (
            empty($nombre)
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios");
        }

        if (
            !is_string($nombre)
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_INCORRECTO, "Los valores no son del tipo correcto o faltan campos obligatorios");
        }

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia UPDATE
            $comando = " UPDATE " . self::NOMBRE_TABLA . " SET " .
                self::NOMBRE . " = ? " .
                " WHERE " . self::ID_CATEGORIA . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $idCategoria);

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