const internLevel = document.getElementById("internLevel");
const courseWrap = document.getElementById("courseWrap");
const courseSelect = document.getElementById("courseSelect");
const courseCustom = document.getElementById("courseCustom");
const zoomToggleBtn = document.getElementById("zoomToggleBtn");
const bodyEl = document.body;
const certificateSheet = document.getElementById("certificateSheet");
const downloadPdfBtn = document.getElementById("downloadPdfBtn");
const schoolSelect = document.getElementById("schoolSelect");
const schoolCustom = document.getElementById("schoolCustom");
const departmentSelect = document.getElementById("departmentSelect");
const departmentCustom = document.getElementById("departmentCustom");
const hoursSelect = document.getElementById("hoursSelect");
const hoursCustom = document.getElementById("hoursCustom");
const siteFooter = document.querySelector(".site-footer");

function syncCourseVisibility() {
  const isCollege = internLevel && internLevel.value === "college";
  if (!courseWrap) return;

  courseWrap.classList.toggle("d-none", !isCollege);
  if (courseSelect) {
    courseSelect.required = isCollege;
  }

  if (!isCollege && courseSelect) {
    courseSelect.value = "";
    if (courseCustom) {
      courseCustom.classList.add("d-none");
      courseCustom.required = false;
      courseCustom.value = "";
    }
  } else if (isCollege && courseSelect && courseCustom) {
    syncCustomField(courseSelect, courseCustom);
  }
}

if (internLevel) {
  internLevel.addEventListener("change", syncCourseVisibility);
  syncCourseVisibility();
}

function syncCustomField(selectEl, inputEl) {
  if (!selectEl || !inputEl) return;
  const isCustom = selectEl.value === "__custom__";
  inputEl.classList.toggle("d-none", !isCustom);
  inputEl.required = isCustom;
  if (!isCustom) inputEl.value = "";
}

if (schoolSelect) {
  schoolSelect.addEventListener("change", () => syncCustomField(schoolSelect, schoolCustom));
  syncCustomField(schoolSelect, schoolCustom);
}

if (departmentSelect) {
  departmentSelect.addEventListener("change", () => syncCustomField(departmentSelect, departmentCustom));
  syncCustomField(departmentSelect, departmentCustom);
}

if (hoursSelect) {
  hoursSelect.addEventListener("change", () => syncCustomField(hoursSelect, hoursCustom));
  syncCustomField(hoursSelect, hoursCustom);
}

if (courseSelect) {
  courseSelect.addEventListener("change", () => syncCustomField(courseSelect, courseCustom));
  syncCustomField(courseSelect, courseCustom);
}

function setZoomState(isZoomed) {
  bodyEl.classList.toggle("zoomed-ui", isZoomed);
  if (zoomToggleBtn) {
    zoomToggleBtn.innerHTML = isZoomed
      ? '<i class="bi bi-zoom-out me-1"></i>Normal UI'
      : '<i class="bi bi-zoom-in me-1"></i>Zoom UI';
  }
  localStorage.setItem("hrmoZoomedUi", isZoomed ? "1" : "0");
}

if (zoomToggleBtn) {
  zoomToggleBtn.addEventListener("click", () => {
    const isZoomed = !bodyEl.classList.contains("zoomed-ui");
    setZoomState(isZoomed);
  });
}

setZoomState(localStorage.getItem("hrmoZoomedUi") === "1");

document.querySelectorAll(".auto-dismiss").forEach((el) => {
  setTimeout(() => {
    el.style.transition = "opacity 0.4s ease, transform 0.4s ease";
    el.style.opacity = "0";
    el.style.transform = "translateY(-10px)";
    setTimeout(() => el.remove(), 450);
  }, 4000);
});

function downloadCertificatePdf() {
  if (!certificateSheet || typeof html2pdf === "undefined") return;
  const fileName = bodyEl.dataset.pdfName || "certificate";
  const options = {
    margin: [0, 0, 0, 0],
    filename: `${fileName}.pdf`,
    image: { type: "jpeg", quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
    pagebreak: { mode: ["css", "legacy"] },
  };
  return html2pdf().set(options).from(certificateSheet).save();
}

if (downloadPdfBtn) {
  downloadPdfBtn.addEventListener("click", downloadCertificatePdf);
}

if (bodyEl.dataset.autoPdf === "1") {
  setTimeout(() => {
    const run = downloadCertificatePdf();
    if (run && typeof run.then === "function") {
      run.then(() => {
        window.location.href = "index.php";
      });
    } else {
      setTimeout(() => {
        window.location.href = "index.php";
      }, 1200);
    }
  }, 500);
}

function syncFooterVisibility() {
  if (!siteFooter) return;
  const docHeight = Math.max(
    document.body.scrollHeight,
    document.documentElement.scrollHeight,
    document.body.offsetHeight,
    document.documentElement.offsetHeight
  );
  const reachedBottom = window.scrollY + window.innerHeight >= docHeight - 2;
  siteFooter.classList.toggle("footer-visible", reachedBottom);
}

window.addEventListener("scroll", syncFooterVisibility, { passive: true });
window.addEventListener("resize", syncFooterVisibility);
syncFooterVisibility();
