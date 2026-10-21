<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\Layanan;
use App\Services\ActivityLogger;
use App\Services\BerkasService;
use App\Support\PesanValidasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenController extends Controller
{
    public function __construct(private BerkasService $berkasService)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Dokumen::class);

        $user = $request->user();
        $layananList = Layanan::untukUser($user)->orderBy('nama')->get();

        $layanan = null;
        if ($request->filled('layanan')) {
            $layanan = $layananList->firstWhere('kode', $request->query('layanan'));
            abort_if($layanan === null, 404);
        }

        $dasar = Dokumen::dapatDilihat($user)
            ->when($layanan, fn ($q) => $q->where('layanan_id', $layanan->id));

        $hitung = [
            'semua'    => (clone $dasar)->count(),
            'menunggu' => (clone $dasar)->where('status', Dokumen::STATUS_MENUNGGU)->count(),
            'selesai'  => (clone $dasar)->where('status', Dokumen::STATUS_SELESAI)->count(),
        ];

        $status = in_array($request->query('status'), [Dokumen::STATUS_MENUNGGU, Dokumen::STATUS_SELESAI], true)
            ? $request->query('status')
            : null;

        $dokumen = (clone $dasar)
            ->with(['layanan', 'user'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('dokumen.index', compact('dokumen', 'layanan', 'status', 'hitung'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Dokumen::class);

        $layananList = Layanan::untukUser($request->user())->orderBy('nama')->get();
        $terpilih = $layananList->firstWhere('kode', $request->query('layanan'))?->id;

        return view('dokumen.create', compact('layananList', 'terpilih'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Dokumen::class);

        $data = $this->validasi($request);
        $layanan = Layanan::findOrFail($data['layanan_id']);

        $path = $request->hasFile('berkas')
            ? $this->berkasService->simpan($request->file('berkas'))
            : null;

        $dokumen = Dokumen::create([
            'user_id'     => $request->user()->id,
            'layanan_id'  => $layanan->id,
            'nomor_surat' => $data['nomor_surat'] ?? null,
            'judul'       => $data['judul'],
            'tanggal'     => $data['tanggal'],
            'file_path'   => $path,
            'status'      => $layanan->butuh_tindak_lanjut ? Dokumen::STATUS_MENUNGGU : null,
        ]);

        ActivityLogger::catat('dokumen.tambah', $this->ringkas($dokumen), $dokumen->id);

        return redirect()->route('dokumen.show', $dokumen)->with('status', 'Dokumen berhasil ditambahkan.');
    }

    public function show(Dokumen $dokumen): View
    {
        Gate::authorize('view', $dokumen);

        $dokumen->load(['layanan', 'user']);

        return view('dokumen.show', [
            'dokumen'    => $dokumen,
            'adaBerkas'  => $this->berkasService->ada($dokumen->file_path),
        ]);
    }

    public function edit(Request $request, Dokumen $dokumen): View
    {
        Gate::authorize('update', $dokumen);

        $layananList = Layanan::untukUser($request->user())->orderBy('nama')->get();

        return view('dokumen.edit', [
            'dokumen'     => $dokumen,
            'layananList' => $layananList,
            'adaBerkas'   => $this->berkasService->ada($dokumen->file_path),
        ]);
    }

    public function update(Request $request, Dokumen $dokumen): RedirectResponse
    {
        Gate::authorize('update', $dokumen);

        $data = $this->validasi($request);
        $layanan = Layanan::findOrFail($data['layanan_id']);

        // Jika layanan berpindah, sesuaikan status dengan aturan layanan baru.
        $status = $dokumen->status;
        if ($layanan->id !== $dokumen->layanan_id) {
            $status = $layanan->butuh_tindak_lanjut ? ($status ?? Dokumen::STATUS_MENUNGGU) : null;
        }

        $pathLama = $dokumen->file_path;
        $pathBaru = $request->hasFile('berkas')
            ? $this->berkasService->simpan($request->file('berkas'))
            : $pathLama;

        $dokumen->update([
            'layanan_id'  => $layanan->id,
            'nomor_surat' => $data['nomor_surat'] ?? null,
            'judul'       => $data['judul'],
            'tanggal'     => $data['tanggal'],
            'file_path'   => $pathBaru,
            'status'      => $status,
        ]);

        if ($pathBaru !== $pathLama) {
            $this->berkasService->hapus($pathLama);
            ActivityLogger::catat('dokumen.ganti_berkas', $this->ringkas($dokumen), $dokumen->id);
        }

        ActivityLogger::catat('dokumen.ubah', $this->ringkas($dokumen), $dokumen->id);

        return redirect()->route('dokumen.show', $dokumen)->with('status', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(Dokumen $dokumen): RedirectResponse
    {
        Gate::authorize('delete', $dokumen);

        // Soft delete: baris dan berkas fisik tetap tersimpan agar log tetap punya rujukan.
        $dokumen->delete();
        ActivityLogger::catat('dokumen.hapus', $this->ringkas($dokumen), $dokumen->id);

        return redirect()->route('dokumen.index')->with('status', 'Dokumen berhasil dihapus.');
    }

    public function berkas(Request $request, Dokumen $dokumen): StreamedResponse
    {
        Gate::authorize('view', $dokumen);
        abort_unless($this->berkasService->ada($dokumen->file_path), 404);

        $nama = Str::slug($dokumen->nomor_surat ?: $dokumen->judul) ?: 'dokumen-' . $dokumen->id;
        $nama .= '.' . pathinfo($dokumen->file_path, PATHINFO_EXTENSION);

        if ($request->boolean('unduh')) {
            ActivityLogger::catat('dokumen.unduh', $this->ringkas($dokumen), $dokumen->id);

            return $this->berkasService->unduh($dokumen->file_path, $nama);
        }

        return $this->berkasService->tampilkan($dokumen->file_path, $nama);
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'layanan_id'  => ['required', Rule::exists('layanan', 'id')->where('role_id', $request->user()->role_id)],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'judul'       => ['required', 'string', 'max:255'],
            'tanggal'     => ['required', 'date'],
            'berkas'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], PesanValidasi::pesan(), PesanValidasi::atribut());
    }

    private function ringkas(Dokumen $dokumen): string
    {
        return trim(($dokumen->nomor_surat ? $dokumen->nomor_surat . ' - ' : '') . $dokumen->judul);
    }
}
