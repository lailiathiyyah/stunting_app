<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$recent_id = $_SESSION['recent_id'] ?? null;
unset($_SESSION['recent_id']);

include 'db.php';

$sql = "SELECT id_balita, nama_balita, jenis_kelamin, umur, berat_kg, tinggi_cm, imt, status_gizi 
        FROM data_balita_processed 
        WHERE status_gizi IN ('Gizi Buruk', 'Gizi Kurang')";
$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['id_balita']] = $row;
}

function mean($arr) { return array_sum($arr) / count($arr); }
function stddev($arr, $mean) {
    return sqrt(array_sum(array_map(fn($x) => pow($x - $mean, 2), $arr)) / (count($arr) - 1));
}
function zscore($value, $mean, $std) { return ($std == 0) ? 0 : ($value - $mean) / $std; }
function zscore_to_weight($z) {
    if ($z <= -3) return 1;
    if ($z > -3 && $z < -2) return 2;
    if ($z >= -2 && $z <= 2) return 3;
    return 4;
}

$weights = ['tb' => 0.30, 'bb' => 0.25, 'bb_tb' => 0.25, 'imt' => 0.20];

$tb = array_column($data, 'tinggi_cm');
$bb = array_column($data, 'berat_kg');
$imt = array_column($data, 'imt');
$bb_tb = array_map(fn($d) => $d['berat_kg'] / $d['tinggi_cm'], $data);

$mean_tb = mean($tb);        $std_tb = stddev($tb, $mean_tb);
$mean_bb = mean($bb);        $std_bb = stddev($bb, $mean_bb);
$mean_bb_tb = mean($bb_tb);  $std_bb_tb = stddev($bb_tb, $mean_bb_tb);
$mean_imt = mean($imt);      $std_imt = stddev($imt, $mean_imt);

$alternatif = $transformasi = $normalisasi = $terbobot = $ranking = [];

foreach ($data as $id => $d) {
    $bb_tb_val = $d['berat_kg'] / $d['tinggi_cm'];
    $z_tb = zscore($d['tinggi_cm'], $mean_tb, $std_tb);
    $z_bb = zscore($d['berat_kg'], $mean_bb, $std_bb);
    $z_bb_tb = zscore($bb_tb_val, $mean_bb_tb, $std_bb_tb);
    $z_imt = zscore($d['imt'], $mean_imt, $std_imt);

    $w_tb = zscore_to_weight($z_tb);
    $w_bb = zscore_to_weight($z_bb);
    $w_bb_tb = zscore_to_weight($z_bb_tb);
    $w_imt = zscore_to_weight($z_imt);

    $n_tb = $w_tb / 4;
    $n_bb = $w_bb / 4;
    $n_bb_tb = $w_bb_tb / 4;
    $n_imt = $w_imt / 4;

    $score = $n_tb * $weights['tb'] + $n_bb * $weights['bb'] + $n_bb_tb * $weights['bb_tb'] + $n_imt * $weights['imt'];

    $alternatif[] = ['id' => $id, 'nama' => $d['nama_balita'], 'z_tb' => round($z_tb, 4), 'z_bb' => round($z_bb, 4), 'z_bb_tb' => round($z_bb_tb, 4), 'z_imt' => round($z_imt, 4)];
    $transformasi[] = ['id' => $id, 'nama' => $d['nama_balita'], 'tb_u' => $w_tb, 'bb_u' => $w_bb, 'bb_tb' => $w_bb_tb, 'imt_u' => $w_imt];
    $normalisasi[] = ['id' => $id, 'nama' => $d['nama_balita'], 'tb_u' => round($n_tb, 3), 'bb_u' => round($n_bb, 3), 'bb_tb' => round($n_bb_tb, 3), 'imt_u' => round($n_imt, 3)];
    $terbobot[] = ['id' => $id, 'nama' => $d['nama_balita'], 'tb_u' => round($n_tb * $weights['tb'], 3), 'bb_u' => round($n_bb * $weights['bb'], 3), 'bb_tb' => round($n_bb_tb * $weights['bb_tb'], 3), 'imt_u' => round($n_imt * $weights['imt'], 3)];
    $ranking[] = [
        'id' => $id,
        'nama' => $d['nama_balita'],
        'jenis_kelamin' => $d['jenis_kelamin'],
        'umur' => $d['umur'],
        'berat_kg' => $d['berat_kg'],
        'tinggi_cm' => $d['tinggi_cm'],
        'score' => round($score, 4),
        'status_gizi' => $d['status_gizi']
    ];

}

