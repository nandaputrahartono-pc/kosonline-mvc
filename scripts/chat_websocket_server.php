<?php

declare(strict_types=1);

use App\Models\ChatModel;

require_once dirname(__DIR__) . '/bootstrap.php';

$host = getenv('CHAT_WS_HOST') ?: '127.0.0.1';
$port = (int) (getenv('CHAT_WS_PORT') ?: 8098);
$server = @stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);

if ($server === false) {
    fwrite(STDERR, "Gagal menjalankan WebSocket chat: {$errstr} ({$errno})\n");
    exit(1);
}

stream_set_blocking($server, false);
echo "KosOnline chat WebSocket aktif di ws://{$host}:{$port}\n";

$chatModel = new ChatModel();
$clients = [];
$nextClientId = 1;

while (true) {
    $read = [$server];
    foreach ($clients as $client) {
        $read[] = $client['socket'];
    }

    $write = null;
    $except = null;
    $changed = @stream_select($read, $write, $except, 1, 0);

    if ($changed !== false && $changed > 0) {
        foreach ($read as $socket) {
            if ($socket === $server) {
                $clientSocket = @stream_socket_accept($server, 0);
                if ($clientSocket === false) {
                    continue;
                }

                stream_set_blocking($clientSocket, false);
                $clients[$nextClientId] = [
                    'socket' => $clientSocket,
                    'handshake' => false,
                    'thread_id' => null,
                    'role' => 'user',
                    'last_signature' => '',
                ];
                $nextClientId++;
                continue;
            }

            $clientId = findClientId($clients, $socket);
            if ($clientId === null) {
                continue;
            }

            $data = @fread($socket, 8192);
            if ($data === '' || $data === false) {
                closeClient($clients, $clientId);
                continue;
            }

            if (!$clients[$clientId]['handshake']) {
                if (performHandshake($socket, $data)) {
                    $clients[$clientId]['handshake'] = true;
                } else {
                    closeClient($clients, $clientId);
                }
                continue;
            }

            foreach (decodeFrames($data) as $frame) {
                if ($frame === null) {
                    closeClient($clients, $clientId);
                    continue 2;
                }

                $payload = json_decode($frame, true);
                if (!is_array($payload) || ($payload['type'] ?? '') !== 'subscribe') {
                    continue;
                }

                $threadId = (int) ($payload['threadId'] ?? 0);
                if ($threadId <= 0) {
                    continue;
                }

                $role = (string) ($payload['role'] ?? 'user');
                $clients[$clientId]['thread_id'] = $threadId;
                $clients[$clientId]['role'] = $role === 'admin' ? 'admin' : 'user';
                $clients[$clientId]['last_signature'] = '';
            }
        }
    }

    pushChatUpdates($clients, $chatModel);
}

/**
 * @param array<int, array<string, mixed>> $clients
 */
function findClientId(array $clients, mixed $socket): ?int
{
    foreach ($clients as $clientId => $client) {
        if ($client['socket'] === $socket) {
            return (int) $clientId;
        }
    }

    return null;
}

/**
 * @param array<int, array<string, mixed>> $clients
 */
function closeClient(array &$clients, int $clientId): void
{
    if (isset($clients[$clientId]['socket']) && is_resource($clients[$clientId]['socket'])) {
        @fclose($clients[$clientId]['socket']);
    }

    unset($clients[$clientId]);
}

function performHandshake(mixed $socket, string $headers): bool
{
    if (preg_match('/Sec-WebSocket-Key:\s*(.+)\r\n/i', $headers, $matches) !== 1) {
        return false;
    }

    $key = trim($matches[1]);
    $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    $response = "HTTP/1.1 101 Switching Protocols\r\n"
        . "Upgrade: websocket\r\n"
        . "Connection: Upgrade\r\n"
        . "Sec-WebSocket-Accept: {$accept}\r\n\r\n";

    return @fwrite($socket, $response) !== false;
}

