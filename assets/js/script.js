// Global variables
let projectData = {
  title: "Proyek Keputusan Default",
  description: "",
  criteria: [],
  alternatives: [],
  results: [],
};

let currentEditingCriteriaIndex = null;
let currentEditingAlternativeIndex = null;
let tempRanges = [];

// Initialize the application
function init() {
  updateUI();
  showTab("criteria", document.querySelector('.nav-tab[onclick*="criteria"]'));
}

// Tab functionality
function showTab(tabName, clickedTabElement) {
  document.querySelectorAll(".tab-pane").forEach((tab) => {
    tab.classList.remove("active");
  });
  document.querySelectorAll(".nav-tab").forEach((tab) => {
    tab.classList.remove("active");
  });

  document.getElementById(tabName).classList.add("active");
  if (clickedTabElement) {
    clickedTabElement.classList.add("active");
  } else {
    const targetTabButton = document.querySelector(
      `.nav-tab[onclick*="${tabName}"]`
    );
    if (targetTabButton) targetTabButton.classList.add("active");
  }

  if (tabName === "criteria") {
    updateCriteriaList();
    if (currentEditingCriteriaIndex === null) resetCriteriaForm();
  } else if (tabName === "alternatives") {
    renderCriteriaInputsForAlternative("alternativeCriteriaInputsContainer");
    updateAlternativesList();
    if (currentEditingAlternativeIndex === null) resetAlternativeForm();
  } else if (tabName === "results") {
    updateResults();
  }
}

function toggleValueRangesInput() {
  const inputType = document.getElementById("criteriaInputType").value;
  const rangesSection = document.getElementById("valueRangesSection");
  if (inputType === "ranges") {
    rangesSection.classList.remove("hidden");
  } else {
    rangesSection.classList.add("hidden");
  }
}

function addCurrentRange() {
  const name = document.getElementById("rangeName").value.trim();
  const value = parseFloat(document.getElementById("rangeValue").value);

  if (!name) {
    showAlert("Nama rentang harus diisi!", "danger", "criteria");
    return;
  }
  if (isNaN(value)) {
    showAlert("Nilai rentang harus berupa angka!", "danger", "criteria");
    return;
  }
  if (tempRanges.some((range) => range.name === name)) {
    showAlert(
      "Nama rentang sudah ada. Gunakan nama lain.",
      "danger",
      "criteria"
    );
    return;
  }

  tempRanges.push({ name, value });
  renderTempRangesList();
  document.getElementById("rangeName").value = "";
  document.getElementById("rangeValue").value = "";
}

function renderTempRangesList() {
  const listContainer = document.getElementById("currentRangesList");
  listContainer.innerHTML = "";
  if (tempRanges.length === 0) {
    listContainer.innerHTML =
      "<p><small>Belum ada rentang nilai yang ditambahkan.</small></p>";
    return;
  }
  const ul = document.createElement("ul");
  ul.style.listStyleType = "none";
  ul.style.paddingLeft = "0";
  tempRanges.forEach((range, index) => {
    const li = document.createElement("li");
    li.style.display = "flex";
    li.style.justifyContent = "space-between";
    li.style.alignItems = "center";
    li.style.marginBottom = "5px";
    li.style.padding = "5px";
    li.style.border = "1px solid #eee";
    li.style.borderRadius = "5px";
    li.innerHTML = `<span>${range.name} (Nilai: ${range.value})</span> 
                                <button class="btn btn-danger btn-sm" onclick="removeTempRange(${index})" style="padding: 2px 6px; font-size:12px;">X</button>`;
    ul.appendChild(li);
  });
  listContainer.appendChild(ul);
}

function removeTempRange(index) {
  tempRanges.splice(index, 1);
  renderTempRangesList();
}

