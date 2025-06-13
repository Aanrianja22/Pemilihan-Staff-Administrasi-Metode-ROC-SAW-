<?php
session_start(); // Mulai session untuk pesan alert

include 'config.php'; // Koneksi database
include 'functions.php'; // Fungsi CRUD dan perhitungan

$alert_message = '';
$alert_type = '';

// Ambil pesan alert dari session jika ada
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    $alert_type = $_SESSION['alert_type'];
    unset($_SESSION['alert_message']); // Hapus setelah dibaca
    unset($_SESSION['alert_type']);
}

// --- Manajemen Proyek Aktif ---
// Nama proyek aktif disimpan di session. Jika tidak ada, default ke "Proyek Default"
if (!isset($_SESSION['active_project_name']) || $_SESSION['active_project_name'] === '') {
    $_SESSION['active_project_name'] = "Proyek Default"; // Nama proyek default jika tidak ada yang dimuat
}
$active_project_name = $_SESSION['active_project_name'];


// Tentukan tab aktif berdasarkan parameter GET atau default ke 'criteria'
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'criteria';


// --- PENANGANAN AKSI POST (Form Submissions) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    // Arahkan ke tab yang sesuai setelah POST
    $redirect_tab = isset($_POST['tab']) ? $_POST['tab'] : 'criteria';

    if ($_POST['action'] == 'add_criteria' || $_POST['action'] == 'update_criteria') {
        $criteria_name = trim($_POST['criteriaName']);
        $criteria_type = $_POST['criteriaType'];
        $criteria_unit = trim($_POST['criteriaUnit']);
        $criteria_input_type = $_POST['criteriaInputType'];
        
        // Memastikan range_names dan range_values adalah array dan memiliki jumlah yang sama
        $posted_range_names = isset($_POST['range_names']) ? (array)$_POST['range_names'] : [];
        $posted_range_values = isset($_POST['range_values']) ? (array)$_POST['range_values'] : [];
        
        $temp_ranges_for_save = [];
        if (count($posted_range_names) === count($posted_range_values)) {
            for ($i = 0; $i < count($posted_range_names); $i++) {
                // Pastikan format array asosiatif untuk range_data agar sesuai dengan fungsi addCriteria/updateCriteria
                $temp_ranges_for_save[] = ['range_name' => $posted_range_names[$i], 'range_value' => $posted_range_values[$i]];
            }
        }

        // Validasi input di sisi server
        if (empty($criteria_name)) {
            $_SESSION['alert_message'] = "Nama kriteria harus diisi!";
            $_SESSION['alert_type'] = "danger";
        } else if ($criteria_input_type == 'ranges' && empty($temp_ranges_for_save)) {
            $_SESSION['alert_message'] = 'Untuk tipe input "Rentang Nilai", minimal satu rentang harus didefinisikan.';
            $_SESSION['alert_type'] = "danger";
        } else {
            if ($_POST['action'] == 'add_criteria') {
                // Cek apakah nama kriteria sudah ada untuk proyek aktif
                $existing_criteria_names = array_column(getAllCriteria($conn, $active_project_name), 'name');
                if (in_array($criteria_name, $existing_criteria_names)) {
                    $_SESSION['alert_message'] = "Nama kriteria '{$criteria_name}' sudah ada dalam proyek ini!";
                    $_SESSION['alert_type'] = "danger";
                } else {
                    if (addCriteria($conn, $active_project_name, $criteria_name, $criteria_type, $criteria_unit, $criteria_input_type, $temp_ranges_for_save)) {
                        $_SESSION['alert_message'] = "Kriteria berhasil ditambahkan!";
                        $_SESSION['alert_type'] = "success";
                    } else {
                        $_SESSION['alert_message'] = "Gagal menambahkan kriteria.";
                        $_SESSION['alert_type'] = "danger";
                    }
                }
            } else if ($_POST['action'] == 'update_criteria' && isset($_POST['criteria_id'])) {
                $criteria_id = (int)$_POST['criteria_id'];
                // Anda mungkin ingin menambahkan logika untuk memastikan nama kriteria yang diupdate
                // tidak sama dengan kriteria lain dalam proyek yang sama (kecuali dirinya sendiri)
                if (updateCriteria($conn, $criteria_id, $criteria_name, $criteria_type, $criteria_unit, $criteria_input_type, $temp_ranges_for_save)) {
                    $_SESSION['alert_message'] = "Kriteria berhasil diupdate!";
                    $_SESSION['alert_type'] = "success";
                } else {
                    $_SESSION['alert_message'] = "Gagal mengupdate kriteria.";
                    $_SESSION['alert_type'] = "danger";
                }
            }
        }
        $redirect_tab = 'criteria';
    } elseif ($_POST['action'] == 'delete_criteria' && isset($_POST['criteria_id'])) {
        $criteria_id = (int)$_POST['criteria_id'];
        if (deleteCriteria($conn, $criteria_id)) {
            $_SESSION['alert_message'] = "Kriteria berhasil dihapus!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert_message'] = "Gagal menghapus kriteria.";
            $_SESSION['alert_type'] = "danger";
        }
        $redirect_tab = 'criteria';
    } elseif ($_POST['action'] == 'move_criteria_up' && isset($_POST['criteria_id'])) {
        $criteria_id = (int)$_POST['criteria_id'];
        if (moveCriteria($conn, $criteria_id, 'up', $active_project_name)) {
            $_SESSION['alert_message'] = "Urutan kriteria berhasil diubah!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert_message'] = "Gagal mengubah urutan kriteria (mungkin sudah di posisi teratas).";
            $_SESSION['alert_type'] = "warning";
        }
        $redirect_tab = 'criteria';
    } elseif ($_POST['action'] == 'move_criteria_down' && isset($_POST['criteria_id'])) {
        $criteria_id = (int)$_POST['criteria_id'];
        if (moveCriteria($conn, $criteria_id, 'down', $active_project_name)) {
            $_SESSION['alert_message'] = "Urutan kriteria berhasil diubah!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert_message'] = "Gagal mengubah urutan kriteria (mungkin sudah di posisi terbawah).";
            $_SESSION['alert_type'] = "warning";
        }
        $redirect_tab = 'criteria';
    } elseif ($_POST['action'] == 'add_alternative' || $_POST['action'] == 'update_alternative') {
        $alternative_name = trim($_POST['alternativeName']);
        $alternative_values = [];
        $all_criteria_for_validation = getAllCriteria($conn, $active_project_name);

        $allValuesFilledOrValid = true;
        if (empty($all_criteria_for_validation)) {
            // Jika tidak ada kriteria sama sekali, nilai alternatif tidak perlu diisi
            $allValuesFilledOrValid = true;
        } else {
            foreach ($all_criteria_for_validation as $crit) {
                $input_name = 'alt_crit_' . $crit['id'];
                if (!isset($_POST[$input_name]) || $_POST[$input_name] === '') {
                    $allValuesFilledOrValid = false;
                    break;
                }
                $value = (float)$_POST[$input_name];
                $alternative_values[$crit['id']] = $value;
            }
        }

        if (empty($alternative_name)) {
            $_SESSION['alert_message'] = "Nama alternatif harus diisi!";
            $_SESSION['alert_type'] = "danger";
        } else if (count($all_criteria_for_validation) > 0 && !$allValuesFilledOrValid) {
            $_SESSION['alert_message'] = 'Semua nilai kriteria untuk alternatif harus diisi dengan angka yang valid!';
            $_SESSION['alert_type'] = "danger";
        } else {
            if ($_POST['action'] == 'add_alternative') {
                 // Cek apakah nama alternatif sudah ada untuk proyek aktif
                $existing_alternative_names = array_column(getAllAlternatives($conn, $active_project_name), 'name');
                if (in_array($alternative_name, $existing_alternative_names)) {
                    $_SESSION['alert_message'] = "Nama alternatif '{$alternative_name}' sudah ada dalam proyek ini!";
                    $_SESSION['alert_type'] = "danger";
                } else {
                    if (addAlternative($conn, $active_project_name, $alternative_name, $alternative_values)) {
                        $_SESSION['alert_message'] = "Alternatif berhasil ditambahkan!";
                        $_SESSION['alert_type'] = "success";
                    } else {
                        $_SESSION['alert_message'] = "Gagal menambahkan alternatif.";
                        $_SESSION['alert_type'] = "danger";
                    }
                }
            } else if ($_POST['action'] == 'update_alternative' && isset($_POST['alternative_id'])) {
                $alternative_id = (int)$_POST['alternative_id'];
                if (updateAlternative($conn, $alternative_id, $alternative_name, $alternative_values)) {
                    $_SESSION['alert_message'] = "Alternatif berhasil diupdate!";
                    $_SESSION['alert_type'] = "success";
                } else {
                    $_SESSION['alert_message'] = "Gagal mengupdate alternatif.";
                    $_SESSION['alert_type'] = "danger";
                }
            }
        }
        $redirect_tab = 'alternatives';
    } elseif ($_POST['action'] == 'delete_alternative' && isset($_POST['alternative_id'])) {
        $alternative_id = (int)$_POST['alternative_id'];
        if (deleteAlternative($conn, $alternative_id)) {
            $_SESSION['alert_message'] = "Alternatif berhasil dihapus!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert_message'] = "Gagal menghapus alternatif.";
            $_SESSION['alert_type'] = "danger";
        }
        $redirect_tab = 'alternatives';
    } elseif ($_POST['action'] == 'calculate_results') {
        $current_criteria_count = count(getAllCriteria($conn, $active_project_name));
        $current_alternatives_count = count(getAllAlternatives($conn, $active_project_name));
        if ($current_criteria_count === 0 || $current_alternatives_count === 0) {
             $_SESSION['alert_message'] = "Tambahkan kriteria dan alternatif (beserta nilainya) terlebih dahulu sebelum menghitung!";
             $_SESSION['alert_type'] = "danger";
        } else {
            // Perhitungan dilakukan di bagian render, hanya set pesan sukses jika data cukup
             $_SESSION['alert_message'] = "Perhitungan berhasil! Hasil ditampilkan di bawah.";
             $_SESSION['alert_type'] = "success";
        }
        $redirect_tab = 'results';
    } elseif ($_POST['action'] == 'save_current_project_as') {
        $new_project_name = trim($_POST['new_project_name']);
        if (empty($new_project_name)) {
            $_SESSION['alert_message'] = "Nama proyek baru harus diisi!";
            $_SESSION['alert_type'] = "danger";
        } else {
            // Cek apakah nama proyek sudah ada
            $existing_project_names = getAllUniqueProjectNames($conn);
            if (in_array($new_project_name, $existing_project_names)) {
                $_SESSION['alert_message'] = "Nama proyek '{$new_project_name}' sudah ada. Silakan gunakan nama lain.";
                $_SESSION['alert_type'] = "danger";
            } else {
                // Ambil data dari proyek aktif saat ini
                $criteria_to_copy = getAllCriteria($conn, $active_project_name);
                $alternatives_to_copy_raw = getAllAlternatives($conn, $active_project_name);
                $alternatives_to_copy = [];
                foreach ($alternatives_to_copy_raw as $alt) {
                    $alternatives_to_copy[] = getAlternativeById($conn, $alt['id']);
                }

                // Copy data ke nama proyek baru
                $success = true;
                foreach ($criteria_to_copy as $crit) {
                    // Pastikan getCriteriaRanges($conn, $crit['id']) mengembalikan array dengan format yang diharapkan addCriteria
                    if (!addCriteria($conn, $new_project_name, $crit['name'], $crit['type'], $crit['unit'], $crit['input_type'], getCriteriaRanges($conn, $crit['id']))) {
                        $success = false;
                        break;
                    }
                }
                if ($success) {
                    // Setelah kriteria disalin, kita perlu mengambil ID kriteria baru untuk alternatif
                    $old_to_new_crit_id_map = [];
                    $new_criterias_in_new_project = getAllCriteria($conn, $new_project_name);
                    foreach ($criteria_to_copy as $old_crit) {
                        foreach ($new_criterias_in_new_project as $new_crit) {
                            // Mencocokkan berdasarkan nama dan tipe kriteria (asumsi unik dalam proyek)
                            if ($old_crit['name'] === $new_crit['name'] && $old_crit['type'] === $new_crit['type']) {
                                $old_to_new_crit_id_map[$old_crit['id']] = $new_crit['id'];
                                break;
                            }
                        }
                    }

                    foreach ($alternatives_to_copy as $alt) {
                        $new_alt_values = [];
                        foreach ($alt['values'] as $old_crit_id => $value) {
                            if (isset($old_to_new_crit_id_map[$old_crit_id])) {
                                $new_alt_values[$old_to_new_crit_id_map[$old_crit_id]] = $value;
                            }
                        }
                        if (!addAlternative($conn, $new_project_name, $alt['name'], $new_alt_values)) {
                            $success = false;
                            break;
                        }
                    }
                }

                if ($success) {
                    $_SESSION['alert_message'] = "Proyek berhasil disimpan sebagai '{$new_project_name}'!";
                    $_SESSION['alert_type'] = "success";
                    $_SESSION['active_project_name'] = $new_project_name; // Set proyek baru sebagai aktif
                } else {
                    $_SESSION['alert_message'] = "Gagal menyimpan proyek sebagai '{$new_project_name}'.";
                    $_SESSION['alert_type'] = "danger";
                }
            }
        }
        $redirect_tab = $_POST['current_tab_after_save_as']; // Kembali ke tab sebelumnya
    } elseif ($_POST['action'] == 'load_project') {
        $project_name_to_load = trim($_POST['project_name_to_load']);
        if (empty($project_name_to_load)) {
             $_SESSION['alert_message'] = "Nama proyek harus dipilih untuk dimuat.";
             $_SESSION['alert_type'] = "danger";
        } else {
            $_SESSION['active_project_name'] = $project_name_to_load;
            $_SESSION['alert_message'] = "Proyek '{$project_name_to_load}' berhasil dimuat.";
            $_SESSION['alert_type'] = "success";
        }
        $redirect_tab = 'criteria';
    } elseif ($_POST['action'] == 'delete_project_by_name') {
        $project_name_to_delete = trim($_POST['project_name_to_delete']);
        if (deleteProjectByName($conn, $project_name_to_delete)) {
            $_SESSION['alert_message'] = "Proyek '{$project_name_to_delete}' berhasil dihapus!";
            $_SESSION['alert_type'] = "success";
            if ($active_project_name === $project_name_to_delete) {
                $_SESSION['active_project_name'] = "Proyek Default"; // Kembali ke proyek default
            }
        } else {
            $_SESSION['alert_message'] = "Gagal menghapus proyek '{$project_name_to_delete}'.";
            $_SESSION['alert_type'] = "danger";
        }
        $redirect_tab = 'criteria'; // Kembali ke kriteria setelah hapus
    } elseif ($_POST['action'] == 'set_active_project_name') {
        $new_active_name = trim($_POST['new_active_project_name']);
        if (empty($new_active_name)) {
            $_SESSION['alert_message'] = "Nama proyek aktif tidak boleh kosong!";
            $_SESSION['alert_type'] = "danger";
        } else {
            $_SESSION['active_project_name'] = $new_active_name;
            $_SESSION['alert_message'] = "Nama proyek aktif berhasil diatur ke '{$new_active_name}'.";
            $_SESSION['alert_type'] = "info";
        }
        $redirect_tab = 'criteria';
    }

    header("Location: index.php?tab=" . $redirect_tab);
    exit();
}

