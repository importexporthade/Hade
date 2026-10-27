<?php

namespace App\View\Components;

use App\Models\Category;
use App\Models\Page;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;

/**
 * The storefront frame: photo band, logo, menu, footer, cart drawer and WhatsApp button,
 * all fed by what the owner set in the manager.
 */
class StoreLayout extends Component
{
    public StoreSetting $settings;

    /** @var Collection<int, Category> */
    public Collection $categories;

    /** @var Collection<int, Page> */
    public Collection $pages;

    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $price = null,
        public bool $indexable = true,
    ) {
        $this->settings = StoreSetting::current();
        $this->categories = Category::query()->visible()->orderBy('sort_order')->get();
        $this->pages = Page::query()->published()->orderBy('sort_order')->get();
    }

    public function pageTitle(): string
    {
        return $this->title ? "{$this->title} - {$this->settings->store_name}" : $this->settings->store_name;
    }

    /**
     * The summary search engines and chats show under the link, falling back to the store's
     * own description from the footer. Rich text arrives as HTML, so each tag becomes a space
     * to keep "Incluye</p><ul><li>1 blister" from running together as "Incluye1 blister".
     */
    public function metaDescription(): ?string
    {
        $description = html_entity_decode(strip_tags(str_replace('<', ' <', (string) ($this->description ?: $this->settings->footer_text))));

        return filled($description) ? str($description)->squish()->limit(155)->toString() : null;
    }

    /**
     * The picture WhatsApp, Facebook and Instagram show for a shared link, as a full address.
     * They cannot show SVG, so the product placeholder gives way to the logo or the hero photo.
     */
    public function shareImage(): string
    {
        $image = $this->image && ! str_ends_with($this->image, '.svg')
            ? $this->image
            : ($this->publicUrl($this->settings->logo) ?? asset('images/hero-nail-stickers.jpg'));

        return url($image);
    }

    public function publicUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function render(): View
    {
        return view('components.store-layout');
    }
}