// Criteria management
function addCriteria() {
  const name = document.getElementById("criteriaName").value;
  const type = document.getElementById("criteriaType").value;
  const unit = document.getElementById("criteriaUnit").value;
  const inputType = document.getElementById("criteriaInputType").value;

  if (!name) {
    showAlert("Nama kriteria harus diisi!", "danger", "criteria");
    return;
  }

  const newCriteria = {
    id: Date.now(),
    name: name,
    type: type,
    unit: unit || "",
    weight: 0,
    inputType: inputType,
    valueRanges: inputType === "ranges" ? [...tempRanges] : [],
  };

  if (inputType === "ranges" && tempRanges.length === 0) {
    showAlert(
      'Untuk tipe input "Rentang Nilai", minimal satu rentang harus didefinisikan.',
      "danger",
      "criteria"
    );
    return;
  }

  projectData.criteria.push(newCriteria);
  resetCriteriaForm();
  updateCriteriaList();
  showAlert("Kriteria berhasil ditambahkan!", "success", "criteria");
}

function updateCriteria(index) {
  const name = document.getElementById("criteriaName").value;
  const type = document.getElementById("criteriaType").value;
  const unit = document.getElementById("criteriaUnit").value;
  const inputType = document.getElementById("criteriaInputType").value;

  if (!name) {
    showAlert("Nama kriteria harus diisi!", "danger", "criteria");
    return;
  }
  if (inputType === "ranges" && tempRanges.length === 0) {
    showAlert(
      'Untuk tipe input "Rentang Nilai", minimal satu rentang harus didefinisikan.',
      "danger",
      "criteria"
    );
    return;
  }

  projectData.criteria[index] = {
    ...projectData.criteria[index],
    name: name,
    type: type,
    unit: unit || "",
    inputType: inputType,
    valueRanges: inputType === "ranges" ? [...tempRanges] : [],
  };

  resetCriteriaForm();
  updateCriteriaList();
  showAlert("Kriteria berhasil diupdate!", "success", "criteria");
}

function clearCriteriaForm() {
  document.getElementById("criteriaName").value = "";
  document.getElementById("criteriaType").value = "benefit";
  document.getElementById("criteriaUnit").value = "";
  document.getElementById("criteriaInputType").value = "numeric";
  tempRanges = [];
  renderTempRangesList();
  toggleValueRangesInput();
}

function resetCriteriaForm() {
  clearCriteriaForm();
  const addButton = document.getElementById("addCriteriaBtn");
  addButton.textContent = "Tambah Kriteria";
  addButton.onclick = addCriteria;
  currentEditingCriteriaIndex = null;
}

function updateCriteriaList() {
  const criteriaList = document.getElementById("criteriaList");
  if (!criteriaList) return;

  if (projectData.criteria.length === 0) {
    criteriaList.innerHTML =
      "<p>Belum ada kriteria. Tambahkan kriteria terlebih dahulu.</p>";
    renderCriteriaInputsForAlternative("alternativeCriteriaInputsContainer");
    return;
  }

  calculateROCWeights();

  let html = '<div class="table-container">';
  html += '<table class="table">';
  html +=
    "<thead><tr><th>Urutan</th><th>Kriteria</th><th>Tipe</th><th>Tipe Input</th><th>Bobot ROC</th><th>Aksi</th></tr></thead>";
  html += "<tbody>";

  projectData.criteria.forEach((criteria, index) => {
    let inputTypeDisplay =
      criteria.inputType === "ranges" ? "Rentang Nilai" : "Angka Langsung";
    if (
      criteria.inputType === "ranges" &&
      criteria.valueRanges &&
      criteria.valueRanges.length > 0
    ) {
      inputTypeDisplay += ` <small>(${criteria.valueRanges
        .map((r) => r.name)
        .join(", ")})</small>`;
    }

    html += `
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span><strong>${index + 1}</strong></span>
                                <div>
                                    <button class="btn btn-sm btn-primary" onclick="moveCriteriaUp(${index})" 
                                            ${index === 0 ? "disabled" : ""} 
                                            style="padding: 4px 8px; font-size: 12px; margin-right: 2px;">↑</button>
                                    <button class="btn btn-sm btn-primary" onclick="moveCriteriaDown(${index})" 
                                            ${
                                              index ===
                                              projectData.criteria.length - 1
                                                ? "disabled"
                                                : ""
                                            } 
                                            style="padding: 4px 8px; font-size: 12px;">↓</button>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong>${criteria.name}</strong>
                            ${
                              criteria.unit
                                ? `<br><small>(${criteria.unit})</small>`
                                : ""
                            }
                        </td>
                        <td>${
                          criteria.type === "benefit" ? "Benefit" : "Cost"
                        }</td>
                        <td>${inputTypeDisplay}</td>
                        <td>${criteria.weight.toFixed(4)}</td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editCriteria(${index})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteCriteria(${index})">Hapus</button>
                        </td>
                    </tr>
                `;
  });

  html += "</tbody></table></div>";
  criteriaList.innerHTML = html;
  renderCriteriaInputsForAlternative("alternativeCriteriaInputsContainer");
}

