import './bootstrap';
import Chart from 'chart.js/auto';

Chart.defaults.font.family = "'IBM Plex Sans', system-ui, sans-serif";
Chart.defaults.color = '#1f2a33';

// Grafik dibuat dari konfigurasi JSON di atribut data-config pada elemen <canvas>.
document.querySelectorAll('canvas[data-config]').forEach((el) => {
    new Chart(el, JSON.parse(el.dataset.config));
});
