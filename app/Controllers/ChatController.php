<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ChatModel;
use App\Models\RoomModel;

final class ChatController extends Controller
{
    private ChatModel $chatModel;
    private RoomModel $roomModel;

    public function __construct()
    {
        $this->chatModel = new ChatModel();
        $this->roomModel = new RoomModel();
    }

    public function sendFromMember(): void
    {
        $this->requireUser();

        $threadId = (int) ($_POST['id_thread'] ?? 0);
        $roomId = (int) ($_POST['id_kamar'] ?? 0);
        $message = trim((string) ($_POST['isi_pesan'] ?? ''));

        if ($message === '') {
            set_flash('error', 'Pesan tidak boleh kosong.');
            $target = '/member/dashboard?tab=chat';
            $target .= $threadId > 0 ? '&thread=' . $threadId : '';
            $target .= $roomId > 0 ? '&pending_room=' . $roomId : '';
            redirect_to($target);
        }

        if ($threadId <= 0) {
            $threadId = $this->chatModel->getOrCreateThread((int) $_SESSION['id_user']);
        } elseif ($this->chatModel->getThreadForUser($threadId, (int) $_SESSION['id_user']) === null) {
            set_flash('error', 'Chat tidak ditemukan.');
            redirect_to('/member/dashboard?tab=chat');
        }

        $roomId = $roomId > 0 && $this->chatModel->roomCardByRoomId($roomId) !== null ? $roomId : 0;

        $this->chatModel->addMessage($threadId, 'user', $message, $roomId > 0 ? $roomId : null);
        $this->chatModel->setTyping($threadId, 'user', false);

        if ($this->expectsJson()) {
            $this->json($this->memberThreadPayload($threadId));
        }

        redirect_to('/member/dashboard?tab=chat&thread=' . $threadId);
    }

    public function sendFromRoom(): void
    {
        $this->requireUser();

        $roomId = (int) ($_POST['id_kamar'] ?? 0);
        $message = trim((string) ($_POST['isi_pesan'] ?? ''));
        $room = $this->roomModel->findByIdWithKost($roomId);

        if ($room === null) {
            set_flash('error', 'Kamar tidak ditemukan.');
            redirect_to('/rooms');
        }

        $threadId = $this->chatModel->getOrCreateThread((int) $_SESSION['id_user']);

        if ($message !== '') {
            $this->chatModel->addMessage($threadId, 'user', $message, $roomId);
            $this->chatModel->setTyping($threadId, 'user', false);
            set_flash('success', 'Pesan dan detail kamar dikirim ke admin.');

            if ($this->expectsJson()) {
                $this->json($this->memberThreadPayload($threadId));
            }

            redirect_to('/member/dashboard?tab=chat&thread=' . $threadId);
        }

        set_flash('success', 'Detail kamar siap dikirim. Tulis pertanyaanmu lalu kirim ke admin.');

        if ($this->expectsJson()) {
            $payload = $this->memberThreadPayload($threadId);
            $payload['pending_room_id'] = $roomId;
            $payload['pending_room_card'] = $this->chatModel->roomCardByRoomId($roomId);
            $this->json($payload);
        }

        redirect_to('/member/dashboard?tab=chat&thread=' . $threadId . '&pending_room=' . $roomId);
    }

    public function sendFromAdmin(): void
    {
        $this->requireAdmin();

        $threadId = (int) ($_POST['id_thread'] ?? 0);
        $message = trim((string) ($_POST['isi_pesan'] ?? ''));

        if ($threadId <= 0 || $this->chatModel->getThreadForAdmin($threadId) === null) {
            set_flash('error', 'Chat tidak ditemukan.');
            redirect_to('/admin/dashboard?tab=chat-user');
        }

        if ($message === '') {
            set_flash('error', 'Balasan tidak boleh kosong.');
            redirect_to('/admin/dashboard?tab=chat-user&thread=' . $threadId);
        }

        $this->chatModel->addMessage($threadId, 'admin', $message);
        $this->chatModel->setTyping($threadId, 'admin', false);

        if ($this->expectsJson()) {
            $this->json($this->adminThreadPayload($threadId));
        }

        redirect_to('/admin/dashboard?tab=chat-user&thread=' . $threadId);
    }