function moveCriteriaUp(index) {
  if (index > 0) {
    const temp = projectData.criteria[index];
    projectData.criteria[index] = projectData.criteria[index - 1];
    projectData.criteria[index - 1] = temp;
    updateCriteriaList();
    showAlert("Urutan kriteria berhasil diubah!", "success", "criteria");
  }
}

function moveCriteriaDown(index) {
  if (index < projectData.criteria.length - 1) {
    const temp = projectData.criteria[index];
    projectData.criteria[index] = projectData.criteria[index + 1];
    projectData.criteria[index + 1] = temp;
    updateCriteriaList();
    showAlert("Urutan kriteria berhasil diubah!", "success", "criteria");
  }
}

function calculateROCWeights() {
  const n = projectData.criteria.length;
  if (n === 0) return;

  for (let i = 0; i < n; i++) {
    let weight = 0;
    for (let j = i; j < n; j++) {
      weight += 1 / (j + 1);
    }
    projectData.criteria[i].weight = weight / n;
  }
}

function deleteCriteria(index) {
  if (confirm("Yakin ingin menghapus kriteria ini?")) {
    const critIdToDelete = projectData.criteria[index].id; // Get ID before splice
    projectData.criteria.splice(index, 1);
    updateCriteriaList();
    if (currentEditingCriteriaIndex === index) {
      resetCriteriaForm();
    }
    projectData.alternatives.forEach((alt) => {
      if (alt.values && alt.values[critIdToDelete]) {
        delete alt.values[critIdToDelete];
      }
    });
    showAlert("Kriteria berhasil dihapus!", "success", "criteria");
  }
}

function editCriteria(index) {
  const criteria = projectData.criteria[index];
  document.getElementById("criteriaName").value = criteria.name;
  document.getElementById("criteriaType").value = criteria.type;
  document.getElementById("criteriaUnit").value = criteria.unit || "";
  document.getElementById("criteriaInputType").value = criteria.inputType;

  tempRanges =
    criteria.inputType === "ranges" && criteria.valueRanges
      ? [...criteria.valueRanges]
      : [];
  renderTempRangesList();
  toggleValueRangesInput();

  const updateButton = document.getElementById("addCriteriaBtn");
  updateButton.textContent = "Update Kriteria";
  updateButton.onclick = () => updateCriteria(index);
  currentEditingCriteriaIndex = index;

  showAlert(
    "Data kriteria dimuat untuk diedit. Scroll ke atas untuk melihat form.",
    "success",
    "criteria"
  );
  document.getElementById("criteriaName").focus();
}

