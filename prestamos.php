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

class prestamos {
    // Datos de la tabla "prestamo"
    const NOMBRE_TABLA = "prestamo";
    const ID_PRESTAMO = "id_prestamo";
    const ID_LIBRO = "id_libro";
    const FECHA_PRESTAMO = "fecha_prestamo";
    const FECHA_DEVOLUCION = "fecha_devolucion";
    const NOMBRE_TABLAU = "usuario";
    const ID_USUARIO = "id_usuario";
    const TOKEN = "token";
    const ESTADO_CREACION_EXITOSA  = "Creación con éxito";
    const ESTADO_CREACION_FALLIDA = "Creación fallida";
    const ESTADO_UPDATE_EXITOSA = "Modificación exitosa";
    const ESTADO_UPDATE_FALLIDA = "Modificación fallida";
    const ESTADO_DELETE_EXITOSA = "Eliminación exitosa";
    const ESTADO_DELETE_FALLIDA = "Eliminación fallida";
    const ESTADO_ERROR_BD = -1;
    const ESTADO_CLAVE_NO_AUTORIZADA = 410;
    const ESTADO_AUSENCIA_CLAVE_API = 411;
    const ESTADO_URL_INCORRECTA = 412;

    public static function get($peticion) {
        $idUsuario = self::autorizar();

        if ($idUsuario == null) {
            throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave API no autorizada");
        }

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            if (empty($peticion)) {
                $sentencia = $pdo->prepare("SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_USUARIO . " = ?");
                $sentencia->bindParam(1, $idUsuario);

                if ($sentencia->execute()) {
                    $resultado = $sentencia->fetchAll(PDO::FETCH_ASSOC);
                    if ($resultado) {
                        return $resultado;
                    } else {
                        throw new ExcepcionApi(self::ESTADO_ERROR_BD, "No se encontraron préstamos.");
                    }
                } else {
                    throw new ExcepcionApi(self::ESTADO_ERROR_BD, "Se ha producido un error al recuperar los préstamos.");
                }
            } else {
                $idPrestamo = $peticion[0]; // Asumiendo que el ID del préstamo es el primer elemento de $peticion
                $sentencia = $pdo->prepare("SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_PRESTAMO . " = ? AND " . self::ID_USUARIO . " = ?");
                $sentencia->bindParam(1, $idPrestamo);
                $sentencia->bindParam(2, $idUsuario);
                
                if ($sentencia->execute()) {
                    $resultado = $sentencia->fetch(PDO::FETCH_ASSOC);
                    if ($resultado) {
                        return $resultado;
                    } else {
                        throw new ExcepcionApi(self::ESTADO_ERROR_BD, "No se encontró el préstamo.");
                    }
                } else {
                    throw new ExcepcionApi(self::ESTADO_ERROR_BD, "Se ha producido un error al recuperar los detalles del préstamo.");
                }
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    

    private static function obtenerPrestamosRango($inicio, $fin)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Consulta SQL para obtener los botones en el rango especificado
            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_PRESTAMO . " BETWEEN ? AND ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $inicio, PDO::PARAM_INT);
            $sentencia->bindParam(2, $fin, PDO::PARAM_INT);
            $sentencia->execute();

            $prestamos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $prestamos;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerPrestamos()
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA;

            $sentencia = $pdo->prepare($comando);
            $sentencia->execute();

            $prestamos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $prestamos;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerPrestamo($idPrestamo)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_PRESTAMO . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idPrestamo);
            $sentencia->execute();

            $prestamo = $sentencia->fetch(PDO::FETCH_ASSOC);

            if (!$prestamo) {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO, "El botón con ID $idPrestamo no existe", 404);
            }

            return $prestamo;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    public static function post($peticion)
    {
        //Procesar post
        //this->crear($peticion);
        $idUsuario = self::autorizar();
        if ($peticion[0] == 'crear') {
            $cuerpo = file_get_contents('php://input');
            $datosPrestamo = json_decode($cuerpo);
            return self::crear($datosPrestamo);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function crear($datosPrestamo)
    {
        $id_libro = $datosPrestamo->id_libro;
        $fecha_prestamo = $datosPrestamo->fecha_prestamo;
        $fecha_devolucion = $datosPrestamo->fecha_devolucion;

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::ID_LIBRO . "," .
                self::FECHA_PRESTAMO . "," .
                self::FECHA_DEVOLUCION . ")" .
                " VALUES(?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $id_libro);
            $sentencia->bindParam(2, $fecha_prestamo);
            $sentencia->bindParam(3, $fecha_devolucion);

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
        // Si hay parámetros en la solicitud
        if (!empty($peticion)) {
            // Si hay dos parámetros en la solicitud
            if (count($peticion) == 2) {
                // Obtener los valores de inicio y fin
                $inicio = intval($peticion[0]);
                $fin = intval($peticion[1]);

                // Verificar si el inicio es menor o igual al fin
                if ($inicio <= $fin) {
                    // Eliminar los botones en el rango especificado
                    return self::eliminarPrestamosRango($inicio, $fin);
                } else {
                    // Si el inicio es mayor que el fin, devolver un mensaje de error
                    throw new ExcepcionApi(self::ESTADO_ERROR, "El parámetro de inicio debe ser menor o igual al parámetro de fin", 400);
                }
            } else {
                // Si no hay exactamente dos parámetros, intentar eliminar un botón por su ID
                $idPrestamo = $peticion[0];
                return self::eliminarPrestamo($idPrestamo);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada", 400);
        }
    }

    private static function eliminarPrestamosRango($inicio, $fin)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE para eliminar los botones en el rango especificado
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_PRESTAMO . " BETWEEN ? AND ?";

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


    public static function eliminarPrestamo($idPrestamo)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_PRESTAMO . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idPrestamo);
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
        if (!empty($peticion)) {
            $idPrestamo = $peticion[0];
            $cuerpo = file_get_contents('php://input');
            $datosPrestamo = json_decode($cuerpo);
            return self::modificarPrestamo($idPrestamo, $datosPrestamo);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada", 400);
        }
    }

    public static function modificarPrestamo($idPrestamo, $datosPrestamo)
    {
        $id_libro = $datosPrestamo->id_libro;
        $fecha_prestamo = $datosPrestamo->fecha_prestamo;
        $fecha_devolucion = $datosPrestamo->fecha_devolucion;

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia UPDATE
            $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " .
                self::ID_LIBRO . " = ?," .
                self::FECHA_PRESTAMO . " = ?," .
                self::FECHA_DEVOLUCION . " = ?" .
                " WHERE " . self::ID_PRESTAMO . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $id_libro);
            $sentencia->bindParam(2, $fecha_prestamo);
            $sentencia->bindParam(3, $fecha_devolucion);
            $sentencia->bindParam(4, $idPrestamo);

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
                throw new ExcepcionApi(self::ESTADO_CLAVE_NO_AUTORIZADA, "Token no autorizado", 401);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_AUSENCIA_CLAVE_API, "Se requiere Token para autenticación", 400);
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
?>
