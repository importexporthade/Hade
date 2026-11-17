<?php

namespace App\View\Components;

use App\Models\Category;
use App\Models\FrequentQuestion;
use App\Models\Page;
use App\Models\StoreSetting;
use App\Support\ImageOptimizer;
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

    /** Whether the menu and footer link to "Preguntas frecuentes". */
    public bool $showsFrequentQuestions;

    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        /** A photo on the public disk to show when the page is shared. */
        public ?string $image = null,
        public ?string $price = null,
        public bool $indexable = true,
    ) {
        $this->settings = StoreSetting::current();
        $this->categories = Category::query()->visible()->orderBy('sort_order')->get();
        $this->pages = Page::query()->published()->orderBy('sort_order')->get();
        $this->showsFrequentQuestions = FrequentQuestion::sectionIsShown();
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

    /** The store picture shared links show when the page has no photo of its own. */
    public const string DEFAULT_SHARE_IMAGE = 'images/share.jpg';

    /**
     * The picture WhatsApp, Facebook and Instagram show for a shared link, as a full address.
     * The page's photo (a path on the public disk) goes out as a light JPEG copy, since
     * those apps do not reliably show WebP and drop heavy pictures; without one, the logo or
     * the store picture is shown.
     */
    public function shareImage(): string
    {
        $sharePath = collect([$this->image, $this->settings->logo])
            ->filter()
            ->map(fn (string $path): ?string => ImageOptimizer::shareCopy($path))
            ->first(fn (?string $path): bool => $path !== null);

        return url($sharePath ? Storage::disk('public')->url($sharePath) : asset(self::DEFAULT_SHARE_IMAGE));
    }

    /**
     * Width and height of the shared picture when it is the store one, so the apps can draw
     * the large preview the first time the link is shared, before downloading it.
     *
     * @return array{width: int, height: int}|null
     */
    public function shareImageSize(): ?array
    {
        return $this->shareImage() === url(asset(self::DEFAULT_SHARE_IMAGE)) ? ['width' => 1200, 'height' => 630] : null;
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
