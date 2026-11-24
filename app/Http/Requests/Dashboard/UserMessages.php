<?php

namespace App\Http\Requests\Dashboard;

/**
 * Indonesian validation messages shared by the user store/update requests.
 */
final class UserMessages
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai.',
            'phone.regex' => 'Nomor telepon harus 6-20 karakter berisi angka, spasi, +, -, atau tanda kurung.',
            'role.required' => 'Peran wajib dipilih.',
            'role.enum' => 'Peran tidak valid.',
            'is_active.required' => 'Status wajib dipilih.',
            'is_active.boolean' => 'Status tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }
}