/**
 * @return array<int, string|null>
 */
function decodeFrames(string $data): array
{
    $frames = [];
    $offset = 0;
    $dataLength = strlen($data);

    while ($offset + 2 <= $dataLength) {
        $firstByte = ord($data[$offset]);
        $secondByte = ord($data[$offset + 1]);
        $opcode = $firstByte & 0x0F;
        $masked = ($secondByte & 0x80) === 0x80;
        $payloadLength = $secondByte & 0x7F;
        $offset += 2;

        if ($opcode === 0x8) {
            $frames[] = null;
            break;
        }

        if ($payloadLength === 126) {
            if ($offset + 2 > $dataLength) {
                break;
            }
            $payloadLength = unpack('n', substr($data, $offset, 2))[1];
            $offset += 2;
        } elseif ($payloadLength === 127) {
            if ($offset + 8 > $dataLength) {
                break;
            }
            $lengthParts = unpack('Nhigh/Nlow', substr($data, $offset, 8));
            if ((int) $lengthParts['high'] !== 0) {
                break;
            }
            $payloadLength = (int) $lengthParts['low'];
            $offset += 8;
        }

        $mask = '';
        if ($masked) {
            if ($offset + 4 > $dataLength) {
                break;
            }
            $mask = substr($data, $offset, 4);
            $offset += 4;
        }

        if ($offset + $payloadLength > $dataLength) {
            break;
        }

        $payload = substr($data, $offset, $payloadLength);
        $offset += $payloadLength;

        if ($masked) {
            $decoded = '';
            for ($i = 0; $i < $payloadLength; $i++) {
                $decoded .= $payload[$i] ^ $mask[$i % 4];
            }
            $payload = $decoded;
        }

        if ($opcode === 0x1) {
            $frames[] = $payload;
        }
    }

    return $frames;
}

function encodeFrame(string $payload): string
{
    $length = strlen($payload);

    if ($length <= 125) {
        return chr(0x81) . chr($length) . $payload;
    }

    if ($length <= 65535) {
        return chr(0x81) . chr(126) . pack('n', $length) . $payload;
    }

    return chr(0x81) . chr(127) . pack('N2', 0, $length) . $payload;
}

/**
 * @param array<int, array<string, mixed>> $clients
 */
function pushChatUpdates(array &$clients, ChatModel $chatModel): void
{
    foreach ($clients as $clientId => &$client) {
        if (!$client['handshake'] || empty($client['thread_id'])) {
            continue;
        }

        $payload = buildChatPayload($chatModel, (int) $client['thread_id'], (string) $client['role']);
        if (!$payload['ok']) {
            continue;
        }

        $signature = md5(json_encode([
            $payload['messages'],
            $payload['peer_typing'],
            $payload['peer_label'],
            $payload['context_card'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($signature === $client['last_signature']) {
            continue;
        }

        $message = json_encode([
            'type' => 'chat:update',
            'payload' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($message === false || @fwrite($client['socket'], encodeFrame($message)) === false) {
            closeClient($clients, $clientId);
            continue;
        }

        $client['last_signature'] = $signature;
    }
    unset($client);
}

function buildChatPayload(ChatModel $chatModel, int $threadId, string $role): array
{
    $thread = $chatModel->getThreadForAdmin($threadId);
    if ($thread === null) {
        return ['ok' => false];
    }

    $isAdmin = $role === 'admin';

    return [
        'ok' => true,
        'thread_id' => $threadId,
        'messages' => $chatModel->formatMessagesForJson($chatModel->getMessages($threadId)),
        'peer_typing' => $chatModel->isTyping($threadId, $isAdmin ? 'user' : 'admin'),
        'peer_label' => $isAdmin ? (string) ($thread['nama_lengkap'] ?? 'User') : 'Admin',
        'me_label' => $isAdmin ? 'Admin' : 'Kamu',
        'context_card' => $chatModel->roomContextForJson($thread),
    ];
}
