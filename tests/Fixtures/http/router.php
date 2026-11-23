<?php

declare(strict_types=1);

// Router of the PHP built-in server that Tests\Support\Http\LocalHttpServer starts: echoes the request back as JSON.

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/missing') {
    http_response_code(404);
}

header('Content-Type: application/json');
header('X-Fixture: yes');

echo json_encode(
    [
        'method'  => $_SERVER['REQUEST_METHOD'] ?? '',
        'uri'     => $_SERVER['REQUEST_URI'] ?? '',
        'headers' => getallheaders(),
        'body'    => file_get_contents('php://input'),
    ],
    JSON_THROW_ON_ERROR,
);
