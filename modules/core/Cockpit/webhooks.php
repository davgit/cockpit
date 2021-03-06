<?php

// make sure curl extension is loaded
if (!function_exists('curl_init')) {
    return;
}

$webhooks = $app->storage->find("cockpit/webhooks");

foreach ($webhooks as &$webhook) {

    if ($webhook['active'] && $webhook['url'] && count($webhook['events'])) {

        foreach ($webhook['events'] as $evt) {

            $app->on($evt, function() use($evt, $webhook) {

                $ch      = curl_init($webhook['url']);
                $data    = json_encode(['event' => $evt, 'args' => func_get_args()]);
                $headers = [
                    'Content-Type: application/json',
                    'Content-Length: '.strlen($data)
                ];

                // add custom headers
                if (isset($webhook['headers']) && is_array($webhook['headers']) && count($webhook['headers'])) {

                    foreach ($webhook['headers'] as &$h) {
                        
                        if (!isset($h['k'], $h['v']) || !$h['k'] || !$h['v']) {
                            continue;
                        }
                        $headers[] = implode(': ', [$h['k'], $h['v']]);
                    }
                }

                // add basic hhtp auth
                if (isset($webhook['auth']) && $webhook['auth']['user'] && $webhook['auth']['pass']) {
                    curl_setopt($ch, CURLOPT_USERPWD, $webhook['auth']['user'] . ":" . $webhook['auth']['pass']);
                }

                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                curl_exec($ch);

            }, -1000);
        }
    }
}
