<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class MeasurementConverter
{
    private const UNITS = [
        'cup' => [
            'aliases' => ['cup', 'cups'],
            'factor' => 2.365882365,
            'unit' => 'dl',
            'category' => 'volume',
        ],
        'fluid_ounce' => [
            'aliases' => ['fluid ounce', 'fluid ounces', 'fl ounce', 'fl ounces', 'fl oz', 'fl. oz.'],
            'factor' => 29.5735295625,
            'unit' => 'ml',
            'category' => 'volume',
        ],
        'ounce' => [
            'aliases' => ['ounce', 'ounces', 'oz'],
            'factor' => 28.349523125,
            'unit' => 'g',
            'category' => 'weight',
        ],
        'tablespoon' => [
            'aliases' => ['tablespoon', 'tablespoons', 'tbsp', 'tbsp.'],
            'factor' => 0.98578431875,
            'unit' => 'msk',
            'category' => 'volume',
        ],
        'teaspoon' => [
            'aliases' => ['teaspoon', 'teaspoons', 'tsp', 'tsp.'],
            'factor' => 0.98578431875,
            'unit' => 'tsk',
            'category' => 'volume',
        ],
        'pound' => [
            'aliases' => ['pound', 'pounds', 'lb', 'lbs', 'lb.'],
            'factor' => 453.59237,
            'unit' => 'g',
            'category' => 'weight',
        ],
        'fahrenheit' => [
            'aliases' => ['fahrenheit', 'f', '°f'],
            'unit' => '°C',
            'category' => 'temperature',
        ],
    ];

    /**
     * Convert an American recipe measurement to a Swedish metric measurement.
     *
     * @return array{value: float, unit: string, category: string, source_unit: string}
     */
    public function convert(float $value, string $unit): array
    {
        $sourceUnit = $this->normalizeUnit($unit);
        $definition = self::UNITS[$sourceUnit];

        $convertedValue = $sourceUnit === 'fahrenheit'
            ? ($value - 32) * 5 / 9
            : $value * $definition['factor'];

        return [
            'value' => $convertedValue,
            'unit' => $definition['unit'],
            'category' => $definition['category'],
            'source_unit' => $sourceUnit,
        ];
    }

    /**
     * @return array<string, array{label: string, category: string}>
     */
    public function supportedUnits(): array
    {
        $labels = [
            'cup' => 'Cup',
            'fluid_ounce' => 'Fluid ounce (fl oz, volym)',
            'ounce' => 'Ounce (oz, vikt)',
            'tablespoon' => 'Tablespoon (tbsp)',
            'teaspoon' => 'Teaspoon (tsp)',
            'pound' => 'Pound (lb)',
            'fahrenheit' => 'Fahrenheit (°F)',
        ];
        $units = [];

        foreach (self::UNITS as $key => $definition) {
            $units[$key] = [
                'label' => $labels[$key],
                'category' => $definition['category'],
            ];
        }

        return $units;
    }

    public function normalizeUnit(string $unit): string
    {
        $normalized = strtolower(trim($unit));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        foreach (self::UNITS as $key => $definition) {
            if ($normalized === $key || in_array($normalized, $definition['aliases'], true)) {
                return $key;
            }
        }

        throw new InvalidArgumentException('Måttenheten stöds inte.');
    }
}

