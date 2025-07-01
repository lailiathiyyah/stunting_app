<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';
include 'naive_bayes.php'; // File yang berisi fungsi naive_bayes()

$report = naive_bayes($conn);
$confusion = $report['confusion_matrix'];
$classification = $report['classification_report'];
$accuracy = $report['accuracy'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Evaluasi Model - Sistem Stunting</title>
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon">
                    <i class="fa-regular fa-face-smile"></i>
                </div>
                <div class="sidebar-brand-text mx-2" style="white-space: normal; font-size: 0.9rem;">Stunting Risk Ranking System</div>
            </a>


            <hr class="sidebar-divider my-0">

            <!-- Nav Items -->
            <li class="nav-item active">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="tambah_data.php"><i class="fas fa-plus-circle"></i> <span>Tambah Data</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="data_balita.php"><i class="fas fa-table"></i> <span>Data Balita</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="ranking.php"><i class="fas fa-chart-line"></i> <span>Ranking Resiko Stunting</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="evaluasi_model.php"><i class="fas fa-chart-bar"></i> <span>Evaluasi Model Klasifikasi</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#" onclick="confirmLogout(event)">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
        <!-- End of Sidebar -->

        <!-- Content -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Judul halaman -->
                    <span class="font-weight-bold text-gray-800">Hasil Evaluasi Klasifikasi Naive Bayes</span>

                    <!-- Bagian kanan: Nama dan Avatar -->
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item d-flex align-items-center">
                            <!-- Nama Admin -->
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small font-weight-bold">
                                <?= $_SESSION['username'] ?? 'Admin' ?>
                            </span>
                            <!-- Avatar -->
                            <img class="img-profile rounded-circle"
                                src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'Admin') ?>&background=4e73df&color=fff&size=32"
                                width="32" height="32" alt="Admin">
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Classification Report</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Kelas</th>
                                            <th>Precision</th>
                                            <th>Recall</th>
                                            <th>F1-Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classification as $class => $metrics): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($class) ?></td>
                                                <td><?= number_format($metrics['precision'], 2) ?></td>
                                                <td><?= number_format($metrics['recall'], 2) ?></td>
                                                <td><?= number_format($metrics['f1_score'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Accuracy</th>
                                            <th><?= number_format($accuracy, 2) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <h6 class="m-0 font-weight-bold text-primary mt-4">Confusion Matrix</h6>
                            <canvas id="confusionMatrixChart" height="150"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const confusionMatrix = <?= json_encode($confusion) ?>;
        const labels = Object.keys(confusionMatrix);
        const trueLabels = labels;
        const datasets = [];

        labels.forEach((predLabel, i) => {
            const data = [];
            labels.forEach(actualLabel => {
                data.push(confusionMatrix[actualLabel][predLabel] ?? 0);
            });

            datasets.push({
                label: `Prediksi: ${predLabel}`,
                data: data,
                backgroundColor: `rgba(${50 + i * 50}, 99, 132, 0.6)`,
                borderColor: `rgba(${50 + i * 50}, 99, 132, 1)`,
                borderWidth: 1
            });
        });

        const ctx = document.getElementById('confusionMatrixChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: trueLabels,
                datasets: datasets
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Confusion Matrix'
                    },
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah'
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmLogout(event) {
            event.preventDefault();
            if (confirm("Apakah Anda yakin ingin logout?")) {
                window.location.href = "logout.php";
            }
        }
    </script>

</body>
</html>
