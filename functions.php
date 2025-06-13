<?php

// Catatan: Variabel $conn diasumsikan tersedia dari file yang meng-include functions.php (misalnya index.php)

/**
* Fungsi untuk mendapatkan semua nama proyek unik dari database.
* @param mysqli $conn Objek koneksi database.
* @return array Array string nama proyek.
*/
function getAllUniqueProjectNames(mysqli $conn): array {
    $projectNames = [];
    $sql = "SELECT DISTINCT project_name FROM criteria UNION SELECT DISTINCT project_name FROM alternatives ORDER BY project_name ASC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $projectNames[] = $row['project_name'];
        }
    }
    return $projectNames;
}

/**
* Fungsi untuk menghapus semua data (kriteria, alternatif, nilai) yang terkait dengan project_name tertentu.
* @param mysqli $conn Objek koneksi database.
* @param string $project_name Nama proyek yang akan dihapus.
* @return bool True jika berhasil, false jika gagal.
*/
function deleteProjectByName(mysqli $conn, string $project_name): bool {
    $conn->begin_transaction();
    try {
        // Hapus alternatif yang terkait dengan project_name ini (ini akan CASCADE ke alternative_values)
        $stmt_del_alt = $conn->prepare("DELETE FROM alternatives WHERE project_name = ?");
        if (!$stmt_del_alt) { throw new Exception("Prepare delete alternatives failed: " . $conn->error); }
        $stmt_del_alt->bind_param("s", $project_name);
        if (!$stmt_del_alt->execute()) { throw new Exception("Execute delete alternatives failed: " . $stmt_del_alt->error); }
        $stmt_del_alt->close();

        // Hapus kriteria yang terkait dengan project_name ini (ini akan CASCADE ke criteria_value_ranges dan alternative_values)
        $stmt_del_crit = $conn->prepare("DELETE FROM criteria WHERE project_name = ?");
        if (!$stmt_del_crit) { throw new Exception("Prepare delete criteria failed: " . $conn->error); }
        $stmt_del_crit->bind_param("s", $project_name);
        if (!$stmt_del_crit->execute()) { throw new Exception("Execute delete criteria failed: " . $stmt_del_crit->error); }
        $stmt_del_crit->close();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting project '" . $project_name . "': " . $e->getMessage());
        return false;
    }
}


/**
* Fungsi untuk mendapatkan semua kriteria dari database, diurutkan berdasarkan sort_order.
* @param mysqli $conn Objek koneksi database.
* @param string $project_name Nama proyek yang akan difilter.
* @return array Array asosiatif dari semua kriteria.
*/
function getAllCriteria(mysqli $conn, string $project_name): array {
    $criteria = [];
    $sql = "SELECT id, name, type, unit, sort_order, input_type FROM criteria WHERE project_name = ? ORDER BY sort_order ASC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare getAllCriteria failed: " . $conn->error);
        return [];
    }
    $stmt->bind_param("s", $project_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $criteria[] = $row;
        }
    }
    $stmt->close();
    return $criteria;
}

