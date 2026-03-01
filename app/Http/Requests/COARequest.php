<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class COARequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $coaId = $this->route('coa')?->id ?? $this->route('coa');
        $businessUnitId = auth()->user()?->business_unit_id;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('c_o_a_s', 'code')
                    ->where('business_unit_id', $businessUnitId)
                    ->ignore($coaId),
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:aktiva,pasiva,modal,pendapatan,beban',
            'parent_code' => 'nullable|exists:c_o_a_s,id',
            'order' => 'required|integer|min:1',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'is_leaf_account' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode akun wajib diisi.',
            'code.unique' => 'Kode akun sudah digunakan.',
            'code.max' => 'Kode akun maksimal 20 karakter.',
            'name.required' => 'Nama akun wajib diisi.',
            'name.max' => 'Nama akun maksimal 255 karakter.',
            'type.required' => 'Tipe akun wajib dipilih.',
            'type.in' => 'Tipe akun harus salah satu dari: aktiva, pasiva, modal, pendapatan, beban.',
            'parent_code.exists' => 'Kode akun induk tidak ditemukan.',
            'order.required' => 'Urutan akun wajib diisi.',
            'order.integer' => 'Urutan akun harus berupa angka.',
            'order.min' => 'Urutan akun minimal 1.',
            'description.max' => 'Deskripsi maksimal 500 karakter.',
        ];
    }

    public function coaData(): array
    {
        return [
            'code' => $this->input('code'),
            'name' => $this->input('name'),
            'type' => $this->input('type'),
            'parent_code' => $this->input('parent_code'),
            'order' => $this->input('order'),
            'description' => $this->input('description'),
            'is_active' => $this->boolean('is_active', true),
            'is_leaf_account' => $this->boolean('is_leaf_account', true),
        ];
    }
}
