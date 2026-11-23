<?php
$ch = curl_init('https://pay.iotec.io/swagger/v1/swagger.json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$res = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP: $http, Length: " . strlen($res) . "\n";
if ($http == 200 && $res) {
    file_put_contents(__DIR__ . '/swagger.json', $res);
    $json = json_decode($res, true);
    if ($json && isset($json['paths'])) {
        foreach (array_keys($json['paths']) as $p) {
            echo "PATH: $p\n";
        }
    }
}
