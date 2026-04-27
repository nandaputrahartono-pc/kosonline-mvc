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
            $email = trim((string) $_POST['email']);

            if ($this->userModel->emailExists($email)) {
                set_flash('error', 'Email sudah terdaftar! Gunakan email lain.');
                redirect_to('/admin/users/create');
            }

            $this->userModel->create([
                'nama_lengkap' => trim((string) $_POST['nama']),
                'email' => $email,
                'password' => (string) $_POST['password'],
                'no_hp' => trim((string) $_POST['no_hp']),
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
            $email = trim((string) $_POST['email']);

            if ($this->userModel->emailExists($email, $id)) {
                set_flash('error', 'Email sudah dipakai user lain.');
                redirect_to('/admin/users/edit?id=' . $id);
            }

            $payload = [
                'nama_lengkap' => trim((string) $_POST['nama']),
                'email' => $email,
                'no_hp' => trim((string) $_POST['no_hp']),
            ];

            $password = trim((string) ($_POST['password'] ?? ''));
            if ($password !== '') {
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

        $userId = (int) ($_GET['id'] ?? 0);

        try {
            $this->userModel->delete($userId);
            set_flash('success', 'User Berhasil Dihapus');
        } catch (\Throwable $throwable) {
            set_flash('error', 'Gagal menghapus user. Pastikan user tidak sedang menyewa kamar.');
        }

        redirect_to('/admin/dashboard');
    }
}
