// Hitung mundur realtime untuk jatuh tempo.
// Pakai: <span data-countdown data-deadline="2026-07-21T00:00:00"></span>
//
// deadline HARUS awal hari jatuh tempo (T00:00:00), bukan akhir hari (T23:59:59).
// Kalau dipakai akhir hari, sisa harinya kelebihan satu (15 Jul -> 15 Agu terbaca
// "32 hari" padahal 31), dan label "Terlambat" jadi telat sehari dari backend —
// yang menghitung menunggak begitu jatuh_tempo < CURDATE(), yakni sehari SESUDAH
// tanggal jatuh tempo.
//
// Diperbarui tiap detik. Pada hari-H -> "Jatuh tempo hari ini"; sesudahnya ->
// "Terlambat X hari" + class .is-overdue.
(function () {
  function pad(n) {
    return n < 10 ? '0' + n : String(n);
  }

  function init() {
    var nodes = document.querySelectorAll('[data-countdown][data-deadline]');
    if (nodes.length === 0) {
      return;
    }

    var targets = [];
    for (var i = 0; i < nodes.length; i++) {
      var t = Date.parse(nodes[i].getAttribute('data-deadline'));
      targets.push({ el: nodes[i], time: isNaN(t) ? null : t });
    }

    function render() {
      var now = Date.now();
      for (var j = 0; j < targets.length; j++) {
        var item = targets[j];
        if (item.time === null) {
          item.el.textContent = '-';
          continue;
        }

        var diff = item.time - now;
        if (diff <= 0) {
          var lateDays = Math.floor((now - item.time) / 86400000);
          item.el.textContent = lateDays > 0 ? 'Terlambat ' + lateDays + ' hari' : 'Jatuh tempo hari ini';
          item.el.classList.add('is-overdue');
          continue;
        }

        item.el.classList.remove('is-overdue');
        var totalSeconds = Math.floor(diff / 1000);
        var days = Math.floor(totalSeconds / 86400);
        var hours = Math.floor((totalSeconds % 86400) / 3600);
        var mins = Math.floor((totalSeconds % 3600) / 60);
        var secs = totalSeconds % 60;

        var out = '';
        if (days > 0) {
          out += days + ' hari ';
        }
        out += pad(hours) + ' jam ' + pad(mins) + ' menit ' + pad(secs) + ' detik';
        item.el.textContent = out;
      }
    }

    render();
    setInterval(render, 1000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
