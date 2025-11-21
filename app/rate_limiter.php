<?php
function global_rate_limit($limit = 30, $window = 60) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time();

    $file = __DIR__ . "/rate_limits.json";

    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
    }

    $data = json_decode(file_get_contents($file), true);

    if (!isset($data[$ip])) {
        $data[$ip] = ['count' => 1, 'start' => $now];
    } else {
        // Window expired → reset
        if ($now - $data[$ip]['start'] > $window) {
            $data[$ip] = ['count' => 1, 'start' => $now];
        } else {
            // If limit exceeded
            if ($data[$ip]['count'] >= $limit) {
                file_put_contents($file, json_encode($data));
                return false;
            }
            $data[$ip]['count']++;
        }
    }

    file_put_contents($file, json_encode($data));
    return true;
}