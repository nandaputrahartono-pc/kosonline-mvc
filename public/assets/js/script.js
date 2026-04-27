/* =========================================
   1. NAVBAR SCROLL EFFECT (Agar header berubah saat discroll)
   ========================================= */
const header = document.querySelector(".header");

window.addEventListener("scroll", function () {
  if (window.scrollY > 0) {
    header.classList.add("sticky");
  } else {
    header.classList.remove("sticky");
  }
});

/* =========================================
   2. SLIDER REKOMENDASI (Hanya jalan jika ada elemen slider)
   ========================================= */
const slider = document.getElementById("slider");

function slideLeft() {
  if (slider) slider.scrollLeft -= 320;
}

function slideRight() {
  if (slider) slider.scrollLeft += 320;
}

/* =========================================
   3. HAMBURGER MENU (MOBILE) - FIX UTAMA
   ========================================= */
const menuBtn = document.getElementById("mobile-menu");
const navList = document.querySelector(".nav-menu"); // Pastikan di HTML class-nya 'nav-menu'

// Cek dulu apakah elemennya ada (Defensive Programming)
if (menuBtn && navList) {
  menuBtn.addEventListener("click", () => {
    // Toggle class 'active' untuk memunculkan menu dari kanan
    navList.classList.toggle("active");

    // Toggle class 'is-active' untuk animasi ikon X
    menuBtn.classList.toggle("is-active");
  });
}

// Fitur tambahan: Tutup menu otomatis saat salah satu link diklik
const navLinks = document.querySelectorAll(".nav-menu li a");
navLinks.forEach((link) => {
  link.addEventListener("click", () => {
    if (navList) navList.classList.remove("active");
    if (menuBtn) menuBtn.classList.remove("is-active");
  });
});

/* =========================================
   4. FOOTER TAHUN OTOMATIS
   ========================================= */
const yearEl = document.getElementById("year");
if (yearEl) {
  yearEl.textContent = new Date().getFullYear();
}
