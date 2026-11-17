<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\FrequentQuestion;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;

class FrequentQuestionController extends Controller
{
    /**
     * Every active frequent question, in the owner's order. The page does not exist while
     * the section is turned off or has no active question.
     */
    public function __invoke(): View
    {
        $questions = FrequentQuestion::shownInStore();

        abort_if($questions->isEmpty(), 404);

        return view('store.frequent-questions', [
            'questions' => $questions,
            'whatsappUrl' => StoreSetting::current()->whatsappUrl(),
        ]);
    }
}
