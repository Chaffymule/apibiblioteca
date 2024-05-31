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

class autor
{
    // Datos de la tabla "autor"
    const NOMBRE_TABLA = "autor";
    const ID_AUTOR = "id_autor";
    const NOMBRE = "nombre";
    const APELLIDO = "apellido";
    const ESTADO_CREACION_EXITOSA = "Creación con éxito";
    const ESTADO_CREACION_FALLIDA = "Creación fallida";
    const URL_FALLIDO = "URL Falliado";    
    const MENSAJE_EXITO_GET = "Obtención exitosa";
    const MENSAJE_EXITO_POST = "Creación exitosa";
    const MENSAJE_EXITO_DELETE = "Eliminación exitosa";
    const MENSAJE_EXITO_PUT = "Modificación exitosa";
    const MENSAJE_FALLA_POST = "Creación fallida";
    const MENSAJE_FALLA_DELETE = "Error al intentar eliminar el autor";
    const MENSAJE_FALLA_PUT = "Error al intentar modificar el autor";
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
                    // Obtener los autores en el rango especificado
                    return self::getAutoresRango($inicio, $fin);
                } else {
                    // Si el inicio es mayor que el fin, devolver un mensaje de error
                    return self::ESTADO_CREACION_FALLIDA;
                }
            } else {
                // Si no hay exactamente dos parámetros, intentar obtener un autor por su ID
                $idAutor = $peticion[0];
                return self::obtenerAutor($idAutor);
            }
        } else {
            // Si no hay parámetros en la solicitud, devolver todos los autores
            return self::obtenerAutores();
        }
    }


    private static function getAutoresRango($inicio, $fin)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Consulta SQL para obtener los autores en el rango especificado
            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_AUTOR . " BETWEEN ? AND ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $inicio, PDO::PARAM_INT);
            $sentencia->bindParam(2, $fin, PDO::PARAM_INT);
            $sentencia->execute();

            $autores = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $autores;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerAutores()
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA;

            $sentencia = $pdo->prepare($comando);
            $sentencia->execute();

            $autores = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $autores;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerAutor($idAutor)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_AUTOR . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idAutor);
            $sentencia->execute();

            $autor = $sentencia->fetch(PDO::FETCH_ASSOC);

            if (!$autor) {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO, "El botón con ID $idAutor no existe", 404);
            }

            return $autor;
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
            $datosAutor = json_decode($cuerpo);
            return self::crear($datosAutor);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function crear($datosAutor)
    {
        $nombre = $datosAutor->nombre;
        $apellido = $datosAutor->apellido;

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . "," .
                self::APELLIDO . ")" .
                " VALUES(?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $apellido);

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
                    // Eliminar los autores en el rango especificado
                    return self::eliminarAutoresRango($inicio, $fin);
                } else {
                    // Si el inicio es mayor que el fin, devolver un mensaje de error
                    throw new ExcepcionApi(self::ESTADO_ERROR, "El parámetro de inicio debe ser menor o igual al parámetro de fin", 400);
                }
            } else {
                // Si no hay exactamente dos parámetros, intentar eliminar un autor por su ID
                $idAutor = $peticion[0];
                return self::eliminarAutor($idAutor);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada", 400);
        }
    }

    public static function eliminarAutoresRango($inicio, $fin)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE para eliminar los botones en el rango especificado
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_AUTOR . " BETWEEN ? AND ?";

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


    public static function eliminarAutor($idAutor)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_AUTOR . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idAutor);
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
            $idAutor = $peticion[0];
            $cuerpo = file_get_contents('php://input');
            $datosAutor = json_decode($cuerpo);
            return self::modificarAutor($idAutor, $datosAutor);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada", 400);
        }
    }

    public static function modificarAutor($idAutor, $datosAutor)
    {
        $nombre = $datosAutor->nombre;
        $apellido = $datosAutor->apellido;

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia UPDATE
            $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " .
                self::NOMBRE . " = ?," .
                self::APELLIDO . " = ?," .
                " WHERE " . self::ID_AUTOR . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $apellido);
            $sentencia->bindParam(3, $idAutor);

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