<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;

class VisitPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar rute kunjungan.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Memeriksa apakah data visit yang diakses via URI dinamis (misal: /rute/detail/{id})
     * benar-benar milik user_id Sales yang sedang login, atau pengguna adalah Admin.
     */
    public function view(User $user, Visit $visit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $visit->user_id === (int) $user->id;
    }

    /**
     * Menentukan apakah pengguna dapat membuat/menjadwalkan kunjungan baru.
     * Admin memiliki akses penuh untuk mengelola master data rute.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui jadwal kunjungan.
     * - Admin dapat memperbarui jadwal.
     * - Sales tidak dapat mengubah rute yang sudah berstatus Selesai (kunci data / imutabilitas).
     */
    public function update(User $user, Visit $visit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Aturan Imutabilitas: Jika sudah selesai, formulir dikunci dan tidak dapat diubah oleh Sales
        return (int) $visit->user_id === (int) $user->id && $visit->status === 'Direncanakan';
    }

    /**
     * Menentukan apakah pengguna dapat melakukan check-in pada kunjungan.
     * - Hanya kunjungan milik sales tersebut.
     * - Formulir dikunci (read-only) jika sudah selesai.
     */
    public function checkin(User $user, Visit $visit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $visit->user_id === (int) $user->id && $visit->status === 'Direncanakan';
    }

    /**
     * Menentukan apakah pengguna dapat menghapus rute kunjungan.
     * Hanya Admin yang berhak menghapus master data rute.
     */
    public function delete(User $user, Visit $visit): bool
    {
        return $user->isAdmin();
    }
}
