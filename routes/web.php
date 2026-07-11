<?php

declare(strict_types=1);

use App\Controllers\AdminDashboardController;
use App\Controllers\AdminKostController;
use App\Controllers\AdminLocationController;
use App\Controllers\AdminMessageController;
use App\Controllers\AdminPaymentController;
use App\Controllers\AdminRoomController;
use App\Controllers\AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\ChatController;
use App\Controllers\ContactController;
use App\Controllers\HomeController;
use App\Controllers\MapController;
use App\Controllers\MemberController;
use App\Controllers\RoomController;
use App\Controllers\WishlistController;
use App\Core\Router;

/** @var Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/rooms', [RoomController::class, 'index']);
$router->get('/rooms/detail', [RoomController::class, 'detail']);
$router->get('/rooms/payment', [RoomController::class, 'payment']);
$router->post('/rooms/payment', [RoomController::class, 'payment']);
$router->get('/rooms/invoice', [RoomController::class, 'invoice']);
$router->post('/rooms/invoice/upload-proof', [RoomController::class, 'uploadProof']);
$router->post('/rooms/invoice/cancel', [RoomController::class, 'cancelBooking']);
$router->post('/rooms/invoice/confirm-chat', [RoomController::class, 'confirmToAdminChat']);
$router->post('/rooms/review', [RoomController::class, 'review']);
$router->post('/rooms/chat', [ChatController::class, 'sendFromRoom']);
$router->get('/wishlist', [WishlistController::class, 'index']);
$router->post('/wishlist/toggle', [WishlistController::class, 'toggle']);
$router->get('/contact', [ContactController::class, 'index']);
$router->post('/contact', [ContactController::class, 'index']);
$router->get('/map', [MapController::class, 'index']);

// Unified Login Route
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/register', [AuthController::class, 'register']);

// Logout Routes
$router->post('/logout', [AuthController::class, 'memberLogout']);
$router->post('/admin/logout', [AuthController::class, 'adminLogout']);

$router->get('/member/dashboard', [MemberController::class, 'dashboard']);
$router->post('/member/dashboard', [MemberController::class, 'dashboard']);
$router->post('/member/orders/delete', [MemberController::class, 'deleteOrder']);
$router->post('/member/chat/send', [ChatController::class, 'sendFromMember']);
$router->get('/member/chat/messages', [ChatController::class, 'memberMessages']);
$router->post('/member/chat/typing', [ChatController::class, 'memberTyping']);

$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/dashboard', [AdminDashboardController::class, 'dashboard']);
$router->post('/admin/chat/send', [ChatController::class, 'sendFromAdmin']);
$router->get('/admin/chat/messages', [ChatController::class, 'adminMessages']);
$router->post('/admin/chat/typing', [ChatController::class, 'adminTyping']);

$router->get('/admin/kost/create', [AdminKostController::class, 'create']);
$router->post('/admin/kost/create', [AdminKostController::class, 'create']);
$router->get('/admin/kost/edit', [AdminKostController::class, 'edit']);
$router->post('/admin/kost/edit', [AdminKostController::class, 'edit']);
$router->post('/admin/kost/delete', [AdminKostController::class, 'delete']);

$router->get('/admin/rooms/create', [AdminRoomController::class, 'create']);
$router->post('/admin/rooms/create', [AdminRoomController::class, 'create']);
$router->get('/admin/rooms/edit', [AdminRoomController::class, 'edit']);
$router->post('/admin/rooms/edit', [AdminRoomController::class, 'edit']);
$router->post('/admin/rooms/delete', [AdminRoomController::class, 'delete']);
$router->post('/admin/rooms/toggle-status', [AdminRoomController::class, 'toggleStatus']);

$router->get('/admin/users/create', [AdminUserController::class, 'create']);
$router->post('/admin/users/create', [AdminUserController::class, 'create']);
$router->get('/admin/users/edit', [AdminUserController::class, 'edit']);
$router->post('/admin/users/edit', [AdminUserController::class, 'edit']);
$router->post('/admin/users/delete', [AdminUserController::class, 'delete']);

$router->get('/admin/locations/edit', [AdminLocationController::class, 'edit']);
$router->post('/admin/locations/edit', [AdminLocationController::class, 'edit']);
$router->post('/admin/payments/update', [AdminPaymentController::class, 'update']);
$router->post('/admin/bookings/delete', [AdminPaymentController::class, 'deleteBooking']);
$router->post('/admin/messages/delete', [AdminMessageController::class, 'delete']);

// Redirect old admin login to unified login
$router->get('/admin/login', static fn (): never => redirect_to('/login'));
$router->post('/admin/login', static fn (): never => redirect_to('/login'));

// Legacy aliases while keeping a single front controller.
$router->get('/admin.php', static fn (): never => redirect_to('/admin/dashboard'));
$router->get('/login.php', static fn (): never => redirect_to('/login'));
$router->get('/kamar.php', static fn (): never => redirect_to('/rooms'));
$router->get('/menu/kamar.php', static fn (): never => redirect_to('/rooms'));
$router->get('/menu/contact.php', static fn (): never => redirect_to('/contact'));
$router->get('/menu/lainnya.php', static fn (): never => redirect_to('/map'));
$router->get('/anggota/anggota.php', static fn (): never => redirect_to('/member/dashboard'));
$router->get('/admin/index.php', static fn (): never => redirect_to('/login'));
$router->get('/admin/admin.php', static fn (): never => redirect_to('/admin/dashboard'));
