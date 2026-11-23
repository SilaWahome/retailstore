<?php
$json = json_decode(file_get_contents(__DIR__ . '/swagger.json'), true);

echo "=== COLLECT ===\n";
print_r($json['paths']['/api/collections/collect'] ?? []);

echo "\n=== STATUS BY EXTERNAL ID (COLLECTION) ===\n";
print_r($json['paths']['/api/collections/external-id/{externalId}'] ?? []);

echo "\n=== DISBURSE ===\n";
print_r($json['paths']['/api/disbursements/disburse'] ?? []);

echo "\n=== STATUS BY EXTERNAL ID (DISBURSEMENT) ===\n";
print_r($json['paths']['/api/disbursements/external-id/{externalId}'] ?? []);
