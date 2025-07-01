<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';
include 'naive_bayes.php';

$message = '';
$hasil_prediksi = null;
$is_risiko = false;

function alertClassByStatus($status) {
    $status = strtolower($status);
    if (in_array($status, ['gizi buruk', 'gizi kurang'])) return 'alert-danger';
    if (in_array($status, ['gizi normal', 'gizi lebih'])) return 'alert-success';
    return 'alert-secondary';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_balita     = trim($_POST['id_balita']);
    $nama_balita   = trim($_POST['nama_balita']);
    $jenis_kelamin = (int)$_POST['jenis_kelamin'];
    $umur          = (float)$_POST['umur'];
    $berat_kg      = (float)$_POST['berat_kg'];
    $tinggi_cm     = (float)$_POST['tinggi_cm'];

    $tinggi_m = $tinggi_cm / 100;
    $tinggi_m2 = $tinggi_m * $tinggi_m;
    $imt = $berat_kg / $tinggi_m2;

    $cek_id = $conn->prepare("SELECT id_balita FROM data_balita_processed WHERE id_balita = ?");
    $cek_id->bind_param("s", $id_balita);
    $cek_id->execute();
    $cek_result = $cek_id->get_result();

    if ($cek_result->num_rows > 0) {
        $message = "<strong>ID Balita sudah terdaftar.</strong> Silakan gunakan ID yang lain.";
    } else {
        list($X, $y) = loadProcessedData($conn);
        $total_data = count($y);
        $train_count = (int)($total_data * 0.8);
        $X_train = array_slice($X, 0, $train_count);
        $y_train = array_slice($y, 0, $train_count);
        $model = trainNaiveBayes($X_train, $y_train);
        $data_baru = [$jenis_kelamin, $umur, $tinggi_cm, $tinggi_m, $tinggi_m2, $berat_kg, $imt];
        $hasil_prediksi = predict($model, $data_baru);

        $stmt = $conn->prepare("INSERT INTO data_balita_processed (id_balita, nama_balita, jenis_kelamin, umur, berat_kg, tinggi_cm, tinggi_m, tinggi_m2, imt, status_gizi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssidddddds", $id_balita, $nama_balita, $jenis_kelamin, $umur, $berat_kg, $tinggi_cm, $tinggi_m, $tinggi_m2, $imt, $hasil_prediksi);
        $success = $stmt->execute();

        if ($success) {
            $message = "Data berhasil disimpan. Prediksi status gizi: <strong>$hasil_prediksi</strong>";
            if (in_array(strtolower($hasil_prediksi), ['gizi buruk', 'gizi kurang'])) {
                $_SESSION['recent_id'] = $id_balita;
                $is_risiko = true;
            } else {
                unset($_SESSION['recent_id']);
            }
        } else {
            $message = "Gagal menyimpan data: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Data Balita - Sistem Pemeringkatan Risiko Stunting</title>
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">
    <!-- Sidebar -->
    <ul class="navbar-nav bg-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
            <div class="sidebar-brand-icon">
                <i class="fa-regular fa-face-smile"></i>
            </div>
            <div class="sidebar-brand-text mx-2" style="white-space: normal; font-size: 0.9rem;">Stunting Risk Ranking System</div>
        </a>
        <hr class="sidebar-divider my-0">
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item active"><a class="nav-link" href="tambah_data.php"><i class="fas fa-plus-circle"></i> <span>Tambah Data</span></a></li>
        <li class="nav-item"><a class="nav-link" href="data_balita.php"><i class="fas fa-table"></i> <span>Data Balita</span></a></li>
        <li class="nav-item"><a class="nav-link" href="ranking.php"><i class="fas fa-chart-line"></i> <span>Ranking Resiko Stunting</span></a></li>
        <li class="nav-item"><a class="nav-link" href="evaluasi_model.php"><i class="fas fa-chart-bar"></i> <span>Evaluasi Model Klasifikasi</span></a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 shadow">
                <span class="font-weight-bold text-gray-800">Tambah Data Balita & Prediksi Gizi</span>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item d-flex align-items-center">
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small font-weight-bold">
                            <?= $_SESSION['username'] ?? 'Admin' ?>
                        </span>
                        <img class="img-profile rounded-circle" src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'Admin') ?>&background=4e73df&color=fff&size=32" width="32" height="32" alt="Admin">
                    </li>
                </ul>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid">
                <?php if ($message): ?>
                <div class="alert <?= strpos($message, 'berhasil') ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
                </div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-baby text-primary mr-2"></i> Form Tambah Data Balita</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="id_balita">ID Balita</label>
                                    <input type="text" class="form-control" name="id_balita" id="id_balita" required value="<?= htmlspecialchars($_POST['id_balita'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="nama_balita">Nama Balita</label>
                                    <input type="text" class="form-control" name="nama_balita" id="nama_balita" required value="<?= htmlspecialchars($_POST['nama_balita'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="jenis_kelamin">Jenis Kelamin</label>
                                    <select class="form-control" name="jenis_kelamin" id="jenis_kelamin" required>
                                        <option disabled <?= !isset($_POST['jenis_kelamin']) ? 'selected' : '' ?>>Pilih</option>
                                        <option value="1" <?= (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == "1") ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="0" <?= (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == "0") ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="umur">Umur (bulan)</label>
                                    <input type="number" step="0.1" class="form-control" name="umur" id="umur" required value="<?= htmlspecialchars($_POST['umur'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="berat_kg">Berat Badan (kg)</label>
                                    <input type="number" step="0.1" class="form-control" name="berat_kg" id="berat_kg" required value="<?= htmlspecialchars($_POST['berat_kg'] ?? '') ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="tinggi_cm">Tinggi Badan (cm)</label>
                                    <input type="number" step="0.1" class="form-control" name="tinggi_cm" id="tinggi_cm" required value="<?= htmlspecialchars($_POST['tinggi_cm'] ?? '') ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-icon-split">
                                <span class="icon text-white-50"><i class="fas fa-save"></i></span>
                                <span class="text">Simpan & Prediksi</span>
                            </button>
                        </form>
                    </div>
                    <hr class="mt-4">

                        <!-- Langkah-langkah proses -->

                        <div class="d-flex justify-content-center align-items-center mb-4">
                            <!-- Langkah 1 -->
                            <div class="text-center mx-3">
                                <div class="icon-circle bg-primary text-white shadow-sm mb-2">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="small font-weight-bold text-primary">Tambah Data</div>
                            </div>

                            <!-- Garis -->
                            <div style="width: 40px; height: 2px; background-color: #4e73df; margin: 0 5px;"></div>

                            <!-- Langkah 2 -->
                            <div class="text-center mx-3">
                                <div class="icon-circle bg-success text-white shadow-sm mb-2">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                                <div class="small font-weight-bold text-success">Prediksi</div>
                            </div>

                            <!-- Garis -->
                            <div style="width: 40px; height: 2px; background-color: #1cc88a; margin: 0 5px;"></div>

                            <!-- Langkah 3 -->
                            <div class="text-center mx-3">
                                <div class="icon-circle bg-light text-dark shadow-sm border mb-2">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="small font-weight-bold text-gray-800">SAW Ranking</div>
                            </div>
                        </div>

                </div>

                <?php if ($hasil_prediksi !== null): ?>
                <div class="modal fade" id="prediksiModal" tabindex="-1" role="dialog" aria-labelledby="prediksiModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content border-left-<?= $is_risiko ? 'danger' : 'success' ?>">
                      <div class="modal-header bg-<?= $is_risiko ? 'danger' : 'success' ?> text-white">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-heartbeat mr-2"></i> Hasil Prediksi</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                      </div>
                      <div class="modal-body text-center">
                        <h4 class="font-weight-bold"><?= htmlspecialchars($hasil_prediksi) ?></h4>
                        <p class="text-muted"><?= $is_risiko ? 'Balita termasuk dalam risiko stunting.' : 'Balita berada dalam status gizi yang baik.' ?></p>
                      </div>
                      <div class="modal-footer justify-content-between">
                        <?php if ($is_risiko): ?>
                          <a href="ranking.php?highlight=<?= urlencode($id_balita) ?>" class="btn btn-danger btn-sm"><i class="fas fa-exclamation-triangle"></i> Lihat Risiko</a>
                        <?php endif; ?>
                        <a href="tambah_data.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmLogout(event) {
    event.preventDefault();
    if (confirm("Apakah Anda yakin ingin logout?")) {
        window.location.href = "logout.php";
    }
}
</script>
<?php if ($hasil_prediksi !== null): ?>
<script>$(document).ready(function () { $('#prediksiModal').modal('show'); });</script>
<?php endif; ?>
</body>
</html>
