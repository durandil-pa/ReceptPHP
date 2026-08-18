<?php
declare(strict_types=1);

require __DIR__ . '/../app/Services/MeasurementConverter.php';

use App\Services\MeasurementConverter;

$converter = new MeasurementConverter();
$failures = [];

$assertConversion = static function (
    float $input,
    string $sourceUnit,
    float $expected,
    string $expectedUnit,
    string $expectedCategory
) use ($converter, &$failures): void {
    $result = $converter->convert($input, $sourceUnit);

    if (abs($result['value'] - $expected) > 0.0001
        || $result['unit'] !== $expectedUnit
        || $result['category'] !== $expectedCategory
    ) {
        $failures[] = sprintf('Felaktig omvandling av %s %s.', $input, $sourceUnit);
    }
};

$assertConversion(1, 'cup', 2.365882365, 'dl', 'volume');
$assertConversion(1, 'fl oz', 29.5735295625, 'ml', 'volume');
$assertConversion(1, 'oz', 28.349523125, 'g', 'weight');
$assertConversion(1, 'tbsp', 0.98578431875, 'msk', 'volume');
$assertConversion(1, 'tsp', 0.98578431875, 'tsk', 'volume');
$assertConversion(1, 'lb', 453.59237, 'g', 'weight');
$assertConversion(32, '°F', 0, '°C', 'temperature');
$assertConversion(212, 'fahrenheit', 100, '°C', 'temperature');

try {
    $converter->convert(1, 'deciliter');
    $failures[] = 'En okänd enhet accepterades.';
} catch (\InvalidArgumentException $exception) {
    // Expected.
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "MeasurementConverter: alla tester godkända." . PHP_EOL;

