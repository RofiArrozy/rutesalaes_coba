<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckInVisitRequest;
use App\Http\Requests\ScheduleVisitRequest;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VisitController extends Controller
{
    /**
     * Tampilkan daftar rute kunjungan sales dengan otorisasi data-level (RBAC).
     * - Admin: Dapat melihat seluruh rute kunjungan semua sales.
     * - Sales: Hanya dapat melihat daftar rute kunjungan yang ditugaskan khusus pada ID mereka.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Visit::with(['user', 'store']);

        // Data-Level Authorization: Sales hanya dapat melihat rute kunjungan miliknya sendiri
        if ($user && $user->isSales()) {
            $query->where('user_id', $user->id);
        } elseif ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($resultStatus = $request->input('result_status')) {
            $query->where('result_status', $resultStatus);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('visit_date', $date);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('store', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%");
            });
        }

        $visits = $query->orderBy('visit_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $salesUsers = User::where('role', 'sales')->where('is_active', true)->orderBy('name')->get();
        $todayDate = Carbon::today()->format('Y-m-d');

        return view('rute.index', compact('visits', 'salesUsers', 'todayDate'));
    }

    /**
     * Tampilkan formulir penjadwalan rute kunjungan baru.
     * Hanya Admin yang berhak menjadwalkan rute baru.
     */
    public function create()
    {
        Gate::authorize('create', Visit::class);

        $salesUsers = User::where('role', 'sales')->where('is_active', true)->orderBy('name')->get();
        $stores = Store::where('status', 'active')->orderBy('name')->get();

        return view('rute.create', compact('salesUsers', 'stores'));
    }

    /**
     * Simpan jadwal rute baru ke database (Admin Only).
     */
    public function store(ScheduleVisitRequest $request)
    {
        Gate::authorize('create', Visit::class);

        $visit = Visit::create([
            'user_id' => $request->user_id,
            'store_id' => $request->store_id,
            'visit_date' => $request->visit_date,
            'status' => 'Direncanakan',
            'notes' => $request->notes,
            'order_value' => 0.00,
        ]);

        return redirect()->route('rute.detail', $visit->id)
            ->with('success', "Rute kunjungan ke '{$visit->store->name}' berhasil dijadwalkan.");
    }

    /**
     * Halaman Dinamis: /rute/detail/{id}
     * Diperiksa menggunakan Laravel Policy untuk memastikan Sales hanya dapat mengakses
     * data kunjungan miliknya sendiri (Data-level Authorization).
     */
    public function detail($id)
    {
        $visit = Visit::with(['user', 'store'])->findOrFail($id);

        // Otorisasi melalui Policy: Sales hanya boleh melihat kunjungan miliknya
        Gate::authorize('view', $visit);

        // Ambil riwayat kunjungan sebelumnya pada toko ini
        $previousVisits = Visit::with('user')
            ->where('store_id', $visit->store_id)
            ->where('id', '!=', $visit->id)
            ->orderBy('visit_date', 'desc')
            ->limit(5)
            ->get();

        return view('rute.detail', compact('visit', 'previousVisits'));
    }

    /**
     * Proses transaksi Formulir Check-In Kunjungan.
     * Menerapkan Aturan Imutabilitas:
     * - Setelah status berubah menjadi 'Selesai', formulir dikunci (read-only) untuk Sales.
     * - Perubahan data terkunci hanya dapat dilakukan oleh Admin dengan catatan log perubahan.
     * - Waktu kunjungan diisi secara otomatis oleh server timestamp.
     */
    public function checkin(CheckInVisitRequest $request, $id)
    {
        $visit = Visit::with('store')->findOrFail($id);
        $user = auth()->user();

        // Cek otorisasi Policy
        Gate::authorize('checkin', $visit);

        // Aturan Imutabilitas & Kunci Data
        $isAlreadyCompleted = ($visit->status === 'Selesai');
        if ($isAlreadyCompleted && $user && $user->isSales()) {
            abort(403, 'Akses Ditolak: Kunjungan ini telah selesai dan dikunci (read-only). Data tidak dapat diubah kembali oleh Sales.');
        }

        $resultStatus = $request->input('result_status', $request->input('status'));
        $orderValue = ($resultStatus === 'Berhasil')
            ? (float) $request->input('order_value', $request->input('nilai_pesanan', 0))
            : 0.00;

        $failReason = ($resultStatus !== 'Berhasil')
            ? $request->input('fail_reason', $request->input('catatan'))
            : null;

        // Waktu kunjungan diisi secara otomatis oleh sistem (timestamp server) saat formulir berhasil dikirim
        $serverTimestamp = Carbon::now();

        $notes = $request->input('notes', $request->input('catatan'));

        // Riwayat log jika perubahan dilakukan oleh Admin pada kunjungan yang sudah terkunci
        if ($isAlreadyCompleted && $user && $user->isAdmin()) {
            $adminLog = "\n[Log Perubahan Admin: " . $user->name . " pada " . $serverTimestamp->format('d/m/Y H:i:s') . "]";
            $notes = $notes ? ($notes . $adminLog) : $adminLog;
        }

        // Update kunjungan
        $visit->update([
            'status' => 'Selesai',
            'result_status' => $resultStatus,
            'check_in_time' => $serverTimestamp,
            'order_value' => $orderValue,
            'fail_reason' => $failReason,
            'notes' => $notes,
            'simulated_latitude' => $request->input('simulated_latitude'),
            'simulated_longitude' => $request->input('simulated_longitude'),
        ]);

        $message = "Check-in kunjungan ke '{$visit->store->name}' berhasil dicatat dengan status {$resultStatus}.";
        if ($resultStatus === 'Berhasil') {
            $message .= ' Nilai pesanan: Rp ' . number_format($orderValue, 0, ',', '.');
        }

        return redirect()->route('rute.detail', $visit->id)->with('success', $message);
    }

    /**
     * Tampilkan formulir edit rute kunjungan.
     */
    public function edit($id)
    {
        $visit = Visit::with(['user', 'store'])->findOrFail($id);

        Gate::authorize('update', $visit);

        $salesUsers = User::where('role', 'sales')->where('is_active', true)->orderBy('name')->get();
        $stores = Store::orderBy('name')->get();

        return view('rute.edit', compact('visit', 'salesUsers', 'stores'));
    }

    /**
     * Perbarui jadwal rute kunjungan.
     */
    public function update(ScheduleVisitRequest $request, $id)
    {
        $visit = Visit::findOrFail($id);

        Gate::authorize('update', $visit);

        $visit->update([
            'user_id' => $request->user_id,
            'store_id' => $request->store_id,
            'visit_date' => $request->visit_date,
            'notes' => $request->notes,
        ]);

        return redirect()->route('rute.detail', $visit->id)
            ->with('success', 'Jadwal rute kunjungan berhasil diperbarui.');
    }

    /**
     * Hapus jadwal rute kunjungan (Admin Only).
     */
    public function destroy($id)
    {
        $visit = Visit::findOrFail($id);

        Gate::authorize('delete', $visit);

        $storeName = $visit->store->name ?? 'Toko';
        $visit->delete();

        return redirect()->route('rute.index')
            ->with('success', "Rute kunjungan ke '{$storeName}' berhasil dihapus.");
    }
}
