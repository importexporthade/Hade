<x-store-layout :title="$page->title" :description="$page->content">
    <article class="store-container max-w-3xl pt-10">
        <h1 class="font-serif text-3xl">{{ $page->title }}</h1>
        <div class="rich-text mt-6">
            {!! str($page->content)->sanitizeHtml() !!}
        </div>
    </article>
</x-store-layout>