/**
* Fungsi untuk mendapatkan detail kriteria berdasarkan ID.
* @param mysqli $conn Objek koneksi database.
* @param int $criteria_id ID kriteria.
* @return array|null Data kriteria atau null jika tidak ditemukan.
*/
function getCriteriaById(mysqli $conn, int $criteria_id): ?array {
    $stmt = $conn->prepare("SELECT id, name, type, unit, sort_order, input_type, project_name FROM criteria WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare getCriteriaById failed: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $criteria_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

/**
* Fungsi untuk mendapatkan rentang nilai kriteria berdasarkan ID kriteria.
* @param mysqli $conn Objek koneksi database.
* @param int $criteria_id ID kriteria.
* @return array Array asosiatif dari rentang nilai.
*/
function getCriteriaRanges(mysqli $conn, int $criteria_id): array {
    $ranges = [];
    $stmt = $conn->prepare("SELECT range_name, range_value FROM criteria_value_ranges WHERE criteria_id = ? ORDER BY range_value ASC");
    if (!$stmt) {
        error_log("Prepare getCriteriaRanges failed: " . $conn->error);
        return [];
    }
    $stmt->bind_param("i", $criteria_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ranges[] = $row;
        }
    }
    $stmt->close();
    return $ranges;
}


/**
* Fungsi untuk menambahkan kriteria baru ke database.
* @param mysqli $conn Objek koneksi database.
* @param string $project_name Nama proyek tempat kriteria akan ditambahkan.
* @param string $name Nama kriteria.
* @param string $type Tipe kriteria ('benefit' atau 'cost').
* @param string $unit Satuan kriteria.
* @param string $input_type Tipe input ('numeric' atau 'ranges').
* @param array $ranges Array rentang nilai jika input_type adalah 'ranges'. (Format: [['range_name' => 'SD', 'range_value' => 1], ...])
* @return bool True jika berhasil, false jika gagal.
*/
function addCriteria(mysqli $conn, string $project_name, string $name, string $type, string $unit, string $input_type, array $ranges = []): bool {
    // Dapatkan urutan terakhir untuk kriteria dalam proyek ini
    $sql_max_order = "SELECT MAX(sort_order) as max_order FROM criteria WHERE project_name = ?";
    $stmt_max = $conn->prepare($sql_max_order);
    if (!$stmt_max) { throw new Exception("Prepare max order failed: " . $conn->error); }
    $stmt_max->bind_param("s", $project_name);
    $stmt_max->execute();
    $result = $stmt_max->get_result();
    $row = $result->fetch_assoc();
    $sort_order = ($row['max_order'] ?? 0) + 1;
    $stmt_max->close();

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO criteria (project_name, name, type, unit, sort_order, input_type) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) { throw new Exception("Prepare insert criteria failed: " . $conn->error); }
        $stmt->bind_param("ssssds", $project_name, $name, $type, $unit, $sort_order, $input_type);
        if (!$stmt->execute()) { throw new Exception("Execute insert criteria failed: " . $stmt->error); }
        $criteria_id = $stmt->insert_id;
        $stmt->close();

        if ($input_type === 'ranges' && !empty($ranges)) {
            $stmt_range = $conn->prepare("INSERT INTO criteria_value_ranges (criteria_id, range_name, range_value) VALUES (?, ?, ?)");
            if (!$stmt_range) { throw new Exception("Prepare insert range failed: " . $conn->error); }
            foreach ($ranges as $range_data) {
                if (!isset($range_data['range_name']) || !isset($range_data['range_value'])) {
                    throw new Exception("Format rentang tidak valid (kurang 'range_name' atau 'range_value').");
                }
                $range_name = $range_data['range_name'];
                $range_value = (float) $range_data['range_value'];
                $stmt_range->bind_param("isd", $criteria_id, $range_name, $range_value);
                if (!$stmt_range->execute()) { throw new Exception("Execute insert range failed: " . $stmt_range->error); }
            }
            $stmt_range->close();
        }
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error adding criteria: " . $e->getMessage());
        return false;
    }
}

