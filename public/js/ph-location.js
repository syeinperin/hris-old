// public/js/ph-location.js
document.addEventListener('DOMContentLoaded', () => {
  const provinceSelect = document.getElementById('current_province');
  const citySelect     = document.getElementById('current_city_select');
  const cityHidden     = document.getElementById('current_city');
  const brgySelect     = document.getElementById('current_barangay_select');
  const brgyHidden     = document.getElementById('current_barangay');
  const postalInput    = document.getElementById('current_postal_code');

  if (!provinceSelect || !citySelect || !cityHidden || !brgySelect || !brgyHidden || !postalInput) {
    console.warn("ph-location.js: Some required elements are missing on this page.");
    return;
  }

  const phLocations = {
    // ── LAGUNA (sample; add others as needed) ──
    "Laguna": [
      { name: "Alaminos", zip: "4024" },
      { name: "Bay", zip: "4008" },
      { name: "Biñan", zip: "4024" },
      { name: "Cabuyao", zip: "4025" },
      { name: "Calamba", zip: "4027" },
      { name: "Calauan", zip: "4004" },
      { name: "Cavinti", zip: "4014" },
      { name: "Famy", zip: "4009" },
      { name: "Kalayaan", zip: "4026" },
      { name: "Liliw", zip: "4006" },
      { name: "Luisiana", zip: "4005" },
      { name: "Lumban", zip: "4010" },
      { name: "Mabitac", zip: "4013" },
      { name: "Magdalena", zip: "4022" },
      { name: "Majayjay", zip: "4016" },
      { name: "Nagcarlan", zip: "4002" },
      { name: "Paete", zip: "4007" },
      { name: "Pagsanjan", zip: "4016" },
      { name: "Pakil", zip: "4011" },
      { name: "Pangil", zip: "4017" },
      { name: "Pila", zip: "4005" },
      { name: "Rizal", zip: "4028" },
      { name: "San Pablo", zip: "4000" },
      { name: "San Pedro", zip: "4023" },
      { name: "Santa Cruz", zip: "4000" },
      { name: "Santa Maria", zip: "4029" },
      { name: "Santa Rosa", zip: "4026" },
      { name: "Siniloan", zip: "4018" },
      { name: "Victoria", zip: "4019" }
    ],

  
    // ── CAVITE (23 LGUs) ──────────────────────────────────────────────
    "Cavite": [
      { name: "Alfonso",        zip: "4123" },
      { name: "Amadeo",         zip: "4119" },
      { name: "Bacoor",         zip: "4102" },
      { name: "Cavite City",    zip: "4100" },
      { name: "Cavite Naval Base", zip: "4101" },
      { name: "Carmona",        zip: "4116" },
      { name: "Corregidor Island", zip: "4125" },
      { name: "Dasmariñas",     zip: "4114" },
      { name: "Dasmariñas Resettlement Area", zip: "4115" },
      { name: "General Emilio Aguinaldo (Bailen)", zip: "4124" },
      { name: "General Mariano Alvarez", zip: "4117" },
      { name: "Imus",           zip: "4103" },
      { name: "Kawit",          zip: "4104" },
      { name: "Magallanes",     zip: "4113" },
      { name: "Maragondon",     zip: "4112" },
      { name: "Mendez",         zip: "4121" },
      { name: "Molino",         zip: "4135" },
      { name: "Naic",           zip: "4110" },
      { name: "Noveleta",       zip: "4105" },
      { name: "Rosario",        zip: "4106" },
      { name: "Silang",         zip: "4118" },
      { name: "Tagaytay",       zip: "4120" },
      { name: "Tanza",          zip: "4108" },
      { name: "Ternate",        zip: "4111" },
      { name: "Trece Martires City", zip: "4109" }
    ],

    // ── BATANGAS (31 LGUs) ────────────────────────────────────────────
    "Batangas": [
      { name: "Alitagtag", zip: "4224" },
      { name: "Balayan",   zip: "4210" },
      { name: "Balete",    zip: "4216" },
      { name: "Batangas City", zip: "4200" },
      { name: "Calaca",    zip: "4225" },
      { name: "Calatagan", zip: "4212" },
      { name: "Cuenca",    zip: "4217" },
      { name: "Ibaan",     zip: "4231" },
      { name: "Laurel",    zip: "4234" },
      { name: "Lemery",    zip: "4230" },
      { name: "Lian",      zip: "4233" },
      { name: "Lobo",      zip: "4236" },
      { name: "Malvar",    zip: "4231" },
      { name: "Mataasnakahoy", zip: "4223" },
      { name: "Mabini",    zip: "4208" },
      { name: "Malilipot", zip: "4209" },
      { name: "Padre Garcia", zip: "4232" },
      { name: "Rosario",   zip: "4230" },
      { name: "San Joseph", zip: "4216" },
      { name: "San Juan",  zip: "4212" },
      { name: "San Luis",  zip: "4218" },
      { name: "San Nicolas", zip: "4215" },
      { name: "San Pascual", zip: "4211" },
      { name: "Santa Teresita", zip: "4215" },
      { name: "Santo Tomas", zip: "4232" },
      { name: "Taal",      zip: "4217" },
      { name: "Talisay",   zip: "4222" },
      { name: "Tanauan",   zip: "4250" },
      { name: "Taysan",    zip: "4241" },
      { name: "Tingloy",   zip: "4208" },
      { name: "Tuy",       zip: "4211" }
    ],

    // ── RIZAL (13 LGUs) ──────────────────────────────────────────────
    "Rizal": [
      { name: "Angono",                 zip: "1930" },
      { name: "Antipolo",               zip: "1870" },
      { name: "Baras",                  zip: "1920" },
      { name: "Binangonan",             zip: "1940" },
      { name: "Cainta",                 zip: "1900" },
      { name: "Cardona",                zip: "1910" },
      { name: "Jala-Jala",              zip: "1984" },
      { name: "Morong",                 zip: "1948" },
      { name: "Pililla",                zip: "1949" },
      { name: "Rodriguez (Montalban)",  zip: "1860" },
      { name: "San Mateo",              zip: "1850" },
      { name: "Tanay",                  zip: "1932" },
      { name: "Taytay",                 zip: "1920" },
      { name: "Teresa",                 zip: "1950" }
    ],

    // ── QUEZON (39 LGUs) ──────────────────────────────────────────────
    "Quezon": [
      { name: "Alabat",      zip: "4323" },
      { name: "Agdangan",    zip: "4324" },
      { name: "Atimonan",    zip: "4326" },
      { name: "Buenavista",  zip: "4312" },
      { name: "Burdeos",     zip: "4329" },
      { name: "Calauag",     zip: "4321" },
      { name: "Candelaria",  zip: "4314" },
      { name: "Catanauan",   zip: "4322" },
      { name: "Dolores",     zip: "4320" },
      { name: "General Luna",zip: "4302" },
      { name: "General Nakar",zip:"4328" },
      { name: "Guinayangan", zip: "4333" },
      { name: "Gumaca",      zip: "4325" },
      { name: "Infanta",     zip: "4318" },
      { name: "Jomalig",     zip: "4316" },
      { name: "Lopez",       zip: "4329" },
      { name: "Lucena",      zip: "4301" },
      { name: "Macalelon",   zip: "4315" },
      { name: "Mauban",      zip: "4327" },
      { name: "Mulanay",     zip: "4317" },
      { name: "Padre Burgos",zip: "4316" },
      { name: "Pagbilao",    zip: "4323" },
      { name: "Panukulan",   zip: "4319" },
      { name: "Patnanungan", zip: "4335" },
      { name: "Pelahinan",   zip: "4319" },
      { name: "Polillo",     zip: "4335" },
      { name: "Quezon",      zip: "4320" },
      { name: "Real",        zip: "4319" },
      { name: "Sampaloc",    zip: "4303" },
      { name: "San Andreas", zip: "4319" },
      { name: "San Antonio", zip: "4306" },
      { name: "San Francisco",zip:"4305" },
      { name: "San Narciso", zip: "4304" },
      { name: "Sariaya",     zip: "4320" },
      { name: "Tagkawayan",  zip: "4336" },
      { name: "Tayabas",     zip: "4320" },
      { name: "Tiaong",      zip: "4328" },
      { name: "Unisan",      zip: "4323" }
    ]
  };

  function resetCityAndBrgy() {
    citySelect.innerHTML = '<option value="" disabled selected>Select City / Municipality</option>';
    brgySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
    cityHidden.value = '';
    brgyHidden.value = '';
    postalInput.value = '';
  }

  // Province change → populate cities
  provinceSelect.addEventListener('change', () => {
    resetCityAndBrgy();
    const list = phLocations[provinceSelect.value] || [];
    list.forEach(({ name, zip }) => {
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name;
      opt.dataset.zip = zip;
      citySelect.appendChild(opt);
    });
  });

  // City change → update hidden + postal
  citySelect.addEventListener('change', () => {
    const sel = citySelect.selectedOptions[0];
    if (!sel) return;
    cityHidden.value = sel.value;
    postalInput.value = sel.dataset.zip || '';
  });

  // Barangay change → update hidden
  brgySelect.addEventListener('change', () => {
    const sel = brgySelect.selectedOptions[0];
    if (!sel) return;
    brgyHidden.value = sel.value;
  });

  // 🔄 Auto-restore values (useful for edit form)
  (function restoreExisting() {
    const prov = provinceSelect.value;
    const city = cityHidden.value;
    const brgy = brgyHidden.value;

    if (!prov) return;

    const list = phLocations[prov] || [];
    if (!list.length) return;

    // Populate city dropdown
    list.forEach(({ name, zip }) => {
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name;
      opt.dataset.zip = zip;
      if (name === city) opt.selected = true;
      citySelect.appendChild(opt);
    });

    // Restore postal code
    const foundCity = list.find(c => c.name === city);
    if (foundCity) postalInput.value = foundCity.zip;

    // Restore barangay (if you maintain a barangay list)
    if (brgy) {
      const opt = document.createElement('option');
      opt.value = brgy;
      opt.textContent = brgy;
      opt.selected = true;
      brgySelect.appendChild(opt);
    }
  })();
});
