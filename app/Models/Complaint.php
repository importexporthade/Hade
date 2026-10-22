<?php

namespace App\Models;

use App\Enums\ComplaintAlert;
use App\Enums\ComplaintItemType;
use App\Enums\ComplaintType;
use App\Enums\IdentityDocument;
use Carbon\CarbonInterface;
use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * One sheet of the store's complaints book ("hoja de reclamación").
 */
#[Fillable([
    'type', 'consumer_name', 'document_type', 'document_number', 'address', 'phone', 'email',
    'is_minor', 'guardian_name', 'item_type', 'amount', 'item_description', 'detail', 'consumer_request',
])]
class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    /**
     * Calendar days the store has to answer, counted from the day the sheet is filed. The law
     * allows fifteen business days; counting every day keeps the store ahead of that deadline.
     */
    public const int RESPONSE_DAYS = 15;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ComplaintType::class,
            'document_type' => IdentityDocument::class,
            'item_type' => ComplaintItemType::class,
            'is_minor' => 'boolean',
            'amount' => 'decimal:2',
            'responded_at' => 'datetime',
            'last_alert' => ComplaintAlert::class,
        ];
    }

    /**
     * Files a new sheet with the next number of the current year. Two customers filing at
     * the same moment cannot share a number: the unique index refuses the second one and
     * it takes the following number.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function file(array $attributes): self
    {
        $year = (int) now()->year;

        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($attributes, $year): self {
                    $complaint = new self($attributes);
                    $complaint->year = $year;
                    $complaint->sequence = (int) self::query()->where('year', $year)->max('sequence') + 1;
                    $complaint->save();

                    return $complaint;
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= 3) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * The sheet number as printed: "000012-2026".
     */
    public function code(): string
    {
        return sprintf('%06d-%d', $this->sequence, $this->year);
    }

    /**
     * Where the customer downloads her copy as a PDF. The link is signed, so only someone who
     * got it (on the receipt page or in the copy email) can open that sheet.
     */
    public function receiptUrl(): string
    {
        return URL::signedRoute('complaints.receipt', ['complaint' => $this]);
    }

    public function isAnswered(): bool
    {
        return $this->responded_at !== null;
    }

    /**
     * Last day to answer: fifteen calendar days after filing, weekends and holidays included.
     */
    public function responseDueDate(): CarbonInterface
    {
        return $this->created_at->copy()->addDays(self::RESPONSE_DAYS)->startOfDay();
    }

    public function respond(string $response): void
    {
        $this->forceFill([
            'response' => $response,
            'responded_at' => now(),
        ])->save();
    }

    /**
     * @param  Builder<Complaint>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('responded_at');
    }
}
