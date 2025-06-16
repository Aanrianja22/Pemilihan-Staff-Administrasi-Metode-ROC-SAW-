# Sistem Pendukung Keputusan (SPK) Penerimaan Staff Administrasi - Metode ROC-SAW

Sistem ini merupakan aplikasi berbasis web yang dikembangkan untuk membantu proses pengambilan keputusan dalam **seleksi dan penerimaan staff administrasi**. Sistem ini menerapkan metode **ROC (Rank Order Centroid)** untuk penentuan bobot kriteria dan metode **SAW (Simple Additive Weighting)** untuk perhitungan skor alternatif.

## 📌 Definisi dan Penjelasan Sistem

Sistem pendukung keputusan ini digunakan untuk menilai dan merekomendasikan calon staff administrasi berdasarkan sejumlah kriteria yang telah ditentukan. Sistem bekerja dengan dua tahap utama:

1. **ROC (Rank Order Centroid)** digunakan untuk menentukan bobot dari masing-masing kriteria berdasarkan urutan prioritas.
2. **SAW (Simple Additive Weighting)** digunakan untuk menghitung nilai akhir dari setiap calon berdasarkan bobot kriteria dan nilai alternatif.

### Contoh Kasus (Data dalam Database)

Kasus yang diangkat adalah seleksi **calon staff administrasi** berdasarkan 6 kriteria sesuai dengan urutan prioritasnya:

1. Umur
2. Pendidikan
3. Pengalaman Kerja
4. Pengalaman Magang
5. Pengalaman Organisasi
6. Sertifikat

Masing-masing calon dinilai berdasarkan kriteria tersebut dan sistem akan menampilkan peringkatnya berdasarkan skor tertinggi.

## 🧩 Fitur Sistem

- ✅ Membuat dan manajemen kriteria
- ✅ Input dan pengurutan kriteria berdasarkan prioritas (untuk ROC)
- ✅ Input dan manajemen data calon pelamar
- ✅ Perhitungan bobot otomatis menggunakan ROC
- ✅ Perhitungan nilai akhir menggunakan metode SAW
- ✅ Hasil peringkat akhir calon berdasarkan skor total
- ✅ Simpan otomatis dalam database
- ✅ Ubah/muat proyek yang sudah tersimpan di database

## ⚙️ Tools dan Requirements

Untuk menjalankan sistem ini, pastikan tools berikut telah terinstal di perangkat Anda:

- **XAMPP** (PHP dan MySQL)
- **Web Browser** (Google Chrome, Mozilla Firefox, dll.)
- **Code Editor** (Opsional, misal: VSCode, Sublime Text)

## 📥 Langkah Instalasi

1. Unduh atau clone repositori ini:
   ```bash
   git clone https://github.com/username-anda/DSS_superfinal.git
2. Ekstrak folder DSS_superfinal.rar (jika masih dalam format RAR/ZIP).
3. Pindahkan folder hasil ekstraksi ke direktori htdocs milik XAMPP.
4. Jalankan Apache dan MySQL di XAMPP.
5. Buka browser dan akses phpMyAdmin melalui:
   ```bash
   http://localhost/phpmyadmin
6. Buat database baru (misalnya: dss_superfinal) lalu import file SQL yang terdapat di dalam folder (dss_superfinal.sql).
7. Akses sistem di browser:
   ```bash
   [http://localhost/phpmyadmin](http://localhost/DSS_superfinal/

## 🗂️ Struktur Proyek

    DSS_superfinal/
    ├── Assets/                 # Tampilan antarmuka 
    │   ├── css
    │   │   └── style.css   
    │   └── js
    │       └── script.js
    ├── database/              
    │   └── rocsawdss.sql      # File SQL untuk database
    ├── config.php             # Konfigurasi koneksi ke database
    ├── functions.php          # Melakukan perhitungan 
    ├── index.php              # Halaman utama
    └── README.md              # Dokumentasi sistem

## 🚀 Cara Penggunaan Sistem

1. Buka sistem melalui browser (pastikan sudah mengaktifkan Apache dan MySQL di XAMPP).
2. Masuk ke halaman "Kriteria".
3. Buat kriteria sesuai dengan sifatnya (benefit/cost) dan tentukan urutan prioritas kriteria (untuk proses ROC).
4. Beralih ke halaman "Alternatif & Nilai".
5. Tambahkan data alternatif (data calon pelamar).
6. Beralih ke halaman "Hasil".
7. Klik tombol "Hitung & Tampilkan Hasil Perangkingan".
8. Sistem akan menampilkan hasil bobot kriteria, skor SAW masing-masing pelamar, serta peringkat akhir.
9. Jika ingin menggunakan data perhitungan dari studi kasus penerimaan staff administrasi yang sudah ada di database maka:
    - Klik tombol "Ubah/Muat Proyek" dibagian atas halaman.
    - Pada daftar proyek tersimpan pilih proyek berjudul "Penerimaan Staff Admisistrasi".
    - Klik tombol "Muat"
    - Masuk ke halaman "Hasil" (karena kriteria dan data pada tiap alternatif sudah otomatis termuat).
    - Klik tombol "Hitung & Tampilkan Hasil Perangkingan".
    - Sistem akan menampilkan hasil bobot kriteria, skor SAW masing-masing pelamar, serta peringkat akhir.



