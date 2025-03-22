<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turnstile_secret = '0x4AAAAAABBzpXJ3-VSr1q_8A7RvQilT3_I'; // Ganti dengan Secret Key Anda
    $token = json_decode(file_get_contents('php://input'))->token;

    // Verifikasi token dengan Cloudflare
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => $turnstile_secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result);

    if ($response->success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>