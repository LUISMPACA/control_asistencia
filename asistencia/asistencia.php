<?php
// Cargar configuración de base de datos de un archivo externo
$config = require __DIR__ . '/config.php';
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = $_POST["nro_documento"];

    $conn = new mysqli(
        $config['host'],
        $config['user'],
        $config['password'],
        $config['database']
    );
    $conn->set_charset("utf8");

    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Verificar si ya tiene asistencia
    $checkSql = "SELECT asistencia FROM inscripcion_simulacros WHERE nro_documento = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $checkResult = $stmt->get_result();
    $yaRegistrado = false;
    if ($rowCheck = $checkResult->fetch_assoc()) {
        if ($rowCheck['asistencia'] == 1) {
            $yaRegistrado = true;
        } else {
            // Registrar asistencia solo si no estaba registrada
            $updateSql = "UPDATE inscripcion_simulacros SET asistencia = 1 WHERE nro_documento = ?";
            $stmt2 = $conn->prepare($updateSql);
            $stmt2->bind_param("s", $dni);
            $stmt2->execute();
        }
    }

    // Mostrar datos del estudiante
    $query = "
        SELECT 
            e.nro_documento,
            CONCAT(e.paterno, ' ', e.materno, ' ', e.nombres) AS nombre_completo,
            s.denominacion AS sede,
            ar.denominacion AS area,
            CONCAT('https://sistemas.cepreuna.edu.pe/storage/fotos/', e.foto) AS foto_url
        FROM estudiantes e
            JOIN matriculas m ON m.estudiantes_id = e.id
            JOIN grupo_aulas ga ON ga.id = m.grupo_aulas_id
            JOIN aulas a ON a.id = ga.aulas_id
            JOIN locales l ON l.id = a.locales_id
            JOIN sedes s ON s.id = l.sedes_id
            JOIN areas ar ON ar.id = ga.areas_id
        WHERE e.nro_documento = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencia Estudiante</title>
     <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #ffb347, #fc6e37);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: #fffdf7;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(255, 140, 0, 0.2);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease-in-out;
            text-align: center;
        }

        h2 {
            text-align: center;
            color: #e67e22;
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            color: #555;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 6px;
            border: 1px solid #e67e22;
            outline: none;
        }

        input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background-color: #e67e22;
            border: none;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color:rgb(255, 131, 23);
        }

        .mensaje {
            margin-top: 20px;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
        }

        .exito {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .error {
            background-color: #f2dede;
            color: #a94442;
        }

        .info {
            background-color: #fcf8e3;
            color: #8a6d3b;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .foto {
            width: 198.4px;
            height: 255.2px;
        }

    </style>
</head>
<body>
    <div class="container">
        <h2>EXAMEN DE SIMULACRO CEPREUNA 2025-I</h2>
        <img src="logo_2.jpg" alt="Descripción de la imagen" >
        <form method="POST">
            <label for="nro_documento">Número de Documento:</label>
            <input type="text" name="nro_documento" required>
            <input type="submit" value="Registrar Asistencia">
        </form>

        <?php if (isset($result) && $row = $result->fetch_assoc()): ?>
            <div class="resultado">
                <h3>Datos del Estudiante</h3>
                <img class="foto" src="<?= $row['foto_url'] ?>" alt="Foto del estudiante"><br>
                <strong>DNI:</strong> <?= $row['nro_documento'] ?><br>
                <strong>Nombre:</strong> <?= $row['nombre_completo'] ?><br>
                <strong>Sede:</strong> <?= $row['sede'] ?><br>
                <strong>Área:</strong> <?= $row['area'] ?><br>
                <?php if (isset($yaRegistrado) && $yaRegistrado): ?>
                    <p class="info">ℹ️ La asistencia de este estudiante ya fue registrada previamente.</p>
                <?php else: ?>
                    <p class="ok">✅ Asistencia registrada correctamente</p>
                <?php endif; ?>
            </div>
        <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <p class="error">❌ Estudiante no encontrado.</p>
        <?php endif; ?>
    </div>
</body>
</html>