usort($ranking, fn($a, $b) => $a['score'] <=> $b['score']);
?>

<!-- HTML output lanjutan (struktur dashboard + tab + modal + JS) -->
<!-- Karena karakter terbatas, bagian selanjutnya langsung aku kirimkan di pesan berikut -->

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ranking Risiko Stunting</title>
  <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <style>
    .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; color: white; display: inline-block; }
    .status-gizi-gizi-buruk { background-color: #e74c3c; }
    .status-gizi-gizi-kurang { background-color: #f39c12; }
    .nav-tabs .nav-link.active { font-weight: bold; color: #4e73df !important; }
    table th, table td { vertical-align: middle; }
  </style>
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
        <li class="nav-item"><a class="nav-link" href="tambah_data.php"><i class="fas fa-plus-circle"></i> <span>Tambah Data</span></a></li>
        <li class="nav-item"><a class="nav-link" href="data_balita.php"><i class="fas fa-table"></i> <span>Data Balita</span></a></li>
        <li class="nav-item active"><a class="nav-link" href="ranking.php"><i class="fas fa-chart-line"></i> <span>Ranking Resiko Stunting</span></a></li>
        <li class="nav-item"><a class="nav-link" href="evaluasi_model.php"><i class="fas fa-chart-bar"></i> <span>Evaluasi Model Klasifikasi</span></a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 shadow">
                <span class="font-weight-bold text-gray-800">Ranking Risiko Stunting Balita</span>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item d-flex align-items-center">
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small font-weight-bold">
                            <?= $_SESSION['username'] ?? 'Admin' ?>
                        </span>
                        <img class="img-profile rounded-circle" src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'Admin') ?>&background=4e73df&color=fff&size=32" width="32" height="32" alt="Admin">
                    </li>
                </ul>
            </nav>

      <div class="container-fluid">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <ul class="nav nav-tabs card-header-tabs" id="rankingTabs">
              <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#ranking">Ranking</a></li>
              <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#alternatif">Alternatif</a></li>
              <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#transformasi">Transformasi</a></li>
              <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#normalisasi">Normalisasi</a></li>
              <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#terbobot">Terbobot</a></li>
            </ul>
          </div>
          <div class="card-body">
            <div class="tab-content pt-3">
              <?php
              function render_table($id, $data, $columns, $highlight_id = null) {
                  $is_ranking = $id === 'ranking';
                  echo "<div class='tab-pane fade".($id==='ranking'?' show active':'')."' id='$id'>";
                  echo "<div class='table-responsive'>";
                  echo "<table class='table table-bordered table-hover table-sm".($is_ranking ? "' id='ranking-table'>" : " datatable'>");
                  echo "<thead class='thead-light'><tr>";
                  foreach ($columns as $col) echo "<th>$col</th>";
                  echo "</tr></thead><tbody>";
                  foreach ($data as $i => $row) {
                      $highlight = ($highlight_id && $row['id'] === $highlight_id) ? 'table-success font-weight-bold' : '';
                      $tr_data_id = $is_ranking ? "data-id='{$row['id']}'" : '';
                      echo "<tr class='$highlight' $tr_data_id>";
                      foreach ($columns as $col) {
                          if ($col === 'Rank') {
                              echo "<td><strong>" . ($i + 1) . "</strong></td>";
                          } elseif ($col === 'Status Gizi') {
                              $status = strtolower($row['status_gizi']);
                              $badge = strtolower(str_replace(' ', '-', $status));
                              echo "<td><span class='status-badge status-gizi-$badge'>" . htmlspecialchars($status) . "</span></td>";
                          } elseif ($col === 'Aksi') {
                              echo "<td><button class='btn btn-sm btn-info' data-toggle='modal' data-target='#detailModal' data-id='" . htmlspecialchars($row['id']) . "'><i class='fas fa-eye'></i> Detail</button></td>";
                          } else {
                              $map = [
                                'berat' => 'berat_kg',
                                'tinggi' => 'tinggi_cm'
                            ];
                            $key = strtolower(str_replace([' ', '/', '-', '.'], '_', $col));
                            $key = $map[$key] ?? $key;

                              if ($key === 'jenis_kelamin') {
                                    echo "<td>" . ($row[$key] === '1' ? 'L' : 'P') . "</td>";
                                } else {
                                    echo "<td>" . htmlspecialchars($row[$key]) . "</td>";
                                }

                          }
                      }
                      echo "</tr>";
                  }
                  echo "</tbody></table></div></div>";
              }

              render_table('ranking', $ranking, ['Rank', 'ID', 'Nama', 'Jenis Kelamin', 'Umur', 'Berat', 'Tinggi', 'Score', 'Status Gizi', 'Aksi'], $recent_id);
              render_table('alternatif', $alternatif, ['ID', 'Nama', 'z_tb', 'z_bb', 'z_bb_tb', 'z_imt']);
              render_table('transformasi', $transformasi, ['ID', 'Nama', 'TB/U', 'BB/U', 'BB/TB', 'IMT/U']);
              render_table('normalisasi', $normalisasi, ['ID', 'Nama', 'TB/U', 'BB/U', 'BB/TB', 'IMT/U']);
              render_table('terbobot', $terbobot, ['ID', 'Nama', 'TB/U', 'BB/U', 'BB/TB', 'IMT/U']);
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-baby me-2"></i> Detail Data Balita</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
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
            <span id="detail-tinggi"></span>
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
        <hr>
        <div class="row mb-2">
          <div class="col-md-6"><strong>Score SAW:</strong><br><span id="detail-score"></span></div>
          <div class="col-md-6"><strong>Ranking:</strong><br><span id="detail-ranking"></span></div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-outline-primary" onclick="window.print()">Cetak</button>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function () {
  $('.datatable').DataTable({ pageLength: 20, lengthChange: false, ordering: false, searching: false, info: false });

  <?php if (!empty($recent_id)): ?>
  $('#rankingTabs a[href="#ranking"]').tab('show');
  setTimeout(() => {
    const row = $('#ranking .table tr.table-success');
    if (row.length) {
      $('html, body').animate({ scrollTop: row.offset().top - 120 }, 800);
    }
  }, 400);
  <?php endif; ?>

  $('#detailModal').on('show.bs.modal', function (event) {
    const id = $(event.relatedTarget).data('id');
    const balita = <?php echo json_encode($data); ?>;
    const scoreMap = <?php echo json_encode(array_column($ranking, 'score', 'id')); ?>;
    const rankList = <?php echo json_encode(array_column($ranking, 'id')); ?>;

    const b = balita[id];
    if (!b) return;

    $('#detail-id').text(id);
    $('#detail-nama').text(b.nama_balita);
    $('#detail-umur').text(b.umur);
    $('#detail-berat').text(b.berat_kg);
    $('#detail-tinggi').text(b.tinggi_cm);
    const tinggi_m = b.tinggi_cm / 100;
    $('#detail-tinggi-m').text(tinggi_m.toFixed(2));
    $('#detail-tinggi-m2').text((tinggi_m * tinggi_m).toFixed(4));

    $('#detail-imt').text(b.imt);
    $('#detail-status')
      .text(b.status_gizi)
      .attr('class', 'status-badge font-weight-bold px-4 py-2 status-gizi-' + b.status_gizi.toLowerCase().replace(/\s+/g, '-'));
    $('#detail-score').text(scoreMap[id] ?? '-');
    $('#detail-ranking').text((rankList.indexOf(id) + 1) + '/' + rankList.length);
  });
});

function confirmLogout(event) {
  event.preventDefault();
  if (confirm("Apakah Anda yakin ingin logout?")) {
    window.location.href = "logout.php";
  }
}
</script>
</body>
</html>
