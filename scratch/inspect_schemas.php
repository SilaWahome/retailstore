<?php
$json = json_decode(file_get_contents(__DIR__ . '/swagger.json'), true);

function dumpSchemaRef($json, $ref) {
    $parts = explode('/', ltrim($ref, '#/'));
    $curr = $json;
    foreach ($parts as $p) {
        $curr = $curr[$p] ?? null;
    }
    return $curr;
}

$collectReq = $json['paths']['/api/collections/collect']['post']['requestBody']['content']['application/json']['schema']['$ref'] ?? null;
echo "Collect Request Schema Ref: $collectReq\n";
print_r(dumpSchemaRef($json, $collectReq));

$disburseReq = $json['paths']['/api/disbursements/disburse']['post']['requestBody']['content']['application/json']['schema']['$ref'] ?? null;
echo "\nDisburse Request Schema Ref: $disburseReq\n";
print_r(dumpSchemaRef($json, $disburseReq));
