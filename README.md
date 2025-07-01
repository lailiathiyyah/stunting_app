# 🧠 SISTEM PEMERINGKATAN RISIKO STUNTING BALITA BERDASARKAN KLASIFIKASI STATUS GIZI DENGAN METODE NAIVE BAYES DAN SAW 

![Screenshot 2025-06-22 005950](https://github.com/user-attachments/assets/69816d27-6d42-4e9b-99eb-472820fef4ab)

Web aplikasi ini dikembangkan untuk mendeteksi risiko **stunting pada balita** berdasarkan data antropometri (berat badan, tinggi badan, umur, IMT) menggunakan **Metode Naive Bayes** untuk klasifikasi status gizi dan **Metode SAW (Simple Additive Weighting)** untuk perangkingan risiko stunting.

## 🚀 Fitur Utama

- **Login Admin**: Akses sistem hanya untuk admin yang terdaftar.
  ![Screenshot 2025-07-01 121516](https://github.com/user-attachments/assets/7163114b-a91f-47e5-8320-e54b54076499)

- **Input Data Balita**: Formulir input data lengkap (nama, jenis kelamin, umur, berat, tinggi).
  ![Screenshot 2025-06-28 162706](https://github.com/user-attachments/assets/70f989f6-1c83-41b6-85e7-8d40ab5cfbe9)

- **Prediksi Status Gizi**: Menggunakan metode **Naive Bayes** dengan output klasifikasi seperti *Gizi Buruk*, *Gizi Kurang*, *Gizi Normal*, dan *Gizi Lebih*.
  ![Screenshot 2025-06-28 163458](https://github.com/user-attachments/assets/9e0f5378-9b2b-4ba1-aace-edd27768d5cb)

- **Perhitungan SAW**: Perangkingan risiko stunting berdasarkan indikator BB/U, TB/U, BB/TB, IMT/U
- **Dashboard Statistik**: Visualisasi jumlah dan status gizi balita dalam bentuk grafik.
- **Ranking Balita**: Tabel interaktif dengan hasil perangkingan risiko stunting, lengkap dengan skor dan urutan.
  ![Screenshot 2025-06-28 162830](https://github.com/user-attachments/assets/24e14058-cb97-4aa8-9ff2-1799a045ff72)

- **Pencarian Nama**: Fitur search balita berdasarkan nama.
  ![Screenshot 2025-07-01 123036](https://github.com/user-attachments/assets/9f0d6241-d3af-4998-9ab8-bae6a2050c52)

- **Detail Modal**: Tampilan detail setiap balita dalam bentuk modal pop-up.
  ![Screenshot 2025-06-28 163605](https://github.com/user-attachments/assets/db5cabbb-a724-419d-83a2-2cef4144fbc6)

- **Hasil Evaluasi Model Klasifikasi**: Tampilan hasil evaluasi model naive bayes (akurasi, f1-score, recall, precision) dan distribusi
  ![Screenshot 2025-06-28 163048](https://github.com/user-attachments/assets/5f5aadcf-96d6-4e0f-a4d7-824231025bcb)

  
## 🛠️ Teknologi

- **Backend**: PHP (Native)
- **Frontend**: HTML5, Bootstrap (SB Admin 2), JavaScript, jQuery, DataTables
- **Database**: MySQL
- **Algoritma**: Naive Bayes, Simple Additive Weighting (SAW)


