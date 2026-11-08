<?php

namespace App\Support;

class CameraImageSettings
{
    /**
     * Nilai awal sama dengan pengaturan sensor yang sudah dipakai kamera.
     *
     * @var array<string, int|bool>
     */
    public const DEFAULTS = [
        'brightness' => 0,
        'contrast' => 0,
        'saturation' => -1,
        'sharpness' => 0,
        'ae_level' => 0,
        'wb_mode' => 0,
        'hmirror' => false,
        'vflip' => false,
    ];

    /**
     * @param  array<string, mixed>|null  $input
     * @return array<string, int|bool>
     */
    public static function normalize(?array $input): array
    {
        $input ??= [];

        return [
            'brightness' => self::clamp($input['brightness'] ?? self::DEFAULTS['brightness'], -2, 2),
            'contrast' => self::clamp($input['contrast'] ?? self::DEFAULTS['contrast'], -2, 2),
            'saturation' => self::clamp($input['saturation'] ?? self::DEFAULTS['saturation'], -2, 2),
            'sharpness' => self::clamp($input['sharpness'] ?? self::DEFAULTS['sharpness'], -2, 2),
            'ae_level' => self::clamp($input['ae_level'] ?? self::DEFAULTS['ae_level'], -2, 2),
            'wb_mode' => self::clamp($input['wb_mode'] ?? self::DEFAULTS['wb_mode'], 0, 4),
            'hmirror' => filter_var($input['hmirror'] ?? self::DEFAULTS['hmirror'], FILTER_VALIDATE_BOOLEAN),
            'vflip' => filter_var($input['vflip'] ?? self::DEFAULTS['vflip'], FILTER_VALIDATE_BOOLEAN),
        ];
    }

    private static function clamp(mixed $value, int $min, int $max): int
    {
        $number = (int) $value;

        return max($min, min($max, $number));
    }
}
