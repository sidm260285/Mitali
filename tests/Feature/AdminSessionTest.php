<?php

namespace Tests\Feature;

use App\Models\Session;
use App\Models\SessionAge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SessionTestData;
use Tests\TestCase;

class AdminSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_view_session_list_with_tabs(): void
    {
        Session::factory()->create(['name' => 'Upcoming Session']);
        Session::factory()->current()->create(['name' => 'Current Session']);

        $this->actingAs($this->admin)
            ->get(route('admin.sessions.index', ['tab' => 'current']))
            ->assertOk()
            ->assertSee('Session Master')
            ->assertSee('Current Session')
            ->assertSee('Upcoming (1)')
            ->assertSee('Current (1)');
    }

    public function test_admin_can_create_session(): void
    {
        $payload = SessionTestData::validPayload(['name' => 'Session 2025']);

        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), $payload)
            ->assertRedirect(route('admin.sessions.index', ['tab' => 'upcoming']))
            ->assertSessionHas('success');

        $session = Session::where('name', 'Session 2025')->first();
        $this->assertNotNull($session);
        $this->assertSame('upcoming', $session->status);
        $this->assertCount(1, $session->ages);
        $this->assertSame('2026-01-15', $session->from_date->toDateString());
        $this->assertSame('2026-06-15', $session->to_date->toDateString());
        $this->assertSame('2026-09-15', $session->extended_date->toDateString());
        $this->assertSame('400.00', $session->ages->first()->extended_fee);
        $this->assertSame('200.00', $session->ages->first()->extended_fee_late);
        $this->assertCount(1, $session->batches);
        $this->assertCount(1, $session->memberships);
    }

    public function test_session_creation_rejects_invalid_age_coverage(): void
    {
        $payload = SessionTestData::validPayload([
            'ages' => [
                ['from_age' => 1, 'to_age' => 100, 'fee' => 3000],
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), $payload);

        $response->assertSessionHasErrors('ages');
        $response->assertSessionHasErrors([
            'ages' => 'Age ranges must cover up to 150. Row 1 (1–100) is the last range but does not end at 150.',
        ]);
    }

    public function test_session_creation_rejects_overlapping_batches(): void
    {
        $payload = SessionTestData::validPayload([
            'batches' => [
                [
                    'start_hour' => 8, 'start_minute' => 0, 'start_period' => 'AM',
                    'end_hour' => 9, 'end_minute' => 0, 'end_period' => 'AM',
                    'buffer_time' => 10, 'max_size' => 30,
                ],
                [
                    'start_hour' => 8, 'start_minute' => 30, 'start_period' => 'AM',
                    'end_hour' => 9, 'end_minute' => 40, 'end_period' => 'AM',
                    'buffer_time' => 10, 'max_size' => 30,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), $payload);

        $response->assertSessionHasErrors('batches');
        $response->assertSessionHasErrors([
            'batches' => 'Batch row 2 (8:30 AM – 9:40 AM) overlaps with batch row 1 (8:00 AM – 9:00 AM).',
        ]);
    }

    public function test_session_creation_rejects_duplicate_membership_slots(): void
    {
        $payload = SessionTestData::validPayload([
            'memberships' => [
                ['card_name' => 'Gold', 'no_of_slot' => 2, 'membership_cost' => 500],
                ['card_name' => 'Silver', 'no_of_slot' => 2, 'membership_cost' => 400],
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), $payload);

        $response->assertSessionHasErrors('memberships');
        $response->assertSessionHasErrors([
            'memberships' => 'Membership row 2 (Silver): 2 Slots slot type is already used in row 1 (Gold).',
        ]);
    }

    public function test_upcoming_session_can_become_current_when_no_other_current(): void
    {
        $session = Session::factory()->create(['status' => 'upcoming']);
        SessionAge::factory()->create(['session_id' => $session->id]);
        $session->load(['ages', 'batches', 'memberships']);

        $payload = $this->payloadFromSession($session, ['status' => 'current']);

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $session), $payload)
            ->assertRedirect(route('admin.sessions.index', ['tab' => 'current']))
            ->assertSessionHas('success');

        $this->assertSame('current', $session->fresh()->status);
    }

    public function test_cannot_set_second_current_session(): void
    {
        Session::factory()->current()->create();
        $session = Session::factory()->create(['status' => 'upcoming']);
        SessionAge::factory()->create(['session_id' => $session->id]);
        $session->load(['ages', 'batches', 'memberships']);

        $payload = $this->payloadFromSession($session, ['status' => 'current']);

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $session), $payload)
            ->assertSessionHasErrors('status');
    }

    public function test_current_session_can_move_to_over_when_admissions_exist(): void
    {
        $session = Session::factory()->current()->create(['admission_count' => 2]);
        SessionAge::factory()->create(['session_id' => $session->id]);
        $session->load(['ages', 'batches', 'memberships']);

        $payload = $this->payloadFromSession($session, ['status' => 'over']);

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $session), $payload)
            ->assertRedirect(route('admin.sessions.index', ['tab' => 'over']));

        $this->assertSame('over', $session->fresh()->status);
    }

    public function test_cannot_mark_session_over_without_admissions(): void
    {
        $session = Session::factory()->current()->create(['admission_count' => 0]);
        SessionAge::factory()->create(['session_id' => $session->id]);
        $session->load(['ages', 'batches', 'memberships']);

        $payload = $this->payloadFromSession($session, ['status' => 'over']);

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $session), $payload)
            ->assertSessionHasErrors('status');
    }

    public function test_over_session_edit_allows_status_only(): void
    {
        $session = Session::factory()->over()->create();
        SessionAge::factory()->create(['session_id' => $session->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.sessions.edit', $session))
            ->assertOk()
            ->assertSee('Only status can be changed');

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $session), ['status' => 'over'])
            ->assertRedirect();
    }

    public function test_session_can_be_deleted_when_counts_are_zero(): void
    {
        $session = Session::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.sessions.destroy', $session))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('training_sessions', ['id' => $session->id]);
    }

    public function test_session_cannot_be_deleted_with_admissions(): void
    {
        $session = Session::factory()->create(['admission_count' => 1]);

        $this->actingAs($this->admin)
            ->delete(route('admin.sessions.destroy', $session))
            ->assertSessionHasErrors('session');
    }

    public function test_frozen_age_row_cannot_be_modified(): void
    {
        $session = Session::factory()->create(['status' => 'upcoming']);
        $age = SessionAge::factory()->create([
            'session_id' => $session->id,
            'from_age' => 1,
            'to_age' => 150,
            'fee' => 3000,
            'admission_counter' => 2,
        ]);
        $session->load(['ages', 'batches', 'memberships']);

        $payload = $this->payloadFromSession($session);
        $payload['ages'][0]['fee'] = 9999;

        $response = $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $session), $payload);

        $response->assertSessionHasErrors('ages');
        $response->assertSessionHasErrors([
            'ages' => 'Age row 1 (1–150): cannot be modified because it has admissions.',
        ]);

        $this->assertSame('3000.00', $age->fresh()->fee);
    }

    public function test_session_dates_must_be_one_month_apart_in_the_same_year(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.sessions.create'))
            ->assertOk()
            ->assertSee('From Date')
            ->assertSee('To Date')
            ->assertSee('Extended Date')
            ->assertSee('Extended Fee Late');

        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), SessionTestData::validPayload([
                'from_date' => '2026-05-16',
                'to_date' => '2026-06-15',
                'extended_date' => '2026-08-15',
            ]))
            ->assertSessionHasErrors([
                'from_date' => 'From Date must be at least 1 month before To Date.',
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), SessionTestData::validPayload([
                'name' => 'Boundary Session',
                'from_date' => '2026-05-15',
                'to_date' => '2026-06-15',
                'extended_date' => '2026-07-15',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('training_sessions', [
            'name' => 'Boundary Session',
            'from_date' => '2026-05-15',
            'to_date' => '2026-06-15',
            'extended_date' => '2026-07-15',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), SessionTestData::validPayload([
                'from_date' => '2026-01-15',
                'to_date' => '2026-06-15',
                'extended_date' => '2027-01-15',
            ]))
            ->assertSessionHasErrors([
                'from_date' => 'From Date, To Date, and Extended Date must be in the same year.',
            ]);
    }

    public function test_age_fees_must_be_greater_than_zero(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), SessionTestData::validPayload([
                'ages' => [[
                    'from_age' => 1,
                    'to_age' => 150,
                    'fee' => 0,
                    'extended_fee' => 400,
                    'extended_fee_late' => 200,
                ]],
            ]))
            ->assertSessionHasErrors('ages.0.fee');

        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), SessionTestData::validPayload([
                'ages' => [[
                    'from_age' => 1,
                    'to_age' => 150,
                    'fee' => 10.5,
                    'extended_fee' => 20.25,
                    'extended_fee_late' => 0,
                ]],
            ]))
            ->assertSessionHasErrors('ages.0.extended_fee_late');
    }

    public function test_edit_updates_dates_and_extended_fees(): void
    {
        $session = Session::factory()->create(['name' => 'Editable Session']);
        SessionAge::factory()->create(['session_id' => $session->id]);
        $session->load(['ages', 'batches', 'memberships']);

        $payload = $this->payloadFromSession($session, [
            'from_date' => '2026-02-01',
            'to_date' => '2026-05-01',
            'extended_date' => '2026-08-01',
        ]);
        $payload['ages'][0]['extended_fee'] = 750.5;
        $payload['ages'][0]['extended_fee_late'] = 125.25;

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $session), $payload)
            ->assertRedirect();

        $session->refresh();
        $this->assertSame('2026-02-01', $session->from_date->toDateString());
        $this->assertSame('2026-05-01', $session->to_date->toDateString());
        $this->assertSame('2026-08-01', $session->extended_date->toDateString());
        $this->assertSame('750.50', $session->ages->first()->extended_fee);
        $this->assertSame('125.25', $session->ages->first()->extended_fee_late);
    }

    public function test_frozen_age_keeps_zero_extended_fees_and_rejects_changes(): void
    {
        $session = Session::factory()->create();
        $age = SessionAge::factory()->create([
            'session_id' => $session->id,
            'from_age' => 1,
            'to_age' => 150,
            'fee' => 3000,
            'extended_fee' => 0,
            'extended_fee_late' => 0,
            'admission_counter' => 2,
        ]);
        $session->load(['ages', 'batches', 'memberships']);

        $payload = $this->payloadFromSession($session);

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $session), $payload)
            ->assertRedirect();

        $this->assertSame('0.00', $age->fresh()->extended_fee);

        $payload['ages'][0]['extended_fee_late'] = 99;

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $session), $payload)
            ->assertSessionHasErrors('ages');

        $this->assertSame('0.00', $age->fresh()->extended_fee_late);
    }

    public function test_session_list_combines_name_dates_status_and_created(): void
    {
        $session = Session::factory()->create([
            'name' => 'Dated Session',
            'from_date' => '2026-01-15',
            'to_date' => '2026-06-15',
            'extended_date' => '2026-09-15',
        ]);
        Session::factory()->create(['name' => 'Undated Session']);

        $this->actingAs($this->admin)
            ->get(route('admin.sessions.index', ['tab' => 'upcoming']))
            ->assertOk()
            ->assertSee('Session')
            ->assertDontSee('>Name<', false)
            ->assertSee('Dated Session')
            ->assertSee('From: 15 Jan 2026')
            ->assertSee('To: 15 Jun 2026')
            ->assertSee('Extended: 15 Sep 2026')
            ->assertSee('Upcoming')
            ->assertSee('Created '.$session->created_at->format('d M Y'))
            ->assertSee('Undated Session')
            ->assertSee('From: —');
    }

    public function test_over_session_edit_does_not_ask_for_dates(): void
    {
        $session = Session::factory()->over()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.sessions.edit', $session))
            ->assertOk()
            ->assertDontSee('From Date')
            ->assertSee('Only status can be changed');
    }

    public function test_executive_cannot_access_sessions(): void
    {
        $executive = User::factory()->executive()->create();

        $this->actingAs($executive)
            ->get(route('admin.sessions.index'))
            ->assertForbidden();
    }

    private function payloadFromSession(Session $session, array $overrides = []): array
    {
        $session->loadMissing(['ages', 'batches', 'memberships']);

        $payload = SessionTestData::validPayload([
            'name' => $session->name,
            'status' => $session->status,
            'ages' => $session->ages->map(fn ($age) => [
                'id' => $age->id,
                'from_age' => $age->from_age,
                'to_age' => $age->to_age,
                'fee' => $age->fee,
                'extended_fee' => $age->extended_fee,
                'extended_fee_late' => $age->extended_fee_late,
            ])->toArray(),
            'memberships' => $session->memberships->map(fn ($m) => [
                'id' => $m->id,
                'card_name' => $m->card_name,
                'no_of_slot' => $m->no_of_slot,
                'membership_cost' => $m->membership_cost,
            ])->toArray(),
        ]);

        if ($session->batches->isNotEmpty()) {
            $payload['batches'] = $session->batches->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'start_hour' => 5,
                    'start_minute' => 20,
                    'start_period' => 'AM',
                    'end_hour' => 6,
                    'end_minute' => 0,
                    'end_period' => 'AM',
                    'buffer_time' => $batch->buffer_time,
                    'max_size' => $batch->max_size,
                ];
            })->toArray();
        }

        return array_replace_recursive($payload, $overrides);
    }
}
