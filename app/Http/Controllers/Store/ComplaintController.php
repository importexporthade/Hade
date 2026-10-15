<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintRequest;
use App\Mail\ComplaintFiled;
use App\Models\Complaint;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The virtual complaints book every store in Peru must offer ("Libro de Reclamaciones").
 */
class ComplaintController extends Controller
{
    public function create(): View
    {
        return view('store.complaints.create', [
            'settings' => StoreSetting::current(),
        ]);
    }

    /**
     * Files the sheet and emails the customer a copy with its number. The sheet is kept
     * even if the email fails, since the record is what the law requires.
     */
    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $complaint = Complaint::file([
            ...$request->validated(),
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

        return redirect()
            ->route('complaints.create')
            ->with('complaint_code', $complaint->code())
            ->with('complaint_email', $complaint->email);
    }
}