//--- Alternative Management & Value Input ---
function renderCriteriaInputsForAlternative(
  containerId,
  existingValues = {}
) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.innerHTML = "";

  if (projectData.criteria.length === 0) {
    container.innerHTML =
      "<p><small>Belum ada kriteria yang didefinisikan. Silakan tambahkan kriteria terlebih dahulu di tab Kriteria.</small></p>";
    return;
  }

  projectData.criteria.forEach((crit) => {
    const inputGroup = document.createElement("div");
    inputGroup.className = "form-group";

    const label = document.createElement("label");
    label.className = "form-label";
    label.textContent = `${crit.name} ${
      crit.unit ? "(" + crit.unit + ")" : ""
    }`;
    label.htmlFor = `alt-crit-${crit.id}`;
    inputGroup.appendChild(label);

    let inputElement;
    const currentValue =
      existingValues[crit.id] !== undefined ? existingValues[crit.id] : "";

    if (
      crit.inputType === "ranges" &&
      crit.valueRanges &&
      crit.valueRanges.length > 0
    ) {
      inputElement = document.createElement("select");
      inputElement.className = "form-select";
      inputElement.id = `alt-crit-${crit.id}`;

      const defaultOption = document.createElement("option");
      defaultOption.value = "";
      defaultOption.textContent = "Pilih...";
      inputElement.appendChild(defaultOption);

      crit.valueRanges.forEach((range) => {
        const option = document.createElement("option");
        option.value = range.value;
        option.textContent = `${range.name} (${range.value})`;
        if (parseFloat(currentValue) === range.value) {
          option.selected = true;
        }
        inputElement.appendChild(option);
      });
    } else {
      inputElement = document.createElement("input");
      inputElement.type = "number";
      inputElement.className = "form-input";
      inputElement.id = `alt-crit-${crit.id}`;
      inputElement.value = currentValue;
      inputElement.step = "0.01";
    }
    inputGroup.appendChild(inputElement);
    container.appendChild(inputGroup);
  });
}

function addAlternative() {
  const name = document.getElementById("alternativeName").value.trim();
  if (!name) {
    showAlert("Nama alternatif harus diisi!", "danger", "alternatives");
    return;
  }

  const alternativeValues = {};
  let allValuesFilledOrValid = true;
  for (const crit of projectData.criteria) {
    const inputElement = document.getElementById(`alt-crit-${crit.id}`);
    if (inputElement) {
      const value = inputElement.value;
      if (value === "" || value === null) {
        allValuesFilledOrValid = false;
        break;
      }
      alternativeValues[crit.id] = parseFloat(value);
      if (isNaN(alternativeValues[crit.id])) {
        allValuesFilledOrValid = false;
        break;
      }
    } else {
      allValuesFilledOrValid = false;
      break;
    }
  }

  if (projectData.criteria.length > 0 && !allValuesFilledOrValid) {
    showAlert(
      "Semua nilai kriteria untuk alternatif harus diisi dengan angka yang valid!",
      "danger",
      "alternatives"
    );
    return;
  }

  const newAlternative = {
    id: Date.now() + Math.random(), // Add random to decrease collision chance
    name: name,
    values: alternativeValues,
  };

  projectData.alternatives.push(newAlternative);
  resetAlternativeForm();
  updateAlternativesList();
  showAlert("Alternatif berhasil ditambahkan!", "success", "alternatives");
}

function updateAlternative(index) {
  const name = document.getElementById("alternativeName").value.trim();
  if (!name) {
    showAlert("Nama alternatif harus diisi!", "danger", "alternatives");
    return;
  }

  const alternativeValues = {};
  let allValuesFilledOrValid = true;
  for (const crit of projectData.criteria) {
    const inputElement = document.getElementById(`alt-crit-${crit.id}`);
    if (inputElement) {
      const value = inputElement.value;
      if (value === "" || value === null) {
        allValuesFilledOrValid = false;
        break;
      }
      alternativeValues[crit.id] = parseFloat(value);
      if (isNaN(alternativeValues[crit.id])) {
        allValuesFilledOrValid = false;
        break;
      }
    } else {
      allValuesFilledOrValid = false;
      break;
    }
  }

  if (projectData.criteria.length > 0 && !allValuesFilledOrValid) {
    showAlert(
      "Semua nilai kriteria untuk alternatif harus diisi dengan angka yang valid!",
      "danger",
      "alternatives"
    );
    return;
  }

  projectData.alternatives[index].name = name;
  projectData.alternatives[index].values = alternativeValues;

  resetAlternativeForm();
  updateAlternativesList();
  showAlert("Alternatif berhasil diupdate!", "success", "alternatives");
}

