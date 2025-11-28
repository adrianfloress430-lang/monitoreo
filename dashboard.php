<?php
session_start();
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Obtener último nivel para tanque
// Funcionalidad: Consulta BD para datos en tiempo real y históricos.
$stmt = $pdo->query("SELECT * FROM water_levels ORDER BY timestamp DESC LIMIT 1");
$last_level = $stmt->fetch();

// Datos históricos para Chart.js (últimos 10 registros)
$historical = $pdo->query("SELECT * FROM water_levels ORDER BY timestamp DESC LIMIT 10")->fetchAll();
$labels = [];
$data_levels = [[], [], [], [], []];  // Para cada nivel

foreach (array_reverse($historical) as $row) {  // Invertir para cronológico
    $labels[] = $row['timestamp'];
    $data_levels[0][] = $row['level1'];
    $data_levels[1][] = $row['level2'];
    $data_levels[2][] = $row['level3'];
    $data_levels[3][] = $row['level4'];
    $data_levels[4][] = $row['level5'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/script.js"></script>
</head>
<body>
    <div class="container">
        <h2>Monitoreo de Tanque de Agua</h2>
        <?php if ($_SESSION['role'] == 'admin') { ?>
            <a href="admin/manage_users.php" class="btn btn-secondary">Gestionar Usuarios</a>
        <?php } ?>
        <div id="tank" class="tank">
            <!-- Tanque con 5 niveles apilados (abajo level1) -->
            <div class="level" id="level5"></div>  <!-- Superior -->
            <div class="level" id="level4"></div>
            <div class="level" id="level3"></div>
            <div class="level" id="level2"></div>
            <div class="level" id="level1"></div>  <!-- Inferior -->
        </div>
        <canvas id="historicalChart" width="400" height="200"></canvas>
    </div>

    <script>
        // Inicializar tanque con datos iniciales
        updateTank(<?php echo json_encode($last_level); ?>);
        
        // Polling para actualización en tiempo real
        setInterval(function() {
            $.ajax({
                url: 'api/get_latest.php',  // Endpoint para último nivel (crear abajo)
                method: 'GET',
                success: function(data) {
                    updateTank(data);
                }
            });
        }, 5000);  // Cada 5 segundos

        // Inicializar gráfico histórico
        const ctx = document.getElementById('historicalChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [
                    { label: 'Nivel 1', data: <?php echo json_encode($data_levels[0]); ?> },
                    { label: 'Nivel 2', data: <?php echo json_encode($data_levels[1]); ?> },
                    { label: 'Nivel 3', data: <?php echo json_encode($data_levels[2]); ?> },
                    { label: 'Nivel 4', data: <?php echo json_encode($data_levels[3]); ?> },
                    { label: 'Nivel 5', data: <?php echo json_encode($data_levels[4]); ?> }
                ]
            },
            options: { scales: { y: { beginAtZero: true, max: 1 } } }
        });
    </script>
</body>
</html>