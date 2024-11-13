<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "api_test";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificamos el envío correcto de los datos del formulario a través de POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellido_paterno = $_POST['apellido_paterno'];
    $apellido_materno = $_POST['apellido_materno'];
    $fono = $_POST['fono'];

    // Insertamos los datos en la tabla usuario
    $sql = "INSERT INTO usuario (nombre, apellido_paterno, apellido_materno, fono) VALUES ('$nombre', '$apellido_paterno', '$apellido_materno', '$fono')";

    if ($conn->query($sql) === TRUE) {
        // Preparamos los datos para el webhook
        $data = json_encode([
            'nombre' => $nombre,
            'apellido_paterno' => $apellido_paterno,
            'apellido_materno' => $apellido_materno,
            'fono' => $fono,
        ]);

        // configuramos la url del webhook y lo enviamos
        $webhookUrl = 'http://localhost:3000/webhook';
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => $data,
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($webhookUrl, false, $context);

        echo "Datos guardados y webhook enviado.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Obtener todos los registros de usuario
    $sql = "SELECT * FROM usuario";
    $result = $conn->query($sql);

    $usuarios = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }

    // Enviamos los datos como JSON
    header('Content-Type: application/json');
    echo json_encode($usuarios);

    // Enviamos los datos obtenidos como webhook al middleware de Node.js
    $data = json_encode($usuarios);
    $webhookUrl = 'http://localhost:3000/webhook_batch';
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => $data,
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($webhookUrl, false, $context);
}

$conn->close();
?>