/**
* Fungsi untuk mengupdate kriteria di database.
* @param mysqli $conn Objek koneksi database.
* @param int $id ID kriteria yang akan diupdate.
* @param string $name Nama kriteria.
* @param string $type Tipe kriteria ('benefit' atau 'cost').
* @param string $unit Satuan kriteria.
* @param string $input_type Tipe input ('numeric' atau 'ranges').
* @param array $ranges Array rentang nilai jika input_type adalah 'ranges'. (Format: [['range_name' => 'SD', 'range_value' => 1], ...])
* @return bool True jika berhasil, false jika gagal.
*/
function updateCriteria(mysqli $conn, int $id, string $name, string $type, string $unit, string $input_type, array $ranges = []): bool {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE criteria SET name = ?, type = ?, unit = ?, input_type = ? WHERE id = ?");
        if (!$stmt) { throw new Exception("Prepare update criteria failed: " . $conn->error); }
        $stmt->bind_param("ssssi", $name, $type, $unit, $input_type, $id);
        if (!$stmt->execute()) { throw new Exception("Execute update criteria failed: " . $stmt->error); }
        $stmt->close();

        // Hapus rentang lama
        $stmt_delete_ranges = $conn->prepare("DELETE FROM criteria_value_ranges WHERE criteria_id = ?");
        if (!$stmt_delete_ranges) { throw new Exception("Prepare delete ranges failed: " . $conn->error); }
        $stmt_delete_ranges->bind_param("i", $id);
        if (!$stmt_delete_ranges->execute()) { throw new Exception("Execute delete ranges failed: " . $stmt_delete_ranges->error); }
        $stmt_delete_ranges->close();

        // Masukkan rentang baru jika input_type adalah 'ranges'
        if ($input_type === 'ranges' && !empty($ranges)) {
            $stmt_insert_range = $conn->prepare("INSERT INTO criteria_value_ranges (criteria_id, range_name, range_value) VALUES (?, ?, ?)");
            if (!$stmt_insert_range) { throw new Exception("Prepare insert range failed: " . $conn->error); }
            foreach ($ranges as $range_data) {
                if (!isset($range_data['range_name']) || !isset($range_data['range_value'])) {
                    throw new Exception("Format rentang tidak valid (kurang 'range_name' atau 'range_value').");
                }
                $range_name = $range_data['range_name'];
                $range_value = (float) $range_data['range_value'];
                $stmt_insert_range->bind_param("isd", $id, $range_name, $range_value);
                if (!$stmt_insert_range->execute()) { throw new Exception("Execute insert range failed: " . $stmt_insert_range->error); }
            }
            $stmt_insert_range->close();
        }
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating criteria: " . $e->getMessage());
        return false;
    }
}

/**
* Fungsi untuk menghapus kriteria dari database.
* @param mysqli $conn Objek koneksi database.
* @param int $id ID kriteria yang akan dihapus.
* @return bool True jika berhasil, false jika gagal.
*/
function deleteCriteria(mysqli $conn, int $id): bool {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM criteria WHERE id = ?");
        if (!$stmt) { throw new Exception("Prepare delete criteria failed: " . $conn->error); }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) { throw new Exception("Execute delete criteria failed: " . $stmt->error); }
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting criteria: " . $e->getMessage());
        return false;
    }
}

