<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Rekapitulasi</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2a33; }
        h1 { font-size: 15px; margin: 0; }
        h2 { font-size: 12px; margin: 18px 0 6px; }
        .sub { margin: 3px 0 0; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #b8c0c7; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #e3e7f0; }
        .angka { text-align: right; }
        .jumlah td { font-weight: bold; background: #f3f5f7; }
        .ringkas td { border: none; padding: 2px 14px 2px 0; width: 1%; white-space: nowrap; }
        .kecil { font-size: 9px; color: #555; margin-top: 14px; }
    </style>
</head>
<body>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="border: none; padding: 0; width: 170px; vertical-align: middle;">
                <img src="{{ public_path('images/logo-bbpjn.png') }}" alt="BBPJN Sumatera Selatan" style="height: 44px;">
            </td>
            <td style="border: none; padding: 0; vertical-align: middle;">
                <h1>Laporan Rekapitulasi Bank Data Terpusat</h1>
                <p class="sub">Balai Besar Pelaksanaan Jalan Nasional Sumatera Selatan</p>
            </td>
        </tr>
    </table>
    <p class="sub">Periode: {{ $periode }} &nbsp;|&nbsp; Kategori: {{ $kategori }}</p>

    <table class="ringkas" style="margin-top: 10px; width: auto;">
        <tr>
            <td>Total dokumen: <strong>{{ $ringkasan['total'] }}</strong></td>
            <td>Total pengaduan: <strong>{{ $ringkasan['pengaduan'] }}</strong></td>
            <td>Menunggu: <strong>{{ $ringkasan['menunggu'] }}</strong></td>
            <td>Selesai: <strong>{{ $ringkasan['selesai'] }}</strong></td>
        </tr>
    </table>

    <h2>Rekap per layanan</h2>
    <table>
        <thead>
            <tr>
                <th>Layanan</th><th>Jenis</th>
                <th class="angka">Total</th><th class="angka">Menunggu</th><th class="angka">Selesai</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rekap as $l)
                <tr>
                    <td>{{ $l->nama }}</td>
                    <td>{{ $l->is_pengaduan ? 'Pengaduan' : 'Arsip' }}</td>
                    <td class="angka">{{ $l->total }}</td>
                    <td class="angka">{{ $l->butuh_tindak_lanjut ? $l->menunggu : '-' }}</td>
                    <td class="angka">{{ $l->butuh_tindak_lanjut ? $l->selesai : '-' }}</td>
                </tr>
            @endforeach
            <tr class="jumlah">
                <td colspan="2">Jumlah</td>
                <td class="angka">{{ $ringkasan['total'] }}</td>
                <td class="angka">{{ $ringkasan['menunggu'] }}</td>
                <td class="angka">{{ $ringkasan['selesai'] }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Rincian dokumen ({{ $dokumen->count() }})</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 4%">No</th><th style="width: 9%">Tanggal</th><th style="width: 16%">Nomor surat</th>
                <th>Judul</th><th style="width: 18%">Layanan</th><th style="width: 9%">Status</th><th style="width: 13%">Diunggah oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dokumen as $i => $d)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $d->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $d->nomor_surat ?: '-' }}</td>
                    <td>{{ $d->judul }}</td>
                    <td>{{ $d->layanan->nama }}</td>
                    <td>{{ $d->labelStatus() }}</td>
                    <td>{{ $d->user->nama }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada dokumen pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="kecil">Dicetak oleh {{ $dicetakOleh }} pada {{ $dicetakPada->format('d/m/Y H:i') }}.</p>
</body>
</html>
