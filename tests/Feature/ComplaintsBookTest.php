<?php

namespace Tests\Feature;

use App\Enums\ComplaintType;
use App\Filament\Resources\Complaints\Pages\ListComplaints;
use App\Filament\Resources\Complaints\Pages\ViewComplaint;
use App\Mail\ComplaintAnswered;
use App\Mail\ComplaintFiled;
use App\Models\Complaint;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ComplaintsBookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();
    }

    public function test_the_form_shows_the_store_legal_details(): void
    {
        StoreSetting::current()->update([
            'legal_name' => 'Hade Import Export S.A.C.',
            'ruc' => '20612345678',
            'legal_address' => 'Av. Larco 123, Miraflores, Lima',
        ]);

        $this->get('/information/complaints')
            ->assertOk()
            ->assertSee('Hade Import Export S.A.C.')
            ->assertSee('20612345678')
            ->assertSee('Av. Larco 123, Miraflores, Lima')
            ->assertSee('La formulación del reclamo no impide acudir a otras vías');
    }

    public function test_every_store_page_links_to_the_complaints_book(): void
    {
        $this->get('/products')->assertOk()->assertSee(route('complaints.create'), false);
    }

    public function test_a_customer_files_a_claim_and_gets_its_number_and_a_copy(): void
    {
        StoreSetting::current()->update(['email' => 'import.export.hade@gmail.com']);

        $this->post('/information/complaints', $this->validSheet())
            ->assertRedirect('/information/complaints')
            ->assertSessionHas('complaint_code', '000001-'.now()->year);

        $complaint = Complaint::query()->sole();
        $this->assertSame(ComplaintType::Claim, $complaint->type);
        $this->assertSame('María Pérez Quispe', $complaint->consumer_name);
        $this->assertSame('76.90', $complaint->amount);
        $this->assertFalse($complaint->is_minor);
        $this->assertNull($complaint->guardian_name);

        Mail::assertSent(ComplaintFiled::class, fn (ComplaintFiled $mail): bool => $mail->hasTo('maria@example.com')
            && $mail->hasBcc('import.export.hade@gmail.com')
            && $mail->complaint->is($complaint));
    }

    public function test_the_copy_carries_the_sheet_details_and_the_legal_notice(): void
    {
        $complaint = Complaint::factory()->create(['sequence' => 7, 'consumer_name' => 'María Pérez Quispe']);

        $rendered = (new ComplaintFiled($complaint))->render();

        $this->assertStringContainsString('000007-'.now()->year, $rendered);
        $this->assertStringContainsString('María Pérez Quispe', $rendered);
        $this->assertStringContainsString('INDECOPI', $rendered);
    }

    public function test_sheets_are_numbered_in_order_and_start_again_each_year(): void
    {
        Carbon::setTestNow('2026-12-31 10:00');
        $this->post('/information/complaints', $this->validSheet());
        $this->post('/information/complaints', $this->validSheet());

        Carbon::setTestNow('2027-01-02 10:00');
        $this->post('/information/complaints', $this->validSheet());

        $this->assertSame(
            ['000001-2026', '000002-2026', '000001-2027'],
            Complaint::query()->orderBy('id')->get()->map->code()->all(),
        );
    }

    public function test_a_minor_must_name_a_parent_or_guardian(): void
    {
        $this->post('/information/complaints', [...$this->validSheet(), 'is_minor' => '1'])
            ->assertSessionHasErrors(['guardian_name' => 'Completa el nombre del padre, madre o apoderado.']);

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_the_document_number_must_match_its_type(): void
    {
        $this->post('/information/complaints', [...$this->validSheet(), 'document_number' => '1234'])
            ->assertSessionHasErrors(['document_number' => 'El número no es un DNI válido.']);
    }

    public function test_an_empty_sheet_lists_every_required_field_in_spanish(): void
    {
        $this->post('/information/complaints', [])
            ->assertSessionHasErrors([
                'consumer_name' => 'Completa tu nombre completo.',
                'email' => 'Completa tu correo.',
                'detail' => 'Completa el detalle.',
                'consumer_request' => 'Completa tu pedido.',
            ]);
    }

    public function test_the_deadline_is_fifteen_business_days_after_filing(): void
    {
        $complaint = Complaint::factory()->create(['created_at' => '2026-10-15 16:00']);

        $this->assertSame('2026-11-05', $complaint->responseDueDate()->toDateString());
    }

    public function test_the_owner_answers_a_sheet_and_the_customer_gets_the_answer(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $complaint = Complaint::factory()->create(['email' => 'maria@example.com']);

        Livewire::test(ViewComplaint::class, ['record' => $complaint->getRouteKey()])
            ->callAction('respond', data: ['response' => 'Te enviamos un kit nuevo sin costo esta semana.'])
            ->assertHasNoFormErrors();

        $complaint->refresh();
        $this->assertTrue($complaint->isAnswered());
        $this->assertSame('Te enviamos un kit nuevo sin costo esta semana.', $complaint->response);
        Mail::assertSent(ComplaintAnswered::class, fn (ComplaintAnswered $mail): bool => $mail->hasTo('maria@example.com'));
    }

    public function test_an_answered_sheet_cannot_be_answered_again(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $complaint = Complaint::factory()->answered()->create();

        Livewire::test(ViewComplaint::class, ['record' => $complaint->getRouteKey()])
            ->assertActionHidden('respond');
    }

    public function test_the_book_lists_sheets_and_offers_no_way_to_edit_or_delete_them(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $pending = Complaint::factory()->create();
        $answered = Complaint::factory()->answered()->create();

        Livewire::test(ListComplaints::class)
            ->assertCanSeeTableRecords([$pending, $answered])
            ->filterTable('answered', false)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$answered]);

        $this->get("/manager/complaints/{$pending->id}/edit")->assertNotFound();
    }

    /**
     * @return array<string, string>
     */
    private function validSheet(): array
    {
        return [
            'type' => 'reclamo',
            'consumer_name' => 'María Pérez Quispe',
            'document_type' => 'dni',
            'document_number' => '45678912',
            'address' => 'Jr. Las Flores 456, Surco, Lima',
            'phone' => '987 654 321',
            'email' => 'maria@example.com',
            'is_minor' => '0',
            'item_type' => 'producto',
            'amount' => '76.90',
            'item_description' => 'Kit Hade Recargada',
            'detail' => 'La lámpara del kit llegó sin cargar y no enciende.',
            'consumer_request' => 'Cambio de la lámpara.',
        ];
    }
}