// --- PENANGANAN AKSI GET (misalnya untuk Edit) ---
$edit_criteria_id = null; // Untuk form edit kriteria
$criteria_name = '';
$criteria_type = 'benefit';
$criteria_unit = '';
$criteria_input_type = 'numeric';
$temp_ranges_for_js = []; // Untuk JavaScript agar bisa menampilkan rentang di form edit

$edit_alternative_id = null; // Untuk form edit alternatif
$alternative_name = '';
$alternative_values_to_edit = []; // Untuk mengisi input nilai kriteria di form alternatif

if (isset($_GET['edit_criteria_id'])) {
    $edit_criteria_id = (int)$_GET['edit_criteria_id'];
    $criteria_to_edit = getCriteriaById($conn, $edit_criteria_id);
    if ($criteria_to_edit) {
        $criteria_name = $criteria_to_edit['name'];
        $criteria_type = $criteria_to_edit['type'];
        $criteria_unit = $criteria_to_edit['unit'];
        $criteria_input_type = $criteria_to_edit['input_type'];
        if ($criteria_to_edit['input_type'] === 'ranges') {
            $temp_ranges_for_js = getCriteriaRanges($conn, $edit_criteria_id);
        }
    }
    $active_tab = 'criteria';
} elseif (isset($_GET['edit_alternative_id'])) {
    $edit_alternative_id = (int)$_GET['edit_alternative_id'];
    $alternative_to_edit = getAlternativeById($conn, $edit_alternative_id);
    if ($alternative_to_edit) {
        $alternative_name = $alternative_to_edit['name'];
        $alternative_values_to_edit = $alternative_to_edit['values'];
    }
    $active_tab = 'alternatives';
}


