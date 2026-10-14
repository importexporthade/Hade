<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredImages;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'store_name', 'logo', 'header_image', 'announcement', 'whatsapp_number', 'whatsapp_message',
    'email', 'phone', 'facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url', 'footer_text',
])]
class StoreSetting extends Model
{
    use DeletesStoredImages;

    /**
     * The store keeps a single row of settings; it is created on first use.
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate();
    }

    /**
     * The wa.me link the "Pedir por WhatsApp" buttons open, or null without a number.
     */
    public function whatsappUrl(?string $message = null): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->whatsapp_number);

        if ($number === '') {
            return null;
        }

        $text = $message ?? $this->whatsapp_message;

        return 'https://wa.me/'.$number.($text ? '?text='.rawurlencode($text) : '');
    }

    /**
     * @return list<string>
     */
    protected function storedImageAttributes(): array
    {
        return ['logo', 'header_image'];
    }
}
