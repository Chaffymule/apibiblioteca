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
require_once "fpdf.php";

class PDF extends FPDF
{
    // Datos de la tabla reporte pdf
    const NOMBRE_TABLA = "reporte";
    const ID_REPORTE = "id_reporte";
    const ID_LIBRO = "id_libro";
    const FECHA_REPORT = "fecha_reporte";
    const DESCRIPCION = "descripcion";
    const NOMBRE_TABLAU = "usuario";
    const ID_USUARIO = "id_usuario";
    const NOMBRE = "nombre";
    const TOKEN = "token";

    public $nombreUsuario;

    function Header()
    {
        $this->SetFont('Arial','B',15);
        $this->Cell(0,10,'Reporte de Usuario: '.$this->nombreUsuario,0,1,'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }

    // Método para imprimir texto con soporte UTF-8
    function TextoUTF8($text)
    {
        $this->SetFont('Arial', '', 12);
        $this->MultiCell(0, 10, utf8_decode($text));
        $this->Ln();
    }
}

function autorizar() {
    if (isset($_GET["token"])) {
        $claveApi = $_GET["token"];

        if (validarClaveApi($claveApi)) {
            return obtenerIdUsuario($claveApi);
        } else {
            throw new ExcepcionApi(401, "Token no autorizado", "Token no autorizado");
        }
    } else {
        throw new ExcepcionApi(400, "Se requiere Token para autenticación", "Se requiere Token para autenticación");
    }
}

function validarClaveApi($claveApi) {
    $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
    $comando = "SELECT COUNT(" . PDF::ID_USUARIO . ") FROM " . PDF::NOMBRE_TABLAU . " WHERE " . PDF::TOKEN . " = ?";
    $sentencia = $pdo->prepare($comando);
    $sentencia->bindParam(1, $claveApi);
    $sentencia->execute();

    return $sentencia->fetchColumn(0) > 0;
}

function obtenerIdUsuario($claveApi) {
    $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
    $comando = "SELECT " . PDF::ID_USUARIO . " FROM " . PDF::NOMBRE_TABLAU . " WHERE " . PDF::TOKEN . " = ?";
    $sentencia = $pdo->prepare($comando);
    $sentencia->bindParam(1, $claveApi);

    if ($sentencia->execute()) {
        $resultado = $sentencia->fetch();
        return $resultado[PDF::ID_USUARIO];
    } else {
        return null;
    }
}

try {
    if (isset($_GET['token'])) {
        $token = $_GET['token'];

        $idUsuario = autorizar();

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
        
        // Obtener el nombre de usuario de la tabla usuario
        $comando = "SELECT " . PDF::NOMBRE . " FROM " . PDF::NOMBRE_TABLAU . " WHERE " . PDF::ID_USUARIO . " = ?";
        $sentencia = $pdo->prepare($comando);
        $sentencia->bindParam(1, $idUsuario);

        $nombreUsuario = 'Usuario Desconocido'; // Valor por defecto

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            $nombreUsuario = $resultado[PDF::NOMBRE];
        }

        // Obtener todos los reportes del usuario
        $comando = "SELECT " . PDF::ID_REPORTE . ", " . PDF::ID_LIBRO . ", " . PDF::FECHA_REPORT .  ", " . PDF::DESCRIPCION . " FROM " . PDF::NOMBRE_TABLA . " WHERE " . PDF::ID_USUARIO . " = ?";
        $sentencia = $pdo->prepare($comando);
        $sentencia->bindParam(1, $idUsuario);

        if ($sentencia->execute()) {
            // Crear el PDF
            $pdf = new PDF();
            $pdf->nombreUsuario = $nombreUsuario; // Establecer el nombre de usuario directamente
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->SetFont('Times','',12);

            // Agregar cada reporte al PDF
            while ($row = $sentencia->fetch(PDO::FETCH_ASSOC)) {
                $fechaReporte = new DateTime($row[PDF::FECHA_REPORT]);
                $fechaFormateada = $fechaReporte->format('d/m/Y');
                $pdf->Cell(0,10,'ID Reporte: '.$row[PDF::ID_REPORTE],0,1);
                $pdf->Cell(0,10,'ID Libro: '.$row[PDF::ID_LIBRO],0,1);
                $pdf->Cell(0,10,'Fecha Reporte: '.$fechaFormateada,0,1);
                $pdf->TextoUTF8('Descripción: '.$row[PDF::DESCRIPCION]); // Usar método personalizado para texto UTF-8
                $pdf->Ln(10);
            }

            // Limpiar el búfer de salida antes de enviar el PDF
            ob_clean();

            // Enviar encabezados para que el navegador interprete el contenido como un PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="reporte.pdf"');

            // Salida del PDF
            $pdf->Output('I', 'reporte.pdf');
            exit; // Asegurar que no haya más salida después de enviar el PDF
        } else {
            echo 'Error al obtener los datos de la base de datos.';
        }
    } else {
        echo 'Token no especificado.';
    }
} catch (PDOException $e) {
    echo 'Error de conexión a la base de datos: ' . $e->getMessage();
} catch (ExcepcionApi $e) {
    echo $e->getMessage();
}
?>
