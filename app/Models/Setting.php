<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'string'];

    // ── Lectura ───────────────────────────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    // ── Escritura (invalida cache automáticamente) ────────────────────────────

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    // ── Helpers de estado ─────────────────────────────────────────────────────

    public static function isConfigured(): bool
    {
        return static::get('center_configured', '0') === '1';
    }

    public static function centerName(): string
    {
        return static::get('center_name', config('app.center_name', 'Mi Centro'));
    }
}
