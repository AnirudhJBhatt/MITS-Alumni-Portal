<?php
/**
 * MITS Alumni Portal - API Base Dispatcher / Fallback
 */
header('Content-Type: application/json; charset=UTF-8');
http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'API endpoint required.'
], JSON_UNESCAPED_UNICODE);
exit;
