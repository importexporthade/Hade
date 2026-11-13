<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredImages;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'store_name', 'legal_name', 'ruc', 'legal_address', 'logo', 'header_image', 'announcement', 'whatsapp_number', 'whatsapp_message',
    'email', 'phone', 'facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url', 'footer_text',
    'shipping_capital', 'shipping_province',
])]
class StoreSetting extends Model
{
    use DeletesStoredImages;

    /**
     * Mirrors the column default so a row created on first use already carries the name.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'store_name' => 'Hade',
    ];

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
     * The owner's opening message, which also starts every order sent from the store, or
     * null when they have not written one.
     */
    public function whatsappGreeting(): ?string
    {
        $greeting = trim((string) $this->whatsapp_message);

        return $greeting !== '' ? $greeting : null;
    }

    /**
     * @return list<string>
     */
    protected function storedImageAttributes(): array
    {
        return ['logo', 'header_image'];
    }
}