// --- AMBIL DATA UNTUK DITAMPILKAN DI HTML ---
// Semua pengambilan data sekarang disaring berdasarkan proyek aktif
$all_criteria = getAllCriteria($conn, $active_project_name);
$all_alternatives = getAllAlternatives($conn, $active_project_name);
$all_unique_project_names = getAllUniqueProjectNames($conn);

$current_results = [];
// Jika tab aktif adalah 'results', lakukan perhitungan
if ($active_tab === 'results') {
    $current_results = calculateROC_SAW($conn, $active_project_name);
    // Jika hasil kosong, set alert untuk menjelaskan mengapa
    if (empty($current_results) && (count($all_criteria) > 0 || count($all_alternatives) > 0) ) {
        // Ini akan menangani kasus di mana ada kriteria/alternatif, tapi nilainya belum lengkap
        if (!isset($_SESSION['alert_message'])) { // Jangan menimpa alert yang sudah ada dari POST
            $_SESSION['alert_message'] = "Tidak ada hasil karena data kriteria atau alternatif belum lengkap/valid. Pastikan semua nilai kriteria untuk setiap alternatif sudah terisi.";
            $_SESSION['alert_type'] = "danger";
        }
    }
}

// Untuk menampilkan nilai alternatif di tabel daftar alternatif
$all_alternative_values_detailed = [];
foreach ($all_alternatives as $alt) {
    $alt_details = getAlternativeById($conn, $alt['id']);
    if ($alt_details) {
        $all_alternative_values_detailed[$alt['id']] = $alt_details['values'];
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistem Pendukung Keputusan ROC-SAW</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sistem Pendukung Keputusan</h1>
            <p>
                Metode ROC (Rank Order Centroid) & SAW (Simple Additive Weighting)
            </p>
            <div style="margin-top: 15px; font-size: 1.1em;">
                <span style="font-weight: bold;">Proyek Aktif:</span>
                <span style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 5px;">
                    <?php echo htmlspecialchars($active_project_name); ?>
                </span>
                <button type="button" class="btn btn-sm btn-info" onclick="showChangeProjectModal()">Ubah / Muat Proyek</button>
            </div>
        </div>

        <div class="nav-tabs">
            <button class="nav-tab <?php echo ($active_tab === 'criteria' ? 'active' : ''); ?>" onclick="showTab('criteria', this)">Kriteria</button>
            <button class="nav-tab <?php echo ($active_tab === 'alternatives' ? 'active' : ''); ?>" onclick="showTab('alternatives', this)">Alternatif & Nilai</button>
            <button class="nav-tab <?php echo ($active_tab === 'results' ? 'active' : ''); ?>" onclick="showTab('results', this)">Hasil</button>
        </div>

        <div class="tab-content">
            <div id="criteria" class="tab-pane <?php echo ($active_tab === 'criteria' ? 'active' : ''); ?>">
                <?php if ($active_tab === 'criteria' && $alert_message): ?>
                    <div class="alert alert-<?php echo $alert_type; ?>"><?php echo $alert_message; ?></div>
                <?php endif; ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Manajemen Kriteria</h2>
                    </div>
                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="<?php echo ($edit_criteria_id ? 'update_criteria' : 'add_criteria'); ?>">
                        <input type="hidden" name="tab" value="criteria">
                        <?php if ($edit_criteria_id): ?>
                            <input type="hidden" name="criteria_id" value="<?php echo $edit_criteria_id; ?>">
                        <?php endif; ?>
                        <div class="grid-2">
                            <div>
                                <div class="form-group">
                                    <label class="form-label">Nama Kriteria</label>
                                    <input type="text" name="criteriaName" id="criteriaName" class="form-input" placeholder="Contoh: Pengalaman Kerja" value="<?php echo htmlspecialchars($criteria_name); ?>" required />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tipe Kriteria</label>
                                    <select name="criteriaType" id="criteriaType" class="form-select">
                                        <option value="benefit" <?php echo ($criteria_type == 'benefit' ? 'selected' : ''); ?>>Benefit (Semakin tinggi semakin baik)</option>
                                        <option value="cost" <?php echo ($criteria_type == 'cost' ? 'selected' : ''); ?>>Cost (Semakin rendah semakin baik)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Unit/Satuan</label>
                                    <input type="text" name="criteriaUnit" id="criteriaUnit" class="form-input" placeholder="Contoh: Tahun, Poin, dll" value="<?php echo htmlspecialchars($criteria_unit); ?>" />
                                </div>
                            </div>
                            <div>
                                <div class="form-group">
                                    <label class="form-label">Tipe Input Nilai</label>
                                    <select name="criteriaInputType" id="criteriaInputType" class="form-select" onchange="toggleValueRangesInputPHP(this.value)">
                                        <option value="numeric" <?php echo ($criteria_input_type == 'numeric' ? 'selected' : ''); ?>>Angka Langsung</option>
                                        <option value="ranges" <?php echo ($criteria_input_type == 'ranges' ? 'selected' : ''); ?>>Rentang Nilai</option>
                                    </select>
                                </div>
                                <div id="valueRangesSection" class="form-group <?php echo ($criteria_input_type == 'numeric' ? 'hidden' : ''); ?>">
                                    <label class="form-label">Definisi Rentang Nilai</label>
                                    <div style="display: flex; gap: 10px; margin-bottom: 10px">
                                        <input type="text" id="rangeName" class="form-input" placeholder="Nama Rentang (e.g., SD)" />
                                        <input type="number" id="rangeValue" class="form-input" placeholder="Nilai (e.g., 5)" step="0.01" />
                                        <button type="button" class="btn btn-sm btn-success" onclick="addCurrentRangePHP()" style="height: 45px; white-space: nowrap">Tambah Rentang</button>
                                    </div>
                                    <div id="currentRangesList" style="margin-top: 10px; max-height: 150px; overflow-y: auto;">
                                        </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <?php echo ($edit_criteria_id ? 'Update Kriteria' : 'Tambah Kriteria'); ?>
                        </button>
                        <?php if ($edit_criteria_id): ?>
                            <a href="index.php?tab=criteria" class="btn btn-warning">Batal Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Daftar Kriteria & Urutan Prioritas</h2>
                    </div>
                    <p>Gunakan tombol panah untuk mengatur urutan prioritas (paling penting di atas).</p>
                    <div id="criteriaList">
                        <?php if (empty($all_criteria)): ?>
                            <p>Belum ada kriteria untuk proyek "<?php echo htmlspecialchars($active_project_name); ?>". Tambahkan kriteria terlebih dahulu.</p>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Urutan</th>
                                            <th>Kriteria</th>
                                            <th>Tipe</th>
                                            <th>Tipe Input</th>
                                            <th>Bobot ROC</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        calculateROCWeights($all_criteria);
                                        
                                        foreach ($all_criteria as $index => $criteria):
                                            $inputTypeDisplay = $criteria['input_type'] === 'ranges' ? 'Rentang Nilai' : 'Angka Langsung';
                                            if ($criteria['input_type'] === 'ranges') {
                                                $ranges_for_display = getCriteriaRanges($conn, $criteria['id']);
                                                if (!empty($ranges_for_display)) {
                                                    $range_names_only = array_column($ranges_for_display, 'range_name');
                                                    $inputTypeDisplay .= ' <small>(' . htmlspecialchars(implode(', ', $range_names_only)) . ')</small>';
                                                } else {
                                                    $inputTypeDisplay .= ' <small>(Belum ada rentang)</small>';
                                                }
                                            }
                                        ?>
                                            <tr>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                        <span><strong><?php echo $index + 1; ?></strong></span>
                                                        <div>
                                                            <form action="index.php" method="POST" style="display:inline-block;">
                                                                <input type="hidden" name="action" value="move_criteria_up">
                                                                <input type="hidden" name="criteria_id" value="<?php echo $criteria['id']; ?>">
                                                                <input type="hidden" name="tab" value="criteria">
                                                                <button type="submit" class="btn btn-sm btn-primary" <?php echo ($index === 0 ? 'disabled' : ''); ?> style="padding: 4px 8px; font-size: 12px; margin-right: 2px;">↑</button>
                                                            </form>
                                                            <form action="index.php" method="POST" style="display:inline-block;">
                                                                <input type="hidden" name="action" value="move_criteria_down">
                                                                <input type="hidden" name="criteria_id" value="<?php echo $criteria['id']; ?>">
                                                                <input type="hidden" name="tab" value="criteria">
                                                                <button type="submit" class="btn btn-sm btn-primary" <?php echo ($index === count($all_criteria) - 1 ? 'disabled' : ''); ?> style="padding: 4px 8px; font-size: 12px;">↓</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($criteria['name']); ?></strong>
                                                        <?php echo ($criteria['unit'] ? '<br><small>(' . htmlspecialchars($criteria['unit']) . ')</small>' : ''); ?>
                                                    </td>
                                                    <td><?php echo ($criteria['type'] === 'benefit' ? 'Benefit' : 'Cost'); ?></td>
                                                    <td><?php echo $inputTypeDisplay; ?></td>
                                                    <td><?php echo number_format($criteria['weight'], 4); ?></td>
                                                    <td>
                                                        <a href="index.php?tab=criteria&edit_criteria_id=<?php echo $criteria['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                        <form action="index.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin ingin menghapus kriteria ini? Ini juga akan menghapus nilai kriteria di alternatif.');">
                                                            <input type="hidden" name="action" value="delete_criteria">
                                                            <input type="hidden" name="criteria_id" value="<?php echo $criteria['id']; ?>">
                                                            <input type="hidden" name="tab" value="criteria">
                                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div id="alternatives" class="tab-pane <?php echo ($active_tab === 'alternatives' ? 'active' : ''); ?>">
                    <?php if ($active_tab === 'alternatives' && $alert_message): ?>
                        <div class="alert alert-<?php echo $alert_type; ?>"><?php echo $alert_message; ?></div>
                    <?php endif; ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Manajemen Alternatif & Input Nilai Kriteria</h2>
                        </div>
                        <form action="index.php" method="POST">
                            <input type="hidden" name="action" value="<?php echo ($edit_alternative_id ? 'update_alternative' : 'add_alternative'); ?>">
                            <input type="hidden" name="tab" value="alternatives">
                            <?php if ($edit_alternative_id): ?>
                                <input type="hidden" name="alternative_id" value="<?php echo $edit_alternative_id; ?>">
                            <?php endif; ?>
                            <div class="form-group">
                                <label class="form-label">Nama Alternatif</label>
                                <input type="text" name="alternativeName" id="alternativeName" class="form-input" placeholder="Contoh: Kandidat A" value="<?php echo htmlspecialchars($alternative_name); ?>" required />
                            </div>

                            <div id="alternativeCriteriaValuesSection" class="form-group" style="margin-top: 20px">
                                <h3 class="form-label" style="font-size: 1.2em; color: #2c3e50; margin-bottom: 15px">Nilai Kriteria untuk Alternatif</h3>
                                <div id="alternativeCriteriaInputsContainer" class="grid-criteria-inputs">
                                    <?php if (empty($all_criteria)): ?>
                                        <p><small>Belum ada kriteria yang didefinisikan untuk proyek "<?php echo htmlspecialchars($active_project_name); ?>". Silakan tambahkan kriteria terlebih dahulu di tab Kriteria.</small></p>
                                    <?php else: ?>
                                        <?php foreach ($all_criteria as $crit): ?>
                                            <div class="form-group">
                                                <label class="form-label"><?php echo htmlspecialchars($crit['name']); ?> <?php echo ($crit['unit'] ? '(' . htmlspecialchars($crit['unit']) . ')' : ''); ?></label>
                                                <?php
                                                    $current_alt_value = $alternative_values_to_edit[$crit['id']] ?? '';
                                                    // Jika kriteria ini adalah tipe 'ranges', tampilkan dropdown
                                                    if ($crit['input_type'] === 'ranges'):
                                                        $crit_ranges = getCriteriaRanges($conn, $crit['id']);
                                                        // Jika tidak ada rentang yang didefinisikan, tampilkan input angka dengan peringatan
                                                        if (empty($crit_ranges)):
                                                ?>
                                                            <input type="number" name="alt_crit_<?php echo $crit['id']; ?>" class="form-input" value="<?php echo htmlspecialchars($current_alt_value); ?>" step="0.01" required disabled />
                                                            <small style="color:red;">(Belum ada rentang nilai untuk kriteria ini)</small>
                                                <?php
                                                        else:
                                                ?>
                                                            <select name="alt_crit_<?php echo $crit['id']; ?>" class="form-select" required>
                                                                <option value="">Pilih...</option>
                                                                <?php foreach ($crit_ranges as $range): ?>
                                                                    <option value="<?php echo htmlspecialchars($range['range_value']); ?>" <?php echo ( (float)$current_alt_value === (float)$range['range_value'] ? 'selected' : ''); ?>>
                                                                        <?php echo htmlspecialchars($range['range_name']); ?> (<?php echo htmlspecialchars($range['range_value']); ?>)
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                <?php
                                                        endif;
                                                    else: // Jika kriteria adalah tipe 'numeric'
                                                ?>
                                                    <input type="number" name="alt_crit_<?php echo $crit['id']; ?>" class="form-input" value="<?php echo htmlspecialchars($current_alt_value); ?>" step="0.01" required />
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="margin-top: 10px">
                                <?php echo ($edit_alternative_id ? 'Update Alternatif' : 'Tambah Alternatif'); ?>
                            </button>
                            <?php if ($edit_alternative_id): ?>
                                <a href="index.php?tab=alternatives" class="btn btn-warning">Batal Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Daftar Alternatif</h2>
                        </div>
                        <div id="alternativesList">
                            <?php if (empty($all_alternatives)): ?>
                                <p>Belum ada alternatif untuk proyek "<?php echo htmlspecialchars($active_project_name); ?>". Tambahkan alternatif terlebih dahulu.</p>
                            <?php else: ?>
                                <div class="table-container" style="overflow-x: auto;">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Alternatif</th>
                                                <?php foreach ($all_criteria as $crit): ?>
                                                    <th><?php echo htmlspecialchars($crit['name']); ?></th>
                                                <?php endforeach; ?>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($all_alternatives as $index => $alt): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($alt['name']); ?></strong></td>
                                                    <?php foreach ($all_criteria as $crit): ?>
                                                        <td>
                                                            <?php
                                                            $val = $all_alternative_values_detailed[$alt['id']][$crit['id']] ?? '-';
                                                            if ($crit['input_type'] === 'ranges') {
                                                                $ranges_for_display = getCriteriaRanges($conn, $crit['id']);
                                                                $found_range = array_filter($ranges_for_display, function($r) use ($val) {
                                                                    return (float)$r['range_value'] === (float)$val;
                                                                });
                                                                if (!empty($found_range)) {
                                                                    echo htmlspecialchars(array_values($found_range)[0]['range_name']) . ' (' . htmlspecialchars($val) . ')';
                                                                } else {
                                                                    echo htmlspecialchars($val);
                                                                }
                                                            } else {
                                                                echo htmlspecialchars($val);
                                                            }
                                                            ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                    <td>
                                                        <a href="index.php?tab=alternatives&edit_alternative_id=<?php echo $alt['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                        <form action="index.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin ingin menghapus alternatif ini? Ini akan menghapus nilai kriteria terkait.');">
                                                            <input type="hidden" name="action" value="delete_alternative">
                                                            <input type="hidden" name="alternative_id" value="<?php echo $alt['id']; ?>">
                                                            <input type="hidden" name="tab" value="alternatives">
                                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div id="results" class="tab-pane <?php echo ($active_tab === 'results' ? 'active' : ''); ?>">
                    <?php if ($active_tab === 'results' && $alert_message): ?>
                        <div class="alert alert-<?php echo $alert_type; ?>"><?php echo $alert_message; ?></div>
                    <?php endif; ?>
                    <div class="card">
                        <form action="index.php" method="POST">
                            <input type="hidden" name="action" value="calculate_results">
                            <input type="hidden" name="tab" value="results">
                            <button type="submit" class="btn btn-success" style="width: 100%; margin-bottom: 20px">
                                Hitung & Tampilkan Hasil Perangkingan
                            </button>
                        </form>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Hasil Perangkingan</h2>
                        </div>
                        <div id="rankingResults">
                            <?php if (empty($current_results)): ?>
                                <p>Belum ada hasil perhitungan. Klik tombol "Hitung & Tampilkan Hasil Perangkingan" di atas.</p>
                            <?php else: ?>
                                <div class="ranking-result">
                                    <h2>Hasil Perangkingan</h2>
                                    <?php foreach ($current_results as $index => $result): ?>
                                        <div class="ranking-item">
                                            <div class="ranking-position"><?php echo $index + 1; ?></div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($result['alternative']['name']); ?></strong>
                                            </div>
                                            <div>
                                                <strong>Skor: <?php echo number_format($result['score'], 4); ?></strong>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Detail Perhitungan</h2>
                        </div>
                        <div id="calculationDetails">
                            <?php if (!empty($current_results)): ?>
                                <div class="table-container" style="overflow-x: auto;">
                                    <h4>Matrix Keputusan (Nilai Asli Alternatif)</h4>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Alternatif</th>
                                                <?php foreach ($all_criteria as $criteria): ?>
                                                    <th><?php echo htmlspecialchars($criteria['name']); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($all_alternatives as $alt): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($alt['name']); ?></strong></td>
                                                    <?php foreach ($all_criteria as $crit): ?>
                                                        <td>
                                                            <?php
                                                            $val = $all_alternative_values_detailed[$alt['id']][$crit['id']] ?? '-';
                                                            if ($crit['input_type'] === 'ranges') {
                                                                $ranges_for_display = getCriteriaRanges($conn, $crit['id']);
                                                                $found_range = array_filter($ranges_for_display, function($r) use ($val) {
                                                                    return (float)$r['range_value'] === (float)$val;
                                                                });
                                                                if (!empty($found_range)) {
                                                                    echo htmlspecialchars(array_values($found_range)[0]['range_name']) . ' (' . htmlspecialchars($val) . ')';
                                                                } else {
                                                                    echo htmlspecialchars($val);
                                                                }
                                                            } else {
                                                                echo htmlspecialchars($val);
                                                            }
                                                            ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="table-container" style="overflow-x: auto; margin-top: 20px;">
                                    <h4>Matrix Ternormalisasi & Perhitungan Skor</h4>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Alternatif</th>
                                                <?php foreach ($all_criteria as $criteria): ?>
                                                    <th><?php echo htmlspecialchars($criteria['name']); ?><br><small>Bobot: <?php echo number_format($criteria['weight'], 4); ?></small></th>
                                                <?php endforeach; ?>
                                                <th>Skor Akhir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($current_results as $result): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($result['alternative']['name']); ?></strong></td>
                                                    <?php foreach ($all_criteria as $criteria): ?>
                                                        <?php
                                                        $normalized_value = $result['normalizedValues'][$criteria['id']] ?? 0;
                                                        $weighted_value = $normalized_value * $criteria['weight'];
                                                        ?>
                                                        <td><?php echo number_format($normalized_value, 4); ?><br><small>(Norm x Bobot = <?php echo number_format($weighted_value, 4); ?>)</small></td>
                                                    <?php endforeach; ?>
                                                    <td><strong><?php echo number_format($result['score'], 4); ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>Belum ada detail perhitungan.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div id="projectModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeProjectModal()">&times;</span>
                        <h2>Ubah / Muat Proyek</h2>
                        <p>Pilih proyek yang ada atau mulai proyek baru.</p>

                        <form action="index.php" method="POST" style="margin-top: 20px;">
                            <input type="hidden" name="action" value="set_active_project_name">
                            <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                            <div class="form-group">
                                <label class="form-label">Nama Proyek Baru / Aktifkan yang Ada</label>
                                <input type="text" name="new_active_project_name" class="form-input" placeholder="Masukkan nama proyek baru atau yang sudah ada" value="<?php echo htmlspecialchars($active_project_name); ?>" required />
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Set Proyek Aktif</button>
                        </form>

                        <h3 style="margin-top: 30px;">Daftar Proyek Tersimpan:</h3>
                        <?php if (empty($all_unique_project_names)): ?>
                            <p>Belum ada proyek yang disimpan. Masukkan nama proyek di atas dan set sebagai aktif, lalu tambahkan data.</p>
                        <?php else: ?>
                            <div class="table-container" style="max-height: 300px; overflow-y: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Nama Proyek</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_unique_project_names as $p_name): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($p_name); ?></strong>
                                                    <?php if ($p_name === $active_project_name): ?>
                                                        <br><span style="font-size: 0.8em; color: #11998e;">(Aktif)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form action="index.php" method="POST" style="display:inline-block;">
                                                        <input type="hidden" name="action" value="load_project">
                                                        <input type="hidden" name="project_name_to_load" value="<?php echo htmlspecialchars($p_name); ?>">
                                                        <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary">Muat</button>
                                                    </form>
                                                    <form action="index.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin ingin menghapus proyek ini? SEMUA data (kriteria, alternatif, nilai) di dalamnya akan terhapus.');">
                                                        <input type="hidden" name="action" value="delete_project_by_name">
                                                        <input type="hidden" name="project_name_to_delete" value="<?php echo htmlspecialchars($p_name); ?>">
                                                        <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script> <script>
            // Mengambil data rentang dari PHP ke JS untuk form kriteria (saat edit)
            let phpTempRanges = <?php echo json_encode($temp_ranges_for_js); ?>;

            // Fungsi untuk mengelola tampilan modal proyek
            function showChangeProjectModal() {
                const modal = document.getElementById('projectModal');
                if (modal) modal.style.display = 'block';
            }

            function closeProjectModal() {
                const modal = document.getElementById('projectModal');
                if (modal) modal.style.display = 'none';
            }

            // Close modal if user clicks outside of it
            window.onclick = function(event) {
                const modal = document.getElementById('projectModal');
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }

            // Fungsi-fungsi JS untuk rentang nilai (tetap sama)
            function toggleValueRangesInputPHP(selectedType) {
                const rangesSection = document.getElementById("valueRangesSection");
                if (rangesSection) {
                    rangesSection.classList.remove("hidden");
                    if (selectedType === "numeric") {
                        rangesSection.classList.add("hidden");
                    }
                }
            }

            function addCurrentRangePHP() {
                const nameInput = document.getElementById("rangeName");
                const valueInput = document.getElementById("rangeValue");
                const name = nameInput.value.trim();
                const value = parseFloat(valueInput.value);

                if (!name) { alert("Nama rentang harus diisi!"); return; }
                if (isNaN(value)) { alert("Nilai rentang harus berupa angka!"); return; }
                if (phpTempRanges.some(range => range.range_name.toLowerCase() === name.toLowerCase())) {
                    alert("Nama rentang sudah ada. Gunakan nama lain.");
                    return;
                }

                phpTempRanges.push({ range_name: name, range_value: value });
                renderTempRangesListPHP();
                nameInput.value = "";
                valueInput.value = "";
            }

            function renderTempRangesListPHP() {
                const listContainer = document.getElementById("currentRangesList");
                if (!listContainer) return;

                listContainer.innerHTML = "";
                if (phpTempRanges.length === 0) {
                    listContainer.innerHTML = "<p><small>Belum ada rentang nilai yang ditambahkan.</small></p>";
                    return;
                }
                const ul = document.createElement("ul");
                ul.style.listStyleType = "none";
                ul.style.paddingLeft = "0";
                phpTempRanges.forEach((range, index) => {
                    const li = document.createElement("li");
                    li.style.display = "flex";
                    li.style.justifyContent = "space-between";
                    li.style.alignItems = "center";
                    li.style.marginBottom = "5px";
                    li.style.padding = "5px";
                    li.style.border = "1px solid #eee";
                    li.style.borderRadius = "5px";
                   li.innerHTML = `<span>${htmlspecialchars(String(range.range_name))} (Nilai: ${range.range_value})</span>
                                    <input type="hidden" name="range_names[]" value="${htmlspecialchars(String(range.range_name))}">
                                    <input type="hidden" name="range_values[]" value="${htmlspecialchars(String(range.range_value))}">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeCurrentRangePHP(this, ${index})" style="padding: 2px 6px; font-size:12px;">X</button>`;
                    ul.appendChild(li);
                });
                listContainer.appendChild(ul);
            }

            function removeCurrentRangePHP(buttonElement, index) {
                phpTempRanges.splice(index, 1);
                renderTempRangesListPHP();
            }

            function htmlspecialchars(str) {
                str = String(str);
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }

            // Fungsi showTab yang dimodifikasi untuk PHP (sudah ada di index.php)
            function showTab(tabName, clickedTabElement) {
                document.querySelectorAll(".tab-pane").forEach(tab => { tab.classList.remove("active"); });
                document.querySelectorAll(".nav-tab").forEach(tab => { tab.classList.remove("active"); });
                document.getElementById(tabName).classList.add("active");
                if (clickedTabElement) {
                    clickedTabElement.classList.add("active");
                } else {
                    const targetTabButton = document.querySelector(`.nav-tab[onclick*="'${tabName}'"]`);
                    if (targetTabButton) targetTabButton.classList.add("active");
                }
                // Update URL tanpa reload halaman (opsional, untuk menjaga tab aktif saat refresh manual)
                history.pushState(null, '', `index.php?tab=${tabName}`);
            }

            // Inisialisasi aplikasi saat DOM dimuat
            document.addEventListener("DOMContentLoaded", function() {
                const initialTab = "<?php echo $active_tab; ?>";
                showTab(initialTab);
                
                renderTempRangesListPHP();

                const criteriaInputTypeSelect = document.getElementById("criteriaInputType");
                if (criteriaInputTypeSelect) {
                    const initialInputType = criteriaInputTypeSelect.value;
                    toggleValueRangesInputPHP(initialInputType);
                }
            });
        </script>
    </body>
    </html>
    ```