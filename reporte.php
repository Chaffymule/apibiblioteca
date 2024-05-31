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

class reporte
{
    // Datos de la tabla "reporte"
    const NOMBRE_TABLA = "reporte";
    const ID_REPORTE = "id_reporte";
    const ID_LIBRO = "id_libro";
    const FECHA_REPORTE = "fecha_reporte";
    const DESCRIPCION = "descripcion";
    const ESTADO_CREACION_EXITOSA = "Creación con éxito";
    const ESTADO_CREACION_FALLIDA = "Creación fallida";
    const URL_FALLIDO = "URL Falliado";    
    const MENSAJE_EXITO_GET = "Obtención exitosa";
    const MENSAJE_EXITO_POST = "Creación exitosa";
    const MENSAJE_EXITO_DELETE = "Eliminación exitosa";
    const MENSAJE_EXITO_PUT = "Modificación exitosa";
    const MENSAJE_FALLA_POST = "Creación fallida";
    const MENSAJE_FALLA_DELETE = "Error al intentar eliminar el reporte";
    const MENSAJE_FALLA_PUT = "Error al intentar modificar el reportes";
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
                    // Obtener los botones en el rango especificado
                    return self::obtenerReportesRango($inicio, $fin);
                } else {
                    // Si el inicio es mayor que el fin, devolver un mensaje de error
                    return self::ESTADO_CREACION_FALLIDA;
                }
            } else {
                // Si no hay exactamente dos parámetros, intentar obtener un botón por su ID
                $idReporte = $peticion[0];
                return self::obtenerReporte($idReporte);
            }
        } else {
            // Si no hay parámetros en la solicitud, devolver todos los botones
            return self::obtenerReportes();
        }
    }


    private static function obtenerReportesRango($inicio, $fin)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Consulta SQL para obtener los botones en el rango especificado
            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_REPORTE . " BETWEEN ? AND ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $inicio, PDO::PARAM_INT);
            $sentencia->bindParam(2, $fin, PDO::PARAM_INT);
            $sentencia->execute();

            $reportes = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $reportes;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerReportes()
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA;

            $sentencia = $pdo->prepare($comando);
            $sentencia->execute();

            $reportes = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            return $reportes;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private static function obtenerReporte($idReporte)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_REPORTE . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idReporte);
            $sentencia->execute();

            $reporte = $sentencia->fetch(PDO::FETCH_ASSOC);

            if (!$reporte) {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO, "El botón con ID $idReporte no existe", 404);
            }

            return $reporte;
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
            $datosReporte = json_decode($cuerpo);
            return self::crear($datosReporte);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function crear($datosReporte)
    {
        $id_libro = $datosReporte->id_libro;
        $fecha_reporte = $datosReporte->fecha_reporte;
        $descripcion = $datosReporte->descripcion;

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::ID_LIBRO . "," .
                self::FECHA_REPORTE . "," .
                self::DESCRIPCION . ")" .
                " VALUES(?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $id_libro);
            $sentencia->bindParam(2, $fecha_reporte);
            $sentencia->bindParam(3, $descripcion);

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
                    // Eliminar los botones en el rango especificado
                    return self::eliminarReportesRango($inicio, $fin);
                } else {
                    // Si el inicio es mayor que el fin, devolver un mensaje de error
                    throw new ExcepcionApi(self::ESTADO_ERROR, "El parámetro de inicio debe ser menor o igual al parámetro de fin", 400);
                }
            } else {
                // Si no hay exactamente dos parámetros, intentar eliminar un botón por su ID
                $idReporte = $peticion[0];
                return self::eliminarReporte($idReporte);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada", 400);
        }
    }

    private static function eliminarReportesRango($inicio, $fin)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE para eliminar los botones en el rango especificado
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_REPORTE . " BETWEEN ? AND ?";

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


    public static function eliminarReporte($idReporte)
    {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA . " WHERE " . self::ID_REPORTE . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idReporte);
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
            $idReporte = $peticion[0];
            $cuerpo = file_get_contents('php://input');
            $datosReporte = json_decode($cuerpo);
            return self::modificarReporte($idReporte, $datosReporte);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada", 400);
        }
    }

    public static function modificarReporte($idReporte, $datosReporte)
    {
        $id_libro = $datosReporte->id_libro;
        $fecha_reporte = $datosReporte->fecha_reporte;
        $descripcion = $datosReporte->descripcion;

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia UPDATE
            $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " .
                self::ID_LIBRO . " = ?," .
                self::FECHA_REPORTE . " = ?," .
                self::DESCRIPCION . " = ?" .
                " WHERE " . self::ID_REPORTE . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $id_libro);
            $sentencia->bindParam(2, $fecha_reporte);
            $sentencia->bindParam(3, $descripcion);
            $sentencia->bindParam(4, $idReporte);

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