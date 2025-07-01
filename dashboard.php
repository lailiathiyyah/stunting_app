<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';

$total  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM data_balita_processed"))['total'];
$normal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as normal FROM data_balita_processed WHERE status_gizi='Gizi Normal'"))['normal'];
$kurang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as kurang FROM data_balita_processed WHERE status_gizi='Gizi Kurang'"))['kurang'];
$buruk  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as buruk FROM data_balita_processed WHERE status_gizi='Gizi Buruk'"))['buruk'];
$lebih  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as lebih FROM data_balita_processed WHERE status_gizi='Gizi Lebih'"))['lebih'];

$risiko = $kurang + $buruk;
$balita_risiko_stunting = $risiko;
$balita_tidak_stunting = $normal + $lebih;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Sistem Pemeringkatan Risiko Stunting</title>
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
                <span class="font-weight-bold text-gray-800">Dashboard</span>

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
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Balita</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Gizi Normal</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $normal ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Gizi Kurang</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $kurang ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Gizi Buruk</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $buruk ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Gizi Lebih</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $lebih ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-dark shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Risiko Stunting</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $risiko ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafik -->
                <div class="row">
                    <!-- Pie Chart: Perbandingan Risiko -->
                    <div class="col-xl-6 col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-primary text-white">
                                <h6 class="m-0 font-weight-bold">Perbandingan Risiko Stunting</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="pieChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Bar Chart -->
                    <div class="col-xl-6 col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-info text-white">
                                <h6 class="m-0 font-weight-bold">Jumlah Status Gizi</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="barChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- JS Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
// Pie Chart: Risiko vs Tidak Risiko
const pieCtx = document.getElementById("pieChart").getContext('2d');
new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: ["Berisiko Stunting", "Tidak Berisiko Stunting"],
        datasets: [{
            data: [<?= $balita_risiko_stunting ?>, <?= $balita_tidak_stunting ?>],
            backgroundColor: ['#e74a3b', '#1cc88a'],
        }]
    },
    options: {
        responsive: true,
        legend: { position: 'bottom' },
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    const dataset = data.datasets[tooltipItem.datasetIndex];
                    const total = dataset.data.reduce((a, b) => a + b, 0);
                    const current = dataset.data[tooltipItem.index];
                    const percent = ((current / total) * 100).toFixed(1);
                    return data.labels[tooltipItem.index] + ': ' + current + ' (' + percent + '%)';
                }
            }
        }
    }
});

// Bar Chart: 4 Kategori Gizi
const barCtx = document.getElementById("barChart").getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: ["Gizi Normal", "Gizi Kurang", "Gizi Buruk", "Gizi Lebih"],
        datasets: [{
            label: 'Jumlah',
            data: [<?= $normal ?>, <?= $kurang ?>, <?= $buruk ?>, <?= $lebih ?>],
            backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc'],
        }]
    },
    options: {
        responsive: true,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true } }]
        }
    }
});
</script>
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