function clearAlternativeForm() {
  document.getElementById("alternativeName").value = "";
  renderCriteriaInputsForAlternative("alternativeCriteriaInputsContainer");
}

function resetAlternativeForm() {
  clearAlternativeForm();
  const addButton = document.getElementById("addAlternativeBtn");
  addButton.textContent = "Tambah Alternatif";
  addButton.onclick = addAlternative;
  currentEditingAlternativeIndex = null;
}

function updateAlternativesList() {
  const alternativesListContainer =
    document.getElementById("alternativesList");
  if (!alternativesListContainer) return;

  if (projectData.alternatives.length === 0) {
    alternativesListContainer.innerHTML =
      "<p>Belum ada alternatif. Tambahkan alternatif terlebih dahulu.</p>";
    return;
  }

  let html = '<div class="table-container">';
  html += '<table class="table">';
  html +=
    "<thead><tr><th>No</th><th>Alternatif</th><th>Aksi</th></tr></thead>";
  html += "<tbody>";

  projectData.alternatives.forEach((alternative, index) => {
    html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${alternative.name}</strong></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editAlternative(${index})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteAlternative(${index})">Hapus</button>
                        </td>
                    </tr>
                `;
  });

  html += "</tbody></table></div>";
  alternativesListContainer.innerHTML = html;
}

function deleteAlternative(index) {
  if (confirm("Yakin ingin menghapus alternatif ini?")) {
    projectData.alternatives.splice(index, 1);
    if (currentEditingAlternativeIndex === index) {
      resetAlternativeForm();
    }
    updateAlternativesList();
    showAlert("Alternatif berhasil dihapus!", "success", "alternatives");
  }
}

function editAlternative(index) {
  const alternative = projectData.alternatives[index];
  document.getElementById("alternativeName").value = alternative.name;

  renderCriteriaInputsForAlternative(
    "alternativeCriteriaInputsContainer",
    alternative.values
  );

  const updateButton = document.getElementById("addAlternativeBtn");
  updateButton.textContent = "Update Alternatif";
  updateButton.onclick = () => updateAlternative(index);
  currentEditingAlternativeIndex = index;

  showAlert(
    "Data alternatif dimuat untuk diedit.",
    "success",
    "alternatives"
  );
  document.getElementById("alternativeName").focus();
}

// Calculation functions
function calculateAndShowResults() {
  if (
    projectData.criteria.length === 0 ||
    projectData.alternatives.length === 0
  ) {
    showAlert(
      "Tambahkan kriteria dan alternatif (beserta nilainya) terlebih dahulu sebelum menghitung!",
      "danger",
      "results"
    );
    return;
  }
  let allAlternativesHaveValues = true;
  for (const alt of projectData.alternatives) {
    if (
      !alt.values ||
      Object.keys(alt.values).length < projectData.criteria.length
    ) {
      allAlternativesHaveValues = false;
      break;
    }
    for (const crit of projectData.criteria) {
      if (
        alt.values[crit.id] === undefined ||
        alt.values[crit.id] === "" ||
        isNaN(parseFloat(alt.values[crit.id]))
      ) {
        allAlternativesHaveValues = false;
        break;
      }
    }
    if (!allAlternativesHaveValues) break;
  }

  if (!allAlternativesHaveValues) {
    showAlert(
      'Pastikan semua alternatif memiliki nilai yang valid untuk setiap kriteria. Periksa kembali di tab "Alternatif & Nilai".',
      "danger",
      "results"
    );
    return;
  }
  calculate();
}

function calculate() {
  const normalizedMatrix = {};
  projectData.alternatives.forEach((alt) => {
    normalizedMatrix[alt.id] = {};
  });

  projectData.criteria.forEach((criteria) => {
    const valuesForCriteria = projectData.alternatives.map(
      (alt) => alt.values[criteria.id]
    );

    const numericValues = valuesForCriteria.filter(
      (v) => typeof v === "number" && !isNaN(v)
    );
    if (numericValues.length === 0) {
      projectData.alternatives.forEach((alt) => {
        normalizedMatrix[alt.id][criteria.id] = 0;
      });
      // Continue to next criteria, do not return from calculate() entirely
      // as other criteria might have valid values.
    } else {
      const maxValue = Math.max(...numericValues);
      const minValue = Math.min(...numericValues);

      projectData.alternatives.forEach((alt) => {
        const altValue = alt.values[criteria.id];
        if (typeof altValue !== "number" || isNaN(altValue)) {
          normalizedMatrix[alt.id][criteria.id] = 0;
          return; // skip this alternative for this criterion
        }

        if (criteria.type === "benefit") {
          normalizedMatrix[alt.id][criteria.id] =
            maxValue === 0 ? 0 : altValue / maxValue;
        } else {
          // cost
          if (altValue === 0) {
            normalizedMatrix[alt.id][criteria.id] =
              minValue === 0 ? 1 : Infinity; // Cost 0 is best or infinitely better if others have cost
            // To avoid Infinity, can cap it at 1 or handle as special case (e.g., always rank first if cost is 0)
            // For SAW, Infinity will break sum. Let's make it 1 if it's the min or also 0.
            if (minValue === 0 || altValue === minValue)
              normalizedMatrix[alt.id][criteria.id] = 1;
            else normalizedMatrix[alt.id][criteria.id] = 1; // If its 0 and min is not, it's still the best
          } else {
            normalizedMatrix[alt.id][criteria.id] = minValue / altValue;
          }
        }
        if (
          isNaN(normalizedMatrix[alt.id][criteria.id]) ||
          !isFinite(normalizedMatrix[alt.id][criteria.id])
        ) {
          normalizedMatrix[alt.id][criteria.id] = 0;
        }
      });
    }
  });

  projectData.results = [];
  projectData.alternatives.forEach((alt) => {
    let totalScore = 0;
    projectData.criteria.forEach((criteria) => {
      totalScore +=
        (normalizedMatrix[alt.id][criteria.id] || 0) * criteria.weight;
    });

    projectData.results.push({
      alternative: alt,
      score: totalScore,
      normalizedValues: normalizedMatrix[alt.id],
    });
  });

  projectData.results.sort((a, b) => b.score - a.score);

  showAlert(
    "Perhitungan berhasil! Hasil ditampilkan di bawah.",
    "success",
    "results"
  );
  updateResults();
}

function updateResults() {
  const resultsContainer = document.getElementById("rankingResults");
  const detailsContainer = document.getElementById("calculationDetails");

  if (!resultsContainer || !detailsContainer) return;

  if (projectData.results.length === 0) {
    resultsContainer.innerHTML =
      '<p>Belum ada hasil perhitungan. Klik tombol "Hitung & Tampilkan Hasil Perangkingan" di atas.</p>';
    detailsContainer.innerHTML = "";
    return;
  }

  let rankingHtml = '<div class="ranking-result">';
  rankingHtml += `<h2>${projectData.title || "Hasil Perangkingan"}</h2>`;

  projectData.results.forEach((result, index) => {
    rankingHtml += `
                    <div class="ranking-item">
                        <div class="ranking-position">${index + 1}</div>
                        <div>
                            <strong>${result.alternative.name}</strong>
                        </div>
                        <div>
                            <strong>Skor: ${result.score.toFixed(4)}</strong>
                        </div>
                    </div>
                `;
  });
  rankingHtml += "</div>";
  resultsContainer.innerHTML = rankingHtml;

  // --- Detail Perhitungan ---
  let detailHtml = ""; // Initialize as empty

  // Tabel 1: Matrix Keputusan (Nilai Asli Alternatif)
  detailHtml += '<div class="table-container" style="overflow-x: auto;">';
  detailHtml += "<h4>Matrix Keputusan (Nilai Asli Alternatif)</h4>";
  detailHtml += '<table class="table">';
  detailHtml += "<thead><tr><th>Alternatif</th>";
  projectData.criteria.forEach((criteria) => {
    detailHtml += `<th>${criteria.name}</th>`;
  });
  detailHtml += "</tr></thead><tbody>";
  projectData.alternatives.forEach((alt) => {
    detailHtml += `<tr><td><strong>${alt.name}</strong></td>`;
    projectData.criteria.forEach((crit) => {
      const val = alt.values[crit.id];
      let displayVal =
        typeof val === "number" && !isNaN(val)
          ? val.toString()
          : val === undefined || val === null
          ? "-"
          : val;
      if (crit.inputType === "ranges" && crit.valueRanges) {
        const range = crit.valueRanges.find((r) => r.value === val);
        if (range) displayVal = `${range.name} (${val})`;
      }
      detailHtml += `<td>${displayVal}</td>`;
    });
    detailHtml += "</tr>";
  });
  detailHtml += "</tbody></table></div>"; // End of 1st table's div

  // Tabel 2: Matrix Ternormalisasi & Perhitungan Skor
  detailHtml +=
    '<div class="table-container" style="overflow-x: auto; margin-top: 20px;">';
  detailHtml += "<h4>Matrix Ternormalisasi & Perhitungan Skor</h4>";
  detailHtml += '<table class="table">';
  detailHtml += "<thead><tr><th>Alternatif</th>";
  projectData.criteria.forEach((criteria) => {
    detailHtml += `<th>${
      criteria.name
    }<br><small>Bobot: ${criteria.weight.toFixed(4)}</small></th>`;
  });
  detailHtml += "<th>Skor Akhir</th></tr></thead><tbody>";
  projectData.results.forEach((result) => {
    detailHtml += `<tr><td><strong>${result.alternative.name}</strong></td>`;
    projectData.criteria.forEach((criteria) => {
      const normalizedValue =
        result.normalizedValues[criteria.id] === undefined
          ? 0
          : result.normalizedValues[criteria.id];
      const weightedValue = normalizedValue * criteria.weight;
      detailHtml += `<td>${normalizedValue.toFixed(
        4
      )}<br><small>(Norm x Bobot = ${weightedValue.toFixed(
        4
      )})</small></td>`;
    });
    detailHtml += `<td><strong>${result.score.toFixed(
      4
    )}</strong></td></tr>`;
  });
  html += "</tbody></table></div>"; // End of 2nd table's div

  detailsContainer.innerHTML = detailHtml; // Set the combined HTML for both tables
}

// Utility functions
function showAlert(message, type, tabName) {
  let alertContainer = document.querySelector(
    `#${tabName}.tab-pane.active`
  );
  if (!alertContainer) {
    alertContainer = document.querySelector(".tab-pane.active");
  }
  if (!alertContainer) return;

  const existingAlerts = alertContainer.querySelectorAll(".alert");
  existingAlerts.forEach((alert) => {
    if (alert.parentNode) {
      alert.parentNode.removeChild(alert);
    }
  });

  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type}`;
  alertDiv.innerHTML = message;

  const firstCard = alertContainer.querySelector(".card");
  if (firstCard) {
    alertContainer.insertBefore(alertDiv, firstCard);
  } else {
    alertContainer.insertBefore(alertDiv, alertContainer.firstChild);
  }

  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.parentNode.removeChild(alertDiv);
    }
  }, 5000);
}

function updateUI() {
  // Minimal UI updates now
}

// Initialize application when page loads
document.addEventListener("DOMContentLoaded", init);