<?php

namespace Tests\Feature;

use App\Filament\Resources\Complaints\Pages\ListComplaints;
use App\Filament\Resources\Complaints\Pages\ViewComplaint;
use App\Models\Complaint;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\ComplaintBookExport;
use App\Support\ComplaintSheetPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ComplaintDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-20 10:00:00');
        $this->actingAs(User::factory()->admin()->create());
        StoreSetting::current()->update(['legal_name' => 'Hade Import Export S.A.C.', 'ruc' => '20612345678']);
    }

    public function test_the_sheet_downloads_as_a_pdf_from_its_page(): void
    {
        $complaint = Complaint::factory()->create(['sequence' => 3]);

        Livewire::test(ViewComplaint::class, ['record' => $complaint->getKey()])
            ->callAction('downloadPdf')
            ->assertFileDownloaded('hoja-de-reclamacion-000003-2026.pdf');

        $this->assertStringStartsWith('%PDF', ComplaintSheetPdf::render($complaint));
    }

    public function test_the_pdf_carries_the_sheet_and_the_store_answer(): void
    {
        $complaint = Complaint::factory()->answered()->create(['sequence' => 3, 'consumer_name' => 'María Pérez Quispe']);

        $html = view('pdf.complaint', ['complaint' => $complaint, 'settings' => StoreSetting::current()])->render();

        $this->assertStringContainsString('N° 000003-2026', $html);
        $this->assertStringContainsString('Hade Import Export S.A.C.', $html);
        $this->assertStringContainsString('RUC 20612345678', $html);
        $this->assertStringContainsString('María Pérez Quispe', $html);
        $this->assertStringContainsString('Te enviamos un kit nuevo sin costo.', $html);
        $this->assertStringContainsString('INDECOPI', $html);
    }

    public function test_the_book_exports_the_sheets_of_a_period_for_excel(): void
    {
        Complaint::factory()->create(['sequence' => 1, 'consumer_name' => 'Fuera del rango', 'created_at' => '2026-08-30 12:00:00']);
        Complaint::factory()->answered()->create(['sequence' => 2, 'consumer_name' => 'Ana Díaz', 'created_at' => '2026-09-01 09:00:00']);
        Complaint::factory()->create(['sequence' => 3, 'consumer_name' => 'Rosa Ñique', 'created_at' => '2026-10-19 18:00:00']);

        $csv = ComplaintBookExport::csv(Carbon::parse('2026-09-01'), Carbon::parse('2026-10-19'));
        $lines = explode("\n", trim($csv));

        $this->assertStringStartsWith("\u{FEFF}N°,Fecha,Tipo,Nombre", $csv);
        $this->assertCount(3, $lines);
        $this->assertStringContainsString('000002-2026,"01/09/2026 09:00",Reclamo,"Ana Díaz"', $lines[1]);
        $this->assertStringContainsString('Respondida', $lines[1]);
        $this->assertStringContainsString('"Rosa Ñique"', $lines[2]);
        $this->assertStringContainsString('"Por responder"', $lines[2]);
        $this->assertStringNotContainsString('Fuera del rango', $csv);
    }

    public function test_the_export_downloads_from_the_book_list(): void
    {
        Livewire::test(ListComplaints::class)
            ->callAction('export', data: ['from' => '2026-01-01', 'until' => '2026-10-20'])
            ->assertHasNoActionErrors()
            ->assertFileDownloaded('libro-de-reclamaciones-2026-01-01-al-2026-10-20.csv');
    }

    public function test_the_export_period_cannot_end_before_it_starts(): void
    {
        Livewire::test(ListComplaints::class)
            ->callAction('export', data: ['from' => '2026-10-01', 'until' => '2026-09-01'])
            ->assertHasActionErrors(['until' => 'after_or_equal']);
    }
}
