<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attendance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * URL Foto Wajah Masuk (Face ID Clock In)
     */
    public function getPhotoInUrlAttribute(): ?string
    {
        if (!$this->photo_in) {
            return null;
        }

        if (str_starts_with($this->photo_in, 'http')) {
            return $this->photo_in;
        }

        return Storage::disk('public')->url($this->photo_in);
    }

    /**
     * URL Foto Wajah Pulang (Face ID Clock Out)
     */
    public function getPhotoOutUrlAttribute(): ?string
    {
        if (!$this->photo_out) {
            return null;
        }

        if (str_starts_with($this->photo_out, 'http')) {
            return $this->photo_out;
        }

        return Storage::disk('public')->url($this->photo_out);
    }

    /**
     * Tautan Titik Lokasi Google Maps
     */
    public function getGoogleMapsUrlAttribute(): ?string
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }

        return null;
    }
}