/**
* Fungsi untuk mengubah urutan kriteria (naik atau turun).
* @param mysqli $conn Objek koneksi database.
* @param int $criteria_id ID kriteria yang akan dipindahkan.
* @param string $direction Arah pergerakan ('up' atau 'down').
* @param string $project_name Nama proyek terkait.
* @return bool True jika berhasil, false jika gagal.
*/
function moveCriteria(mysqli $conn, int $criteria_id, string $direction, string $project_name): bool {
    $conn->begin_transaction();
    try {
        $sql_select_current = "SELECT id, sort_order FROM criteria WHERE id = ? AND project_name = ?";
        $stmt = $conn->prepare($sql_select_current);
        if (!$stmt) { throw new Exception("Prepare select current criteria failed: " . $conn->error); }
        $stmt->bind_param("is", $criteria_id, $project_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_criteria = $result->fetch_assoc();
        $stmt->close();

        if (!$current_criteria) { $conn->rollback(); return false; } // Kriteria tidak ditemukan atau bukan bagian dari proyek ini.

        $current_order = $current_criteria['sort_order'];
        $sql_target = "SELECT id, sort_order FROM criteria WHERE project_name = ? AND sort_order ";
        if ($direction === 'up') { $sql_target .= "< ? ORDER BY sort_order DESC LIMIT 1"; }
        else { $sql_target .= "> ? ORDER BY sort_order ASC LIMIT 1"; }

        $stmt_target = $conn->prepare($sql_target);
        if (!$stmt_target) { throw new Exception("Prepare select target criteria failed: " . $conn->error); }
        $stmt_target->bind_param("si", $project_name, $current_order);
        $stmt_target->execute();
        $result_target = $stmt_target->get_result();
        $target_criteria = $result_target->fetch_assoc();
        $stmt_target->close();

        if (!$target_criteria) { $conn->rollback(); return false; } // Sudah di paling atas/bawah

        $target_order = $target_criteria['sort_order'];
        $target_criteria_id = $target_criteria['id'];

        // Tukar sort_order
        $stmt_update1 = $conn->prepare("UPDATE criteria SET sort_order = ? WHERE id = ?");
        if (!$stmt_update1) { throw new Exception("Prepare update criteria 1 failed: " . $conn->error); }
        $stmt_update1->bind_param("ii", $target_order, $current_criteria['id']);
        if (!$stmt_update1->execute()) { throw new Exception("Execute update criteria 1 failed: " . $stmt_update1->error); }
        $stmt_update1->close();

        $stmt_update2 = $conn->prepare("UPDATE criteria SET sort_order = ? WHERE id = ?");
        if (!$stmt_update2) { throw new Exception("Prepare update criteria 2 failed: " . $conn->error); }
        $stmt_update2->bind_param("ii", $current_order, $target_criteria_id);
        if (!$stmt_update2->execute()) { throw new Exception("Execute update criteria 2 failed: " . $stmt_update2->error); }
        $stmt_update2->close();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error moving criteria: " . $e->getMessage());
        return false;
    }
}


/**
* Fungsi untuk mendapatkan semua alternatif dari database.
* @param mysqli $conn Objek koneksi database.
* @param string $project_name Nama proyek yang akan difilter.
* @return array Array asosiatif dari semua alternatif.
*/
function getAllAlternatives(mysqli $conn, string $project_name): array {
    $alternatives = [];
    $sql = "SELECT id, name FROM alternatives WHERE project_name = ? ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare getAllAlternatives failed: " . $conn->error);
        return [];
    }
    $stmt->bind_param("s", $project_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $alternatives[] = $row;
        }
    }
    $stmt->close();
    return $alternatives;
}

