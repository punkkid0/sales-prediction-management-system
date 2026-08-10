<?php
/**
 * HTTP client for the Python Flask prediction service (Phase 2/3).
 */

declare(strict_types=1);

/**
 * @return array{ok:bool,status:int,data?:array,error?:string}
 */
function ml_request(string $method, string $path, ?array $body = null, int $timeout = 60): array
{
    $base = rtrim((string) app_config('ml_api_base', 'http://127.0.0.1:5000'), '/');
    $url = $base . $path;

    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'status' => 0,
            'error' => 'PHP cURL extension is not enabled. Enable extension=curl in php.ini.',
        ];
    }

    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
    ];

    if ($body !== null) {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_POSTFIELDS] = $json;
    }

    $opts[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $opts);

    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return [
            'ok' => false,
            'status' => 0,
            'error' => 'Cannot reach prediction service at ' . $base . ' (' . $err . '). '
                . 'Start it with: cd ml_service && python app.py',
        ];
    }

    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        return [
            'ok' => false,
            'status' => $status,
            'error' => 'Invalid JSON from prediction service (HTTP ' . $status . ').',
        ];
    }

    if ($status >= 400) {
        return [
            'ok' => false,
            'status' => $status,
            'data' => $data,
            'error' => (string) ($data['error'] ?? ('Prediction API error HTTP ' . $status)),
        ];
    }

    return ['ok' => true, 'status' => $status, 'data' => $data];
}

function ml_health(): array
{
    return ml_request('GET', '/health', null, 5);
}

/**
 * @return array{ok:bool,status:int,data?:array,error?:string}
 */
function ml_predict(int $productId, int $horizonDays = 7, string $model = 'lstm', bool $persist = true): array
{
    return ml_request('POST', '/predict', [
        'product_id'   => $productId,
        'horizon_days' => $horizonDays,
        'model'        => $model,
        'persist'      => $persist,
    ], 90);
}

/**
 * @return array{ok:bool,status:int,data?:array,error?:string}
 */
function ml_retrain(?int $productId = null, string $model = 'both'): array
{
    $payload = [
        'secret' => (string) app_config('ml_retrain_secret', ''),
        'model'  => $model,
    ];
    if ($productId !== null) {
        $payload['product_id'] = $productId;
    }
    // Training can take a while
    return ml_request('POST', '/retrain', $payload, 300);
}
