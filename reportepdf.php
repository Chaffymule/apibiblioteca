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
    //datos de la tabla reporte pdf
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
    if (isset($_GET['id']) && isset($_GET['token'])) {
        $id_reporte = $_GET['id'];
        $token = $_GET['token'];

        $idUsuario = autorizar();

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
        $comando = "SELECT " . PDF::NOMBRE . " FROM " . PDF::NOMBRE_TABLAU . " WHERE " . PDF::ID_USUARIO . " = ?";
        $sentencia = $pdo->prepare($comando);
        $sentencia->bindParam(1, $idUsuario);

        $nombreUsuario = 'Usuario Desconocido'; // Valor por defecto

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            $nombreUsuario = $resultado[PDF::NOMBRE];
        }

        // Crear el PDF
        $pdf = new PDF();
        $pdf->nombreUsuario = $nombreUsuario; // Establecer el nombre de usuario directamente
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Times','',12);

        // Obtener y mostrar los datos del reporte
        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
        $sentencia = $pdo->prepare("SELECT " . PDF::ID_LIBRO . ", " . PDF::FECHA_REPORT .  ", " . PDF::DESCRIPCION ." FROM " . PDF::NOMBRE_TABLA . " WHERE " . PDF::ID_REPORTE . " = ?");

        if ($sentencia->execute([$id_reporte])) {
            while ($row = $sentencia->fetch(PDO::FETCH_ASSOC)) {
                $pdf->Cell(0,10,'ID Reporte: '.$id_reporte,0,1);
                $pdf->Cell(0,10,'ID Libro: '.$row[PDF::ID_LIBRO],0,1);
                $pdf->Cell(0,10,'Fecha Reporte: '.$row[PDF::FECHA_REPORT],0,1);
                $pdf->Cell(0, 10, 'Descripción: ' . $row[PDF::DESCRIPCION], 0, 1);
                $pdf->Ln(10);
            }
        } else {
            // Manejo de error en la consulta
            $pdf->Cell(0,10,'Error al obtener los datos de la base de datos.',0,1);
        }

        // Enviar encabezados para que el navegador interprete el contenido como un PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="reporte.pdf"');

        // Mostrar el PDF en el navegador
        $pdf->Output('D', 'reporte.pdf');
    } else {
        echo 'ID de reporte o token no especificados.';
    }
} catch (PDOException $e) {
    echo 'Error de conexión a la base de datos: ' . $e->getMessage();
} catch (ExcepcionApi $e) {
    echo $e->getMessage();
}
?>
