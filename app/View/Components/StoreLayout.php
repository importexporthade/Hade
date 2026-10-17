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
    ) {
        $this->settings = StoreSetting::current();
        $this->categories = Category::query()->visible()->orderBy('sort_order')->get();
        $this->pages = Page::query()->published()->orderBy('sort_order')->get();
    }

    public function pageTitle(): string
    {
        return $this->title ? "{$this->title} - {$this->settings->store_name}" : $this->settings->store_name;
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