    public function memberMessages(): void
    {
        $this->requireUser();

        $userId = (int) $_SESSION['id_user'];
        $threadId = (int) ($_GET['thread'] ?? 0);
        if ($threadId <= 0 || $this->chatModel->getThreadForUser($threadId, $userId) === null) {
            $this->json(['ok' => false, 'message' => 'Chat tidak ditemukan.'], 404);
        }

        // Sama seperti sisi admin: pesan admin yang masuk saat user membuka chat
        // langsung ditandai terbaca supaya badge angka tidak menumpuk.
        $this->chatModel->markThreadReadForUser($threadId, $userId);

        $this->json($this->memberThreadPayload($threadId));
    }

    public function adminMessages(): void
    {
        $this->requireAdmin();

        $threadId = (int) ($_GET['thread'] ?? 0);
        if ($threadId <= 0 || $this->chatModel->getThreadForAdmin($threadId) === null) {
            $this->json(['ok' => false, 'message' => 'Chat tidak ditemukan.'], 404);
        }

        // Admin sedang membuka thread ini, jadi pesan user yang baru masuk lewat polling
        // realtime pun langsung dianggap terbaca. Tanpa ini notifikasi menumpuk terus.
        $this->chatModel->markThreadReadForAdmin($threadId);

        $this->json($this->adminThreadPayload($threadId));
    }

    public function memberTyping(): void
    {
        $this->requireUser();

        $threadId = (int) ($_POST['id_thread'] ?? 0);
        if ($threadId <= 0 || $this->chatModel->getThreadForUser($threadId, (int) $_SESSION['id_user']) === null) {
            $this->json(['ok' => false, 'message' => 'Chat tidak ditemukan.'], 404);
        }

        $this->chatModel->setTyping($threadId, 'user', (string) ($_POST['is_typing'] ?? '0') === '1');
        $this->json(['ok' => true]);
    }

    public function adminTyping(): void
    {
        $this->requireAdmin();

        $threadId = (int) ($_POST['id_thread'] ?? 0);
        if ($threadId <= 0 || $this->chatModel->getThreadForAdmin($threadId) === null) {
            $this->json(['ok' => false, 'message' => 'Chat tidak ditemukan.'], 404);
        }

        $this->chatModel->setTyping($threadId, 'admin', (string) ($_POST['is_typing'] ?? '0') === '1');
        $this->json(['ok' => true]);
    }

    private function memberThreadPayload(int $threadId): array
    {
        $thread = $this->chatModel->getThreadForAdmin($threadId);

        return [
            'ok' => true,
            'thread_id' => $threadId,
            'messages' => $this->chatModel->formatMessagesForJson($this->chatModel->getMessages($threadId)),
            'peer_typing' => $this->chatModel->isTyping($threadId, 'admin'),
            'peer_label' => 'Admin',
            'me_label' => 'Kamu',
            'context_card' => null,
        ];
    }

    private function adminThreadPayload(int $threadId): array
    {
        $thread = $this->chatModel->getThreadForAdmin($threadId);

        return [
            'ok' => true,
            'thread_id' => $threadId,
            'messages' => $this->chatModel->formatMessagesForJson($this->chatModel->getMessages($threadId)),
            'peer_typing' => $this->chatModel->isTyping($threadId, 'user'),
            'peer_label' => (string) ($thread['nama_lengkap'] ?? 'User'),
            'me_label' => 'Admin',
            'context_card' => null,
        ];
    }

    private function expectsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'fetch';
    }

    private function formatRoomLabel(mixed $roomNumber): string
    {
        $label = trim((string) $roomNumber);
        if ($label === '') {
            return 'Kamar -';
        }

        return preg_match('/^kamar\b/i', $label) === 1 ? $label : 'Kamar ' . $label;
    }

    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
