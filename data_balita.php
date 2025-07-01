<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';
include 'preprocess_training.php';

preprocessDataBalita($conn);

$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStatus = isset($_GET['filter_status']) ? $conn->real_escape_string(trim($_GET['filter_status'])) : '';
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : '';
$sortDir = isset($_GET['sort_dir']) && in_array($_GET['sort_dir'], ['ASC', 'DESC']) ? $_GET['sort_dir'] : 'ASC';

$whereClauses = [];
if ($search !== '') {
    $search_esc = $conn->real_escape_string($search);
    $whereClauses[] = "nama_balita LIKE '%$search_esc%'";
}
if ($filterStatus !== '') {
    $whereClauses[] = "status_gizi = '$filterStatus'";
}
$where = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$allowedSortFields = ['nama_balita', 'umur', 'imt'];
$orderBy = in_array($sortBy, $allowedSortFields) ? "ORDER BY $sortBy $sortDir" : "ORDER BY no ASC";

$totalResult = $conn->query("SELECT COUNT(*) AS total FROM data_balita_processed $where");
$totalRow = $totalResult->fetch_assoc();
$totalData = $totalRow['total'];
$totalPages = ceil($totalData / $limit);

$query = "SELECT * FROM data_balita_processed $where $orderBy LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

function buildQuery($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Data Balita - Sistem Stunting</title>
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            display: inline-block;
        }
        .status-gizi-gizi-buruk { background-color: #e74c3c; }
        .status-gizi-gizi-kurang { background-color: #f39c12; }
        .status-gizi-gizi-normal { background-color: #2ecc71; }
        .status-gizi-gizi-lebih { background-color: #3498db; }
        .table td, .table th { vertical-align: middle !important; }
        .table-hover tbody tr:hover { background-color: #f1f1f1; }
        .table th { white-space: nowrap; }
    </style>
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
        <li class="nav-item ">
            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="tambah_data.php"><i class="fas fa-plus-circle"></i> <span>Tambah Data</span></a>
        </li>

        <li class="nav-item active">
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
            </nav>
            <div class="container-fluid">
                <!-- Search -->
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control bg-light border-1 small" placeholder="Cari nama balita..." value="<?php echo htmlspecialchars($search); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search fa-sm"></i></button>
                        </div>
                    </div>
                </form>
                <!-- Filter & Sort -->
                <form method="GET" class="form-inline mb-4">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <label class="mr-2">Status Gizi:</label>
                    <select name="filter_status" class="form-control mr-3">
                        <option value="">Semua</option>
                        <?php
                        $statuses = ['Gizi Buruk', 'Gizi Kurang', 'Gizi Normal', 'Gizi Lebih'];
                        foreach ($statuses as $status) {
                            $selected = $filterStatus === $status ? 'selected' : '';
                            echo "<option value=\"$status\" $selected>$status</option>";
                        }
                        ?>
                    </select>
                    <label class="mr-2">Urutkan:</label>
                    <select name="sort_by" class="form-control mr-2">
                        <option value="">Default</option>
                        <option value="nama_balita" <?php echo $sortBy === 'nama_balita' ? 'selected' : ''; ?>>Nama</option>
                        <option value="umur" <?php echo $sortBy === 'umur' ? 'selected' : ''; ?>>Umur</option>
                    </select>
                    <select name="sort_dir" class="form-control mr-2">
                        <option value="ASC" <?php echo $sortDir === 'ASC' ? 'selected' : ''; ?>>Naik</option>
                        <option value="DESC" <?php echo $sortDir === 'DESC' ? 'selected' : ''; ?>>Turun</option>
                    </select>
                    <button type="submit" class="btn btn-secondary">Terapkan</button>
                </form>
                <!-- Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-baby text-primary mr-2"></i>Tabel Data Balita</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>ID Balita</th>
                                    <th>Nama</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Umur</th>
                                    <th>Berat</th>
                                    <th>Tinggi</th>
                                    <th>Status Gizi</th>
                                    <th>Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['no'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['id_balita']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_balita']); ?></td>
                                            <td><?php echo $row['jenis_kelamin'] == 1 ? 'L' : 'P'; ?></td>
                                            <td><?php echo htmlspecialchars($row['umur']); ?></td>
                                            <td><?php echo htmlspecialchars($row['berat_kg']); ?></td>
                                            <td><?php echo htmlspecialchars($row['tinggi_cm']); ?></td>
                                            <td><span class="status-badge <?php echo 'status-gizi-' . strtolower(str_replace(' ', '-', $row['status_gizi'])); ?>"><?php echo htmlspecialchars($row['status_gizi']); ?></span></td>
                                            <td><button class="btn btn-info btn-sm" data-toggle="modal" data-target="#detailModal" data-id="<?php echo htmlspecialchars($row['id_balita']); ?>" data-nama="<?php echo htmlspecialchars($row['nama_balita']); ?>" data-umur="<?php echo htmlspecialchars($row['umur']); ?>" data-berat="<?php echo htmlspecialchars($row['berat_kg']); ?>" data-tinggi_cm="<?php echo htmlspecialchars($row['tinggi_cm']); ?>" data-tinggi_m="<?php echo htmlspecialchars($row['tinggi_m']); ?>" data-tinggi_m2="<?php echo htmlspecialchars($row['tinggi_m2']); ?>" data-imt="<?php echo htmlspecialchars($row['imt']); ?>" data-status="<?php echo htmlspecialchars($row['status_gizi']); ?>"><i class='fas fa-eye'></i> Detail</button></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" class="text-center">Data tidak ditemukan.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <nav><ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo buildQuery(['page' => $page - 1]); ?>">Prev</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildQuery(['page' => $i]); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo buildQuery(['page' => $page + 1]); ?>">Next</a>
                            </li>
                        </ul></nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Balita -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="detailModalLabel"><i class="fas fa-baby me-2"></i> Detail Data Balita</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body px-4">
        <div class="row mb-2">
          <div class="col-md-6">
            <strong>ID Balita:</strong><br>
            <span id="detail-id"></span>
          </div>
          <div class="col-md-6">
            <strong>Nama:</strong><br>
            <span id="detail-nama"></span>
          </div>
        </div>
        <div class="row mb-2">
          <div class="col-md-6">
            <strong>Umur (bulan):</strong><br>
            <span id="detail-umur"></span>
          </div>
          <div class="col-md-6">
            <strong>Berat (kg):</strong><br>
            <span id="detail-berat"></span>
          </div>
        </div>
        <div class="row mb-2">
          <div class="col-md-6">
            <strong>Tinggi (cm):</strong><br>
            <span id="detail-tinggi-cm"></span>
          </div>
          <div class="col-md-6">
            <strong>Tinggi (m):</strong><br>
            <span id="detail-tinggi-m"></span>
          </div>
        </div>
        <div class="row mb-2">
          <div class="col-md-6">
            <strong>Tinggi² (m²):</strong><br>
            <span id="detail-tinggi-m2"></span>
          </div>
          <div class="col-md-6">
            <strong>IMT:</strong><br>
            <span id="detail-imt"></span>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col text-center">
            <strong class="d-block mb-2">Status Gizi:</strong>
            <span id="detail-status" class="status-badge font-weight-bold px-4 py-2"></span>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-outline-primary" onclick="window.print()">Cetak</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmLogout(event) {
        event.preventDefault();
        if (confirm("Apakah Anda yakin ingin logout?")) {
            window.location.href = "logout.php";
        }
    }
</script>
<script>
    $('#detailModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const modal = $(this);

        modal.find('#detail-id').text(button.data('id'));
        modal.find('#detail-nama').text(button.data('nama'));
        modal.find('#detail-umur').text(button.data('umur'));
        modal.find('#detail-berat').text(button.data('berat'));
        modal.find('#detail-tinggi-cm').text(button.data('tinggi_cm'));
        modal.find('#detail-tinggi-m').text(button.data('tinggi_m'));
        modal.find('#detail-tinggi-m2').text(button.data('tinggi_m2'));
        modal.find('#detail-imt').text(button.data('imt'));
        modal.find('#detail-status')
            .text(button.data('status'))
            .attr('class', 'status-badge font-weight-bold px-4 py-2 status-gizi-' + button.data('status').toLowerCase().replace(/\s+/g, '-'));
    });

</script>
</body>
</html>
