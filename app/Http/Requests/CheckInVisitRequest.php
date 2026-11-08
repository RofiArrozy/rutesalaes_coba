<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInVisitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Persiapkan dan normalisasi input sebelum divalidasi.
     * Mendukung alias nama field bahasa Indonesia sesuai dokumen SRS I.3:
     * - status / result_status
     * - nilai_pesanan / order_value
     * - catatan / fail_reason / notes
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // Pemetaan alias 'status' -> 'result_status'
        if ($this->has('status') && ! $this->has('result_status')) {
            $mergeData['result_status'] = $this->input('status');
        } elseif ($this->has('result_status') && ! $this->has('status')) {
            $mergeData['status'] = $this->input('result_status');
        }

        // Pemetaan alias 'nilai_pesanan' -> 'order_value'
        if ($this->has('nilai_pesanan') && ! $this->has('order_value')) {
            $mergeData['order_value'] = $this->input('nilai_pesanan');
        } elseif ($this->has('order_value') && ! $this->has('nilai_pesanan')) {
            $mergeData['nilai_pesanan'] = $this->input('order_value');
        }

        // Pemetaan alias 'catatan' -> 'fail_reason' / 'notes'
        if ($this->has('catatan')) {
            if (! $this->has('fail_reason')) {
                $mergeData['fail_reason'] = $this->input('catatan');
            }
            if (! $this->has('notes')) {
                $mergeData['notes'] = $this->input('catatan');
            }
        }

        $activeStatus = $this->input('result_status', $this->input('status'));
        if (in_array($activeStatus, ['Tutup', 'Ditolak'], true)) {
            // Jika status Tutup atau Ditolak dan tidak ada nilai pesanan, set nilai_pesanan / order_value ke 0
            if (! $this->filled('order_value') && ! $this->filled('nilai_pesanan')) {
                $mergeData['order_value'] = 0;
                $mergeData['nilai_pesanan'] = 0;
            }
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Aturan validasi server-side sesuai dokumen SRS Bagian I.3:
     * - status: Wajib diisi (required), bernilai salah satu dari: Berhasil, Tutup, Ditolak.
     * - nilai_pesanan:
     *     * Wajib diisi (required) dan harus numerik positif (>= 0) jika status = Berhasil.
     *     * Wajib bernilai null atau 0 jika status = Tutup atau Ditolak.
     * - catatan: Wajib diisi jika status Tutup atau Ditolak, opsional jika status Berhasil.
     */
    public function rules(): array
    {
        return [
            'result_status' => ['required', 'in:Berhasil,Tutup,Ditolak'],
            'status' => ['nullable', 'in:Berhasil,Tutup,Ditolak'],
            'order_value' => [
                'required_if:result_status,Berhasil',
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $status = $this->input('result_status', $this->input('status'));
                    if (in_array($status, ['Tutup', 'Ditolak'], true)) {
                        if (! is_null($value) && (float) $value != 0) {
                            $fail('Nilai pesanan wajib bernilai null atau 0 jika status kunjungan Tutup atau Ditolak.');
                        }
                    }
                },
            ],
            'nilai_pesanan' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'fail_reason' => [
                'required_if:result_status,Tutup,Ditolak',
                'nullable',
                'string',
                'min:3',
                'max:1000',
            ],
            'catatan' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'simulated_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'simulated_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Custom message validation.
     */
    public function messages(): array
    {
        return [
            'result_status.required' => 'Hasil status kunjungan wajib dipilih (Berhasil, Tutup, atau Ditolak).',
            'result_status.in' => 'Status hasil harus berupa Berhasil, Tutup, atau Ditolak.',
            'status.required' => 'Status kunjungan wajib diisi.',
            'status.in' => 'Status harus berupa Berhasil, Tutup, atau Ditolak.',
            'order_value.required_if' => 'Nilai pesanan wajib diisi jika status kunjungan Berhasil.',
            'order_value.numeric' => 'Nilai pesanan harus berupa angka yang valid.',
            'order_value.min' => 'Nilai pesanan harus numerik positif (>= 0).',
            'nilai_pesanan.required_if' => 'Nilai pesanan wajib diisi jika status kunjungan Berhasil.',
            'nilai_pesanan.numeric' => 'Nilai pesanan harus berupa angka yang valid.',
            'nilai_pesanan.min' => 'Nilai pesanan harus numerik positif (>= 0).',
            'fail_reason.required_if' => 'Catatan/alasan wajib diisi jika status Tutup atau Ditolak.',
            'fail_reason.min' => 'Catatan alasan minimal 3 karakter.',
            'catatan.required_if' => 'Catatan wajib diisi jika status Tutup atau Ditolak.',
            'simulated_latitude.numeric' => 'Koordinat latitude simulasi harus berupa angka.',
            'simulated_longitude.numeric' => 'Koordinat longitude simulasi harus berupa angka.',
        ];
    }
}
