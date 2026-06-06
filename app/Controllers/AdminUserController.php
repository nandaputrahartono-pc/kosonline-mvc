<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;

final class AdminUserController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function create(): void
    {
        $this->requireAdmin();

        if ($this->isPost() && isset($_POST['simpan'])) {
            $name = trim((string) ($_POST['nama'] ?? ''));
            $username = strtolower(trim((string) ($_POST['username'] ?? '')));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $phone = trim((string) ($_POST['no_hp'] ?? ''));

            if ($name === '' || $username === '' || $phone === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                set_flash('error', 'Nama, username, email valid, dan nomor handphone wajib diisi.');
                redirect_to('/admin/users/create');
            }

            if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
                set_flash('error', 'Username hanya boleh huruf kecil, angka, underscore, minimal 3 karakter.');
                redirect_to('/admin/users/create');
            }

            if (strlen($password) < 6) {
                set_flash('error', 'Password minimal 6 karakter.');
                redirect_to('/admin/users/create');
            }

            if ($this->userModel->usernameExists($username)) {
                set_flash('error', 'Username sudah terdaftar! Gunakan username lain.');
                redirect_to('/admin/users/create');
            }

            if ($this->userModel->emailExists($email)) {
                set_flash('error', 'Email sudah terdaftar! Gunakan email lain.');
                redirect_to('/admin/users/create');
            }

            $this->userModel->create([
                'nama_lengkap' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'no_hp' => $phone,
                'foto_profil' => 'default.jpg',
            ]);

            set_flash('success', 'User Berhasil Ditambahkan');
            redirect_to('/admin/dashboard');
        }

        $this->render('admin/forms/user-create');
    }

    public function edit(): void
    {
        $this->requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if ($user === null) {
            set_flash('error', 'Data user tidak ditemukan.');
            redirect_to('/admin/dashboard');
        }

        if ($this->isPost() && isset($_POST['update'])) {
            $name = trim((string) ($_POST['nama'] ?? ''));
            $username = strtolower(trim((string) ($_POST['username'] ?? '')));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone = trim((string) ($_POST['no_hp'] ?? ''));

            if ($name === '' || $username === '' || $phone === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                set_flash('error', 'Nama, username, email valid, dan nomor handphone wajib diisi.');
                redirect_to('/admin/users/edit?id=' . $id);
            }

            if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
                set_flash('error', 'Username hanya boleh huruf kecil, angka, underscore, minimal 3 karakter.');
                redirect_to('/admin/users/edit?id=' . $id);
            }

            if ($this->userModel->usernameExists($username, $id)) {
                set_flash('error', 'Username sudah dipakai user lain.');
                redirect_to('/admin/users/edit?id=' . $id);
            }

            if ($this->userModel->emailExists($email, $id)) {
                set_flash('error', 'Email sudah dipakai user lain.');
                redirect_to('/admin/users/edit?id=' . $id);
            }

            $payload = [
                'nama_lengkap' => $name,
                'username' => $username,
                'email' => $email,
                'no_hp' => $phone,
            ];

            $password = trim((string) ($_POST['password'] ?? ''));
            if ($password !== '') {
                if (strlen($password) < 6) {
                    set_flash('error', 'Password minimal 6 karakter.');
                    redirect_to('/admin/users/edit?id=' . $id);
                }
                $payload['password'] = $password;
            }

            $this->userModel->update($id, $payload);
            set_flash('success', 'Data User Berhasil Diupdate');
            redirect_to('/admin/dashboard');
        }

        $this->render('admin/forms/user-edit', [
            'user' => $user,
        ]);
    }

    public function delete(): void
    {
        $this->requireAdmin();

        $userId = (int) ($_POST['id'] ?? 0);

        try {
            $this->userModel->delete($userId);
            set_flash('success', 'User Berhasil Dihapus');
        } catch (\Throwable $throwable) {
            set_flash('error', 'Gagal menghapus user. Pastikan user tidak sedang menyewa kamar.');
        }

        redirect_to('/admin/dashboard');
    }
}
