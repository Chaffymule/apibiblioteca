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

class libro
{
    // Datos de la tabla "libro"
    const NOMBRE_TABLA = "libro";
    const ID_LIBRO = "id_libro";
    const NOMBRE_LIBRO = "nombre_libro";
    const PAGINAS = "paginas";
    const ID_AUTOR = "id_autor";
    const ID_CATEGORIA = "id_categoria";
    const ID_EDITORIAL = "id_editorial";
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
    const MENSAJE_FALLA_DELETE = "Error al intentar eliminar el libro";
    const MENSAJE_FALLA_PUT = "Error al intentar modificar el libro";
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

    public static function get($peticion){
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
                    // Obtener los libros en el rango especificado
                    return self::obtenerLibrosRango($inicio, $fin);
                } else {
                    // Si el inicio es mayor que el fin, devolver un mensaje de error
                    return self::ESTADO_CREACION_FALLIDA;
                }
            } else {
                // Si no hay exactamente dos parámetros, intentar obtener un libro por su ID
                $idLibro = $peticion[0];
                return self::obtenerLibro($idLibro);
            }
        } else {
            // Si no hay parámetros en la solicitud, devolver todos los libros
            return self::obtenerLibros();
        }
    }


    private static function obtenerLibrosRango($inicio, $fin){
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Consulta SQL para obtener los libros en el rango especificado
            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_LIBRO . " BETWEEN ? AND ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $inicio, PDO::PARAM_INT);
            $sentencia->bindParam(2, $fin, PDO::PARAM_INT);
            $sentencia->execute();

            $libros = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $libros;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerLibros(){
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA;

            $sentencia = $pdo->prepare($comando);
            $sentencia->execute();

            $libros = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $libros;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerLibro($idLibro)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_LIBRO . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idLibro);
            $sentencia->execute();

            $libro = $sentencia->fetch(PDO::FETCH_ASSOC);

            if (!$libro) {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO, "El libro con ID $idLibro no existe");
            }

            return $libro;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    public static function post($peticion){
        $idUsuario = self::autorizar();
        
        if ($idUsuario == null) {
            throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($peticion) || $peticion[0] == 'crear') {
                $cuerpo = file_get_contents('php://input');
                $datosLibro = json_decode($cuerpo);
                return self::crear($datosLibro);
            } else {
                throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Método no permitido. Debe ser POST.");
        }
    }

    public static function crear($datosLibro){
        $requiredParams = ['nombre_libro', 'paginas', 'id_autor', 'id_categoria', 'id_editorial'];
    
        foreach ($requiredParams as $param) {
            if (!isset($datosLibro->$param)) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios: $param");
            }
        }
        
        $nombre_libro = $datosLibro->nombre_libro;
        $paginas = $datosLibro->paginas;
        $id_autor = $datosLibro->id_autor;
        $id_categoria = $datosLibro->id_categoria;
        $id_editorial = $datosLibro->id_editorial;
        if (
            empty($nombre_libro) ||
            empty($paginas) ||
            empty($id_autor) ||
            empty($id_categoria) ||
            empty($id_editorial)
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios");
        }

        if (
            !is_string($nombre_libro) ||
            !is_numeric($paginas) || $paginas <= 0 ||
            !is_numeric($id_autor) || $id_autor <= 0 ||
            !is_numeric($id_categoria) || $id_categoria <= 0 ||
            !is_numeric($id_editorial) || $id_editorial <= 0
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_INCORRECTO, "Los valores no son del tipo correcto o faltan campos obligatorios");
        }

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE_LIBRO . "," .
                self::PAGINAS . "," .
                self::ID_AUTOR . "," .
                self::ID_CATEGORIA . "," .
                self::ID_EDITORIAL . ")" .
                " VALUES(?,?,?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre_libro);
            $sentencia->bindParam(2, $paginas);
            $sentencia->bindParam(3, $id_autor);
            $sentencia->bindParam(4, $id_categoria);
            $sentencia->bindParam(5, $id_editorial);

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

    public static function delete($peticion){
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
                        return self::eliminarLibrosRango($inicio, $fin);
                    } else {
                        // Si el inicio es mayor que el fin, devolver un mensaje de error
                        throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "El parámetro de inicio debe ser menor o igual al parámetro de fin");
                    }
                } else {
                    // Si no hay exactamente dos parámetros, intentar eliminar un libro por su ID
                    $idLibro = $peticion[0];
                    if (is_numeric($idLibro)) {
                        return self::eliminarLibro($idLibro);
                    } else {
                        throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "El ID del libro debe ser un número");
                    }
                }
            } else {
                throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Método no permitido. Debe ser DELETE.");
        }
    }
    
    private static function eliminarLibrosRango($inicio, $fin){
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE para eliminar los botones en el rango especificado
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_LIBRO . " BETWEEN ? AND ?";

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


    public static function eliminarLibro($idLibro){
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_LIBRO . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idLibro);
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
   
    public static function put($peticion){
        $idUsuario = self::autorizar();
        
        if ($idUsuario == null) {
            throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            if (!empty($peticion) && isset($peticion[0]) && is_numeric($peticion[0])) {
                $id_libro = $peticion[0];
                $cuerpo = file_get_contents('php://input');
                $datosLibro = json_decode($cuerpo);
                
                if ($datosLibro != null) {
                    return self::modificarLibro($id_libro, $datosLibro);
                } else {
                    throw new ExcepcionApi(self::ESTADO_DATOS_INCORRECTOS, "Faltan datos del libro.");
                }
            } else {
                throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada. Se requiere ID del libro.");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_METODO_NO_PERMITIDO, "Método no permitido. Debe ser PUT.");
        }
    }

    
    public static function modificarLibro($id_libro, $datosLibro)
    {
        $requiredParams = ['nombre_libro', 'paginas', 'id_autor', 'id_categoria', 'id_editorial'];
        $receivedParams = array_keys((array) $datosLibro);

        foreach ($requiredParams as $param) {
            if (!in_array($param, $receivedParams)) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Falta el campo obligatorio: $param");
            }
        }

        if ($requiredParams !== $receivedParams) {
            throw new ExcepcionApi(self::ESTADO_ORDEN_PARAMETROS_INCORRECTO, "El orden de los parámetros es incorrecto");
        }

    
        $nombre_libro = $datosLibro->nombre_libro;
        $paginas = $datosLibro->paginas;
        $id_autor = $datosLibro->id_autor;
        $id_categoria = $datosLibro->id_categoria;
        $id_editorial = $datosLibro->id_editorial;
    
        if (
            empty($nombre_libro) ||
            empty($paginas) ||
            empty($id_autor) ||
            empty($id_categoria) ||
            empty($id_editorial)
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_FALTANTE, "Faltan campos obligatorios");
        }
    
        if (
            !is_string($nombre_libro) ||
            !is_numeric($paginas) || $paginas <= 0 ||
            !is_numeric($id_autor) || $id_autor <= 0 ||
            !is_numeric($id_categoria) || $id_categoria <= 0 ||
            !is_numeric($id_editorial) || $id_editorial <= 0
        ) {
            throw new ExcepcionApi(self::ESTADO_PARAMETRO_INCORRECTO, "Los valores no son del tipo correcto o faltan campos obligatorios");
        }
    
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
    
            // Verificar si las claves foráneas existen
            $comando = "SELECT COUNT(*) AS count_autor FROM autor WHERE id_autor = ?";
            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $id_autor);
            $sentencia->execute();
            $resultado = $sentencia->fetch(PDO::FETCH_ASSOC);
    
            if ($resultado['count_autor'] == 0) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_NO_ENCONTRADO, "El autor con ID $id_autor no existe");
            }
    
            $comando = "SELECT COUNT(*) AS count_categoria FROM categoria WHERE id_categoria = ?";
            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $id_categoria);
            $sentencia->execute();
            $resultado = $sentencia->fetch(PDO::FETCH_ASSOC);
    
            if ($resultado['count_categoria'] == 0) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_NO_ENCONTRADO, "La categoría con ID $id_categoria no existe");
            }
    
            $comando = "SELECT COUNT(*) AS count_editorial FROM editorial WHERE id_editorial = ?";
            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $id_editorial);
            $sentencia->execute();
            $resultado = $sentencia->fetch(PDO::FETCH_ASSOC);
    
            if ($resultado['count_editorial'] == 0) {
                throw new ExcepcionApi(self::ESTADO_PARAMETRO_NO_ENCONTRADO, "La editorial con ID $id_editorial no existe");
            }
    
            // Sentencia UPDATE
            $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " .
                self::NOMBRE_LIBRO . " = ?," .
                self::PAGINAS . " = ?," .
                self::ID_AUTOR . " = ?," .
                self::ID_EDITORIAL . " = ?," .
                self::ID_CATEGORIA . " = ?" .
                " WHERE " . self::ID_LIBRO . " = ?";
    
            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $nombre_libro);
            $sentencia->bindParam(2, $paginas);
            $sentencia->bindParam(3, $id_autor);
            $sentencia->bindParam(5, $id_categoria);
            $sentencia->bindParam(4, $id_editorial);
            $sentencia->bindParam(6, $id_libro);
    
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