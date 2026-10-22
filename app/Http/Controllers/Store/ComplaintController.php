<?php

namespace App\Http\Controllers\Store;

use App\Enums\ComplaintAlert;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintRequest;
use App\Mail\ComplaintFiled;
use App\Models\Complaint;
use App\Models\StoreSetting;
use App\Support\ComplaintAlerts;
use App\Support\ComplaintSheetPdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * The virtual complaints book every store in Peru must offer ("Libro de Reclamaciones").
 */
class ComplaintController extends Controller
{
    /**
     * The form, and right after filing, the customer's receipt with everything she sent.
     */
    public function create(): View
    {
        return view('store.complaints.create', [
            'settings' => StoreSetting::current(),
            'filed' => session()->has('filed_complaint_id') ? Complaint::query()->find(session('filed_complaint_id')) : null,
        ]);
    }

    /**
     * The customer's copy as a PDF, opened from the signed link on the receipt or in her email.
     */
    public function receipt(Complaint $complaint): StreamedResponse
    {
        return ComplaintSheetPdf::download($complaint);
    }

    /**
     * Files the sheet and emails the customer a copy with its number. The sheet is kept
     * even if the email fails, since the record is what the law requires.
     */
    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $complaint = Complaint::file([
            ...$request->safe()->except('privacy'),
            'is_minor' => $request->boolean('is_minor'),
            'guardian_name' => $request->boolean('is_minor') ? $request->validated('guardian_name') : null,
        ]);

        $storeEmail = StoreSetting::current()->email;

        try {
            Mail::to($complaint->email)
                ->bcc(array_filter([$storeEmail]))
                ->send(new ComplaintFiled($complaint));
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            ComplaintAlerts::send($complaint, ComplaintAlert::Filed, alreadyNotified: array_filter([$storeEmail]));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('complaints.create')
            ->with('filed_complaint_id', $complaint->getKey());
    }
}
