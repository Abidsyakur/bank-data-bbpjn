// Uji beban dengan k6 (https://k6.io). Contoh:
//   k6 run tests/Performance/k6-beban.js
//   k6 run -e BASE_URL=http://localhost/bank-data/public -e VUS=20 tests/Performance/k6-beban.js
//
// Catatan penting:
// - Jalankan terhadap Apache (XAMPP), bukan "php artisan serve": server bawaan PHP hanya memproses satu
//   permintaan pada satu waktu sehingga hasilnya tidak mewakili beban nyata.
// - Isi data besar lebih dulu: php artisan db:seed --class=PerformaSeeder
// - Login dibatasi 5 kali per menit per alamat IP, jadi login dilakukan SEKALI di setup() dan sesinya dipakai bersama.
import http from 'k6/http';
import { check, group, sleep } from 'k6';

const BASE = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const USER = __ENV.APP_USER || 'pimpinan';
const PASS = __ENV.APP_PASS || 'password';
const VUS = parseInt(__ENV.VUS || '10');

export const options = {
  stages: [
    { duration: '30s', target: VUS },
    { duration: '1m', target: VUS },
    { duration: '15s', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<1000'],
    'http_req_duration{halaman:dashboard}': ['p(95)<1500'],
    'http_req_duration{halaman:daftar}': ['p(95)<1000'],
    'http_req_duration{halaman:cari}': ['p(95)<1200'],
    'http_req_duration{halaman:riwayat}': ['p(95)<1200'],
    'http_req_duration{halaman:laporan}': ['p(95)<2000'],
  },
};

export function setup() {
  const halaman = http.get(`${BASE}/login`);
  const token = halaman.html().find('input[name=_token]').attr('value');
  const masuk = http.post(`${BASE}/login`, { _token: token, username: USER, password: PASS });

  check(masuk, { 'login berhasil': (r) => r.status === 200 && r.url.includes('/dashboard') });

  return { cookies: http.cookieJar().cookiesForURL(BASE) };
}

export default function (data) {
  const jar = http.cookieJar();
  for (const [nama, nilai] of Object.entries(data.cookies)) {
    jar.set(BASE, nama, nilai[0]);
  }

  const kata = ['Dokumen', 'contoh', '00012', 'PERF'][Math.floor(Math.random() * 4)];
  const halamanAcak = 1 + Math.floor(Math.random() * 20);
  const dipilih = Math.random();

  if (dipilih < 0.25) {
    group('dashboard', () => {
      const r = http.get(`${BASE}/dashboard`, { tags: { halaman: 'dashboard' } });
      check(r, { 'dashboard 200': (x) => x.status === 200 });
    });
  } else if (dipilih < 0.55) {
    group('daftar dokumen', () => {
      const r = http.get(`${BASE}/dokumen?page=${halamanAcak}`, { tags: { halaman: 'daftar' } });
      check(r, { 'daftar 200': (x) => x.status === 200 });
    });
  } else if (dipilih < 0.8) {
    group('pencarian', () => {
      const r = http.get(`${BASE}/dokumen?q=${encodeURIComponent(kata)}`, { tags: { halaman: 'cari' } });
      check(r, { 'pencarian 200': (x) => x.status === 200 });
    });
  } else if (dipilih < 0.92) {
    group('riwayat', () => {
      const r = http.get(`${BASE}/riwayat?page=${halamanAcak}`, { tags: { halaman: 'riwayat' } });
      check(r, { 'riwayat 200': (x) => x.status === 200 });
    });
  } else {
    group('laporan', () => {
      const r = http.get(`${BASE}/laporan?dari=&sampai=`, { tags: { halaman: 'laporan' } });
      check(r, { 'laporan 200': (x) => x.status === 200 });
    });
  }

  sleep(1 + Math.random());
}