/**
* Fungsi untuk mendapatkan detail alternatif berdasarkan ID, beserta nilai-nilai kriterianya.
* @param mysqli $conn Objek koneksi database.
* @param int $alternative_id ID alternatif.
* @return array|null Data alternatif atau null jika tidak ditemukan.
*/
function getAlternativeById(mysqli $conn, int $alternative_id): ?array {
    $stmt = $conn->prepare("SELECT id, name, project_name FROM alternatives WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare getAlternativeById failed: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $alternative_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $alternative = $result->fetch_assoc();
    $stmt->close();

    if ($alternative) {
        $alternative['values'] = [];
        $stmt_values = $conn->prepare("SELECT criteria_id, value FROM alternative_values WHERE alternative_id = ?");
        if (!$stmt_values) {
            error_log("Prepare get alternative values failed: " . $conn->error);
            return $alternative;
        }
        $stmt_values->bind_param("i", $alternative_id);
        $stmt_values->execute();
        $result_values = $stmt_values->get_result();
        while ($row_val = $result_values->fetch_assoc()) {
            $alternative['values'][$row_val['criteria_id']] = (float)$row_val['value'];
        }
        $stmt_values->close();
    }
    return $alternative;
}

/**
* Fungsi untuk menambahkan alternatif baru ke database.
* @param mysqli $conn Objek koneksi database.
* @param string $project_name Nama proyek tempat alternatif akan ditambahkan.
* @param string $name Nama alternatif.
* @param array $values Array asosiatif [criteria_id => value].
* @return bool True jika berhasil, false jika gagal.
*/
function addAlternative(mysqli $conn, string $project_name, string $name, array $values): bool {
    $conn->begin_transaction();
    try {
        $stmt_alt = $conn->prepare("INSERT INTO alternatives (project_name, name) VALUES (?, ?)");
        if (!$stmt_alt) { throw new Exception("Prepare add alternative failed: " . $conn->error); }
        $stmt_alt->bind_param("ss", $project_name, $name);
        if (!$stmt_alt->execute()) { throw new Exception("Execute add alternative failed: " . $stmt_alt->error); }
        $alternative_id = $stmt_alt->insert_id;
        $stmt_alt->close();

        if (!empty($values)) {
            $stmt_val = $conn->prepare("INSERT INTO alternative_values (alternative_id, criteria_id, value) VALUES (?, ?, ?)");
            if (!$stmt_val) { throw new Exception("Prepare add alternative values failed: " . $conn->error); }
            foreach ($values as $criteria_id => $value) {
                $value_float = (float) $value;
                $stmt_val->bind_param("iid", $alternative_id, $criteria_id, $value_float);
                if (!$stmt_val->execute()) { throw new Exception("Execute add alternative value failed: " . $stmt_val->error); }
            }
            $stmt_val->close();
        }
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error adding alternative: " . $e->getMessage());
        return false;
    }
}

/**
* Fungsi untuk mengupdate alternatif dan nilai-nilai kriterianya.
* @param mysqli $conn Objek koneksi database.
* @param int $id ID alternatif.
* @param string $name Nama alternatif.
* @param array $values Array asosiatif [criteria_id => value].
* @return bool True jika berhasil, false jika gagal.
*/
function updateAlternative(mysqli $conn, int $id, string $name, array $values): bool {
    $conn->begin_transaction();
    try {
        $stmt_alt = $conn->prepare("UPDATE alternatives SET name = ? WHERE id = ?");
        if (!$stmt_alt) { throw new Exception("Prepare update alternative failed: " . $conn->error); }
        $stmt_alt->bind_param("si", $name, $id);
        if (!$stmt_alt->execute()) { throw new Exception("Execute update alternative failed: " . $stmt_alt->error); }
        $stmt_alt->close();

        // Hapus nilai lama
        $stmt_del_val = $conn->prepare("DELETE FROM alternative_values WHERE alternative_id = ?");
        if (!$stmt_del_val) { throw new Exception("Prepare delete alternative values failed: " . $conn->error); }
        $stmt_del_val->bind_param("i", $id);
        if (!$stmt_del_val->execute()) { throw new Exception("Execute delete alternative values failed: " . $stmt_del_val->error); }
        $stmt_del_val->close();

        // Masukkan nilai baru
        if (!empty($values)) {
            $stmt_add_val = $conn->prepare("INSERT INTO alternative_values (alternative_id, criteria_id, value) VALUES (?, ?, ?)");
            if (!$stmt_add_val) { throw new Exception("Prepare insert alternative values failed: " . $conn->error); }
            foreach ($values as $criteria_id => $value) {
                $value_float = (float) $value;
                $stmt_add_val->bind_param("iid", $id, $criteria_id, $value_float);
                if (!$stmt_add_val->execute()) { throw new Exception("Execute insert alternative value failed: " . $stmt_add_val->error); }
            }
            $stmt_add_val->close();
        }
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating alternative: " . $e->getMessage());
        return false;
    }
}

/**
* Fungsi untuk menghapus alternatif dari database.
* @param mysqli $conn Objek koneksi database.
* @param int $id ID alternatif.
* @return bool True jika berhasil, false jika gagal.
*/
function deleteAlternative(mysqli $conn, int $id): bool {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM alternatives WHERE id = ?");
        if (!$stmt) { throw new Exception("Prepare delete alternative failed: " . $conn->error); }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) { throw new Exception("Execute delete alternative failed: " . $stmt->error); }
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting alternative: " . $e->getMessage());
        return false;
    }
}


// --- Fungsi Perhitungan ROC-SAW ---

/**
* Fungsi untuk menghitung bobot ROC (Rank Order Centroid).
* @param array &$criteria Array kriteria yang akan diisi bobotnya (passed by reference).
*/
function calculateROCWeights(array &$criteria) {
    $n = count($criteria);
    if ($n === 0) return;

    foreach ($criteria as $i => &$crit) {
        $weight = 0;
        for ($j = $i; $j < $n; $j++) {
            $weight += 1 / ($j + 1);
        }
        $crit['weight'] = $weight / $n;
    }
    unset($crit);
}

