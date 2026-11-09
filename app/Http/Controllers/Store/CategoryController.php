<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function show(Category $category): View
    {
        abort_unless($category->isShownInStore(), 404);

        return view('store.categories.show', [
            'category' => $category,
            'products' => $category->products()->visible()->orderBy('sort_order')->paginate(24),
        ]);
    }
}
