<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Lokasi "Rumah Pilah" di blok "Temukan Rumah Pilah Terdekat!".
 */
class DropOffPoint extends Model
{
    protected $fillable = [
        'name', 'slug', 'image_file', 'address', 'city', 'province',
        'latitude', 'longitude', 'opening_hours', 'phone',
        'whatsapp_number', 'maps_url', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /** Tautan tombol "Lihat di Maps"; jatuh ke pencarian alamat bila kosong. */
    public function mapsUrl(): string
    {
        if ($this->maps_url) {
            return $this->maps_url;
        }

        $query = $this->latitude && $this->longitude
            ? $this->latitude.','.$this->longitude
            : $this->name.' '.$this->address;

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($query);
    }

    /** Tautan tombol "Hubungi WhatsApp" dalam format wa.me (62...). */
    public function whatsappUrl(): ?string
    {
        if (! $this->whatsapp_number) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $this->whatsapp_number);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