/**
* Fungsi utama untuk melakukan perhitungan SAW.
* @param mysqli $conn Objek koneksi database.
* @param string $project_name Nama proyek yang akan digunakan untuk perhitungan.
* @return array Array hasil perangkingan, atau array kosong jika tidak ada data yang cukup.
*/
function calculateROC_SAW(mysqli $conn, string $project_name): array {
    $results = [];

    $criteria = getAllCriteria($conn, $project_name); // Panggil dengan project_name
    if (empty($criteria)) {
        return [];
    }
    calculateROCWeights($criteria);

    $alternatives = getAllAlternatives($conn, $project_name); // Panggil dengan project_name
    if (empty($alternatives)) {
        return [];
    }

    $normalizedMatrix = [];

    // Proses Normalisasi
    foreach ($criteria as $crit) {
        $criteria_id = $crit['id'];
        $crit_type = $crit['type'];

        $values_for_criteria = [];
        $stmt_values = $conn->prepare("SELECT alternative_id, value FROM alternative_values WHERE criteria_id = ?");
        if (!$stmt_values) { error_log("Prepare values for criteria failed: " . $conn->error); continue; }
        $stmt_values->bind_param("i", $criteria_id);
        $stmt_values->execute();
        $result_values = $stmt_values->get_result();
        while ($row_val = $result_values->fetch_assoc()) {
            $values_for_criteria[$row_val['alternative_id']] = (float)$row_val['value'];
        }
        $stmt_values->close();

        $numeric_values = array_values($values_for_criteria);
        $numeric_values = array_filter($numeric_values, function($v) {
            return is_numeric($v) && !is_infinite($v) && !is_nan($v);
        });

        if (empty($numeric_values)) {
            foreach ($alternatives as $alt) {
                if (!isset($normalizedMatrix[$alt['id']])) {
                    $normalizedMatrix[$alt['id']] = [];
                }
                $normalizedMatrix[$alt['id']][$criteria_id] = 0;
            }
            continue;
        }

        $maxValue = max($numeric_values);
        $minValue = min($numeric_values);

        foreach ($alternatives as $alt) {
            $alt_id = $alt['id'];
            $alt_value = $values_for_criteria[$alt_id] ?? null;

            if (!isset($normalizedMatrix[$alt_id])) {
                $normalizedMatrix[$alt_id] = [];
            }

            if ($alt_value === null || !is_numeric($alt_value) || is_infinite($alt_value) || is_nan($alt_value)) {
                $normalizedMatrix[$alt_id][$criteria_id] = 0;
                continue;
            }

            if ($crit_type === 'benefit') {
                $normalizedMatrix[$alt_id][$criteria_id] = ($maxValue == 0) ? 0 : $alt_value / $maxValue;
            } else { // cost
                if ($alt_value == 0) {
                    $normalizedMatrix[$alt_id][$criteria_id] = 1;
                } else if ($minValue == 0) {
                    $normalizedMatrix[$alt_id][$criteria_id] = 0;
                } else {
                    $normalizedMatrix[$alt_id][$criteria_id] = $minValue / $alt_value;
                }
            }
            if (is_nan($normalizedMatrix[$alt_id][$criteria_id]) || !is_finite($normalizedMatrix[$alt_id][$criteria_id])) {
                $normalizedMatrix[$alt_id][$criteria_id] = 0;
            }
        }
    }

    // Perhitungan Skor Akhir (SAW)
    foreach ($alternatives as $alt) {
        $totalScore = 0;
        $alt_normalized_values = [];

        foreach ($criteria as $crit) {
            $criteria_id = $crit['id'];
            $weight = $crit['weight'];
            $normalized_value = $normalizedMatrix[$alt['id']][$criteria_id] ?? 0;

            $totalScore += $normalized_value * $weight;
            $alt_normalized_values[$criteria_id] = $normalized_value;
        }

        $results[] = [
            'alternative' => $alt,
            'score' => $totalScore,
            'normalizedValues' => $alt_normalized_values
        ];
    }

    usort($results, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return $results;
}

?>