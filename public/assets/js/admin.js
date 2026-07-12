document.addEventListener("DOMContentLoaded", function () {
  // --- A. LOGIC SIDEBAR MOBILE ---
  const sidebar = document.querySelector(".sidebar");
  const toggleBtn = document.getElementById("sidebar-toggle");
  const content = document.querySelector(".content");

  if (toggleBtn) {
    toggleBtn.addEventListener("click", function (e) {
      // Mencegah klik tombol dianggap sebagai klik content (PENTING!)
      e.stopPropagation();
      sidebar.classList.toggle("active");
    });
  }

  // Tutup sidebar jika klik di luar area sidebar (Content)
  content.addEventListener("click", function () {
    if (window.innerWidth <= 768 && sidebar.classList.contains("active")) {
      sidebar.classList.remove("active");
    }
  });

  // --- B. LOGIC PINDAH HALAMAN (TAB SWITCHING) ---
  const menuLinks = document.querySelectorAll(".sidebar .menu a[data-page]");
  const pageLinks = document.querySelectorAll("a[data-page], button[data-page]");
  const pages = document.querySelectorAll(".page");

  function showPage(targetPageId) {
    pages.forEach((page) => {
      page.classList.toggle("active", page.id === targetPageId);
    });

    menuLinks.forEach((item) => {
      item.classList.toggle("active", item.getAttribute("data-page") === targetPageId);
    });
  }

  pageLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();

      const targetPageId = this.getAttribute("data-page");
      showPage(targetPageId);

      // 2. Tutup sidebar otomatis di HP setelah klik menu
      if (window.innerWidth <= 768) {
        sidebar.classList.remove("active");
      }
    });
  });

  // --- C. LOGIC LOGOUT ---
  // Konfirmasi logout ditangani confirm-modal.js lewat atribut data-confirm pada form.

  // --- E. REVENUE CHART ---
  const revenueCanvas = document.getElementById("revenueChart");
  if (revenueCanvas && window.adminRevenueChart) {
    const labels = window.adminRevenueChart.labels || [];
    const values = (window.adminRevenueChart.values || []).map((value) => Number(value || 0));

    function formatShortCurrency(value) {
      if (value >= 1000000) return "Rp " + (value / 1000000).toFixed(value % 1000000 === 0 ? 0 : 1) + " jt";
      if (value >= 1000) return "Rp " + Math.round(value / 1000) + " rb";
      return "Rp " + value.toLocaleString("id-ID");
    }

    function drawRevenueChart() {
      const context = revenueCanvas.getContext("2d");
      const parent = revenueCanvas.parentElement;
      const cssWidth = Math.max(320, parent.clientWidth || revenueCanvas.clientWidth || 600);
      const cssHeight = Math.max(220, parent.clientHeight || 280);
      const ratio = window.devicePixelRatio || 1;

      revenueCanvas.width = Math.floor(cssWidth * ratio);
      revenueCanvas.height = Math.floor(cssHeight * ratio);
      revenueCanvas.style.width = cssWidth + "px";
      revenueCanvas.style.height = cssHeight + "px";

      context.setTransform(ratio, 0, 0, ratio, 0, 0);
      context.clearRect(0, 0, cssWidth, cssHeight);
      const gridColor = "rgba(148, 163, 184, 0.2)";
      const labelColor = "#64748b";
      const lineColor = "#2563eb";
      const pointStroke = "#ffffff";

      const padding = { top: 20, right: 46, bottom: 42, left: 70 };
      const chartWidth = cssWidth - padding.left - padding.right;
      const chartHeight = cssHeight - padding.top - padding.bottom;
      const maxValue = Math.max(...values, 1000000);
      const stepCount = 4;

      context.font = "700 11px Segoe UI, sans-serif";
      context.textBaseline = "middle";
      context.strokeStyle = gridColor;
      context.fillStyle = labelColor;
      context.lineWidth = 1;

      for (let i = 0; i <= stepCount; i++) {
        const y = padding.top + chartHeight - (chartHeight / stepCount) * i;
        const value = (maxValue / stepCount) * i;
        context.beginPath();
        context.moveTo(padding.left, y);
        context.lineTo(cssWidth - padding.right, y);
        context.stroke();
        context.fillText(formatShortCurrency(value), 0, y);
      }

      const points = values.map((value, index) => {
        const x = padding.left + (labels.length <= 1 ? 0 : (chartWidth / (labels.length - 1)) * index);
        const y = padding.top + chartHeight - (value / maxValue) * chartHeight;
        return { x, y, value };
      });

      if (points.length === 0) return;

      const fillGradient = context.createLinearGradient(0, padding.top, 0, padding.top + chartHeight);
      fillGradient.addColorStop(0, "rgba(37, 99, 235, 0.22)");
      fillGradient.addColorStop(1, "rgba(37, 99, 235, 0.02)");

      context.beginPath();
      context.moveTo(points[0].x, padding.top + chartHeight);
      points.forEach((point, index) => {
        if (index === 0) context.lineTo(point.x, point.y);
        else context.lineTo(point.x, point.y);
      });
      context.lineTo(points[points.length - 1].x, padding.top + chartHeight);
      context.closePath();
      context.fillStyle = fillGradient;
      context.fill();

      context.beginPath();
      points.forEach((point, index) => {
        if (index === 0) context.moveTo(point.x, point.y);
        else context.lineTo(point.x, point.y);
      });
      context.strokeStyle = lineColor;
      context.lineWidth = 3;
      context.lineJoin = "round";
      context.lineCap = "round";
      context.stroke();

      points.forEach((point, index) => {
        context.beginPath();
        context.arc(point.x, point.y, 4, 0, Math.PI * 2);
        context.fillStyle = lineColor;
        context.fill();
        context.lineWidth = 2;
        context.strokeStyle = pointStroke;
        context.stroke();

        context.fillStyle = labelColor;
        context.textAlign = "center";
        context.fillText(labels[index] || "", point.x, cssHeight - 18);
      });
    }

    drawRevenueChart();
    window.addEventListener("resize", drawRevenueChart);
  }
});
