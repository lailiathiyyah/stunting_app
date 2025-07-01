# 🧠 SISTEM PEMERINGKATAN RISIKO STUNTING BALITA BERDASARKAN KLASIFIKASI STATUS GIZI DENGAN METODE NAIVE BAYES DAN SAW 

![Screenshot 2025-06-22 005950](https://github.com/user-attachments/assets/69816d27-6d42-4e9b-99eb-472820fef4ab)

Web aplikasi ini dikembangkan untuk mendeteksi risiko **stunting pada balita** berdasarkan data antropometri (berat badan, tinggi badan, umur, IMT) menggunakan **Metode Naive Bayes** untuk klasifikasi status gizi dan **Metode SAW (Simple Additive Weighting)** untuk perangkingan risiko stunting.

## 🚀 Fitur Utama

- **Login Admin**: Akses sistem hanya untuk admin yang terdaftar.
- **Input Data Balita**: Formulir input data lengkap (nama, jenis kelamin, umur, berat, tinggi).
- **Prediksi Status Gizi**: Menggunakan metode **Naive Bayes** dengan output klasifikasi seperti *Gizi Buruk*, *Gizi Kurang*, *Gizi Normal*, dan *Gizi Lebih*.
- **Perhitungan SAW**: Perangkingan risiko stunting berdasarkan indikator BB/U, TB/U, BB/TB, IMT/U
- **Dashboard Statistik**: Visualisasi jumlah dan status gizi balita dalam bentuk grafik.
- **Ranking Balita**: Tabel interaktif dengan hasil perangkingan risiko stunting, lengkap dengan skor dan urutan.
- **Pencarian Nama**: Fitur search balita berdasarkan nama.
- **Detail Modal**: Tampilan detail setiap balita dalam bentuk modal pop-up.
- **Hasil Evaluasi Model Klasifikasi**: Tampilan hasil evaluasi model naive bayes (akurasi, f1-score, recall, precision) dan distribusi

## 🛠️ Teknologi

- **Backend**: PHP (Native)
- **Frontend**: HTML5, Bootstrap (SB Admin 2), JavaScript, jQuery, DataTables
- **Database**: MySQL
- **Algoritma**: Naive Bayes, Simple Additive Weighting (SAW)


