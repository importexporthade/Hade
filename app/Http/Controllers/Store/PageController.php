<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless($page->isPublished(), 404);

        return view('store.pages.show', ['page' => $page]);
    }
}
