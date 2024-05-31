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
require_once "usuarios.php";

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

    public static function get($peticion)
    {
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


    private static function obtenerLibrosRango($inicio, $fin)
    {
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

    private static function obtenerLibros()
    {
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
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO, "El libro con ID $idLibro no existe", 404);
            }

            return $libro;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    public static function post($peticion)
    {
        //Procesar post
        //this->crear($peticion);
        if ($peticion[0] == 'crear') {
            $cuerpo = file_get_contents('php://input');
            $datosLibro = json_decode($cuerpo);
            return self::crear($datosLibro);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function crear($datosLibro)
    {
        $nombre_libro = $datosLibro->nombre_libro;
        $paginas = $datosLibro->paginas;
        $id_autor = $datosLibro->id_autor;
        $id_categoria = $datosLibro->id_categoria;
        $id_editorial = $datosLibro->id_editorial;

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

    public static function delete($peticion)
    {
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
                    throw new ExcepcionApi(self::ESTADO_ERROR, "El parámetro de inicio debe ser menor o igual al parámetro de fin", 400);
                }
            } else {
                // Si no hay exactamente dos parámetros, intentar eliminar un libro por su ID
                $idLibro = $peticion[0];
                return self::eliminarLibro($idLibro);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada", 400);
        }
    }

    private static function eliminarLibrosRango($inicio, $fin)
    {
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


    public static function eliminarLibro($idLibro)
    {
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
    public static function put($peticion)
    {
        if (!empty($peticion)) {
            $idLibro = $peticion[0];
            $cuerpo = file_get_contents('php://input');
            $datosLibro = json_decode($cuerpo);
            return self::modificarLibro($idLibro, $datosLibro);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada", 400);
        }
    }

    public static function modificarLibro($idLibro, $datosLibro)
    {
        $nombre_libro = $datosLibro->nombre_libro;
        $paginas = $datosLibro->paginas;
        $id_autor = $datosLibro->id_autor;
        $id_categoria = $datosLibro->id_categoria;
        $id_editorial = $datosLibro->id_editorial;

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia UPDATE
            $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " .
                self::NOMBRE_LIBRO . " = ?," .
                self::PAGINAS . " = ?," .
                self::ID_AUTOR . " = ?" .
                self::ID_CATEGORIA . " = ?," .
                self::ID_EDITORIAL . " = ?" .
                " WHERE " . self::ID_LIBRO . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $nombre_libro);
            $sentencia->bindParam(2, $paginas);
            $sentencia->bindParam(3, $id_autor);
            $sentencia->bindParam(4, $id_categoria);
            $sentencia->bindParam(5, $id_editorial);
            $sentencia->bindParam(6, $idLibro);

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

}