<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\WishlistModel;

final class WishlistController extends Controller
{
    private WishlistModel $wishlistModel;

    public function __construct()
    {
        $this->wishlistModel = new WishlistModel();
    }

    public function toggle(): void
    {
        $this->requireUser();

        $roomId = (int) ($_POST['id_kamar'] ?? 0);
        $redirect = (string) ($_POST['redirect'] ?? '/rooms');

        if ($roomId <= 0) {
            set_flash('error', 'Kamar tidak valid.');
            redirect_to($redirect);
        }

        $saved = $this->wishlistModel->toggle((int) $_SESSION['id_user'], $roomId);
        set_flash('success', $saved ? 'Kamar disimpan ke wishlist.' : 'Kamar dihapus dari wishlist.');
        redirect_to($redirect);
    }
}
