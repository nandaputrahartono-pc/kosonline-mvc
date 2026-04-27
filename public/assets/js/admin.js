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
  const pages = document.querySelectorAll(".page");

  menuLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();

      // 1. Update status menu aktif
      menuLinks.forEach((item) => item.classList.remove("active"));
      this.classList.add("active");

      // 2. Tutup sidebar otomatis di HP setelah klik menu
      if (window.innerWidth <= 768) {
        sidebar.classList.remove("active");
      }

      // 3. Ganti Halaman
      const targetPageId = this.getAttribute("data-page");
      pages.forEach((page) => {
        page.classList.remove("active");
        if (page.id === targetPageId) {
          page.classList.add("active");
        }
      });
    });
  });

  // --- C. LOGIC LOGOUT ---
  const logoutBtn = document.querySelector(".logout a");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      if (!confirm("Apakah Anda yakin ingin logout?")) {
        e.preventDefault();
      }
    });
  }
});
