<?php
$host = 'mariadb';          // Si estás dentro de Docker, usa el nombre del servicio. 
                            // Si estás fuera, usa 'localhost' o la IP del host.
$dbname = 'mi_base_de_datos';
$user = 'usuario';
$pass = 'password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

try {
    // Crear la conexión PDO
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,   // Modo de errores con excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Devuelve los resultados como arrays asociativos
    ]);

    echo "✅ Conexión exitosa a la base de datos<br>";

    // Ejecutar una consulta SQL
    $sql = "SELECT * FROM usuarios"; // ejemplo de tabla
    $stmt = $pdo->query($sql);

    // Mostrar resultados
    foreach ($stmt as $fila) {
        echo "ID: {$fila['id']} - Nombre: {$fila['nombre']}<br>";
    }

} catch (PDOException $e) {
    echo "❌ Error de conexión o consulta: " . $e->getMessage();
}

if (!empty($_SERVER['SERVER_ADDR'])) {
    echo "SERVER_ADDR: " . $_SERVER['SERVER_ADDR'] . "\n";
} else {
    echo "SERVER_ADDR no está disponible\n";
}
?>
