<?php
function preprocessDataBalita($conn) {
    $sql = "SELECT * FROM data_balita_raw ORDER BY no ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $jk = strtolower($row['jenis_kelamin']);
            $jenis_kelamin = ($jk === 'p') ? 0 : (($jk === 'l') ? 1 : null);

            $tinggi_cm = floatval($row['tinggi_cm']);
            $berat_kg = floatval($row['berat_kg']);
            $tinggi_m = $tinggi_cm / 100;
            $tinggi_m2 = $tinggi_m * $tinggi_m;
            $imt = ($tinggi_m2 > 0) ? $berat_kg / $tinggi_m2 : 0;

            // Gunakan INSERT ... ON DUPLICATE KEY UPDATE
            $stmt = $conn->prepare("
                INSERT INTO data_balita_processed 
                    (id_balita, nama_balita, jenis_kelamin, umur, berat_kg, tinggi_cm, tinggi_m, tinggi_m2, imt, status_gizi)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    nama_balita = VALUES(nama_balita),
                    jenis_kelamin = VALUES(jenis_kelamin),
                    umur = VALUES(umur),
                    berat_kg = VALUES(berat_kg),
                    tinggi_cm = VALUES(tinggi_cm),
                    tinggi_m = VALUES(tinggi_m),
                    tinggi_m2 = VALUES(tinggi_m2),
                    imt = VALUES(imt),
                    status_gizi = VALUES(status_gizi)
            ");

            $stmt->bind_param(
                "ssiiddddds",
                $row['id_balita'],
                $row['nama_balita'],
                $jenis_kelamin,
                $row['umur'],
                $berat_kg,
                $tinggi_cm,
                $tinggi_m,
                $tinggi_m2,
                $imt,
                $row['status_gizi']
            );

            $stmt->execute();
            $stmt->close();
        }
    }
}
