<?php

namespace Tests\Unit;

use App\Services\SessionAgeValidator;
use App\Services\SessionBatchValidator;
use App\Services\SessionMembershipValidator;
use PHPUnit\Framework\TestCase;

class SessionValidatorsTest extends TestCase
{
    public function test_age_validator_accepts_full_coverage(): void
    {
        $validator = new SessionAgeValidator;

        $this->assertNull($validator->validate([
            ['from_age' => 1, 'to_age' => 10],
            ['from_age' => 11, 'to_age' => 150],
        ]));
    }

    public function test_age_validator_rejects_gaps_with_row_details(): void
    {
        $validator = new SessionAgeValidator;

        $error = $validator->validate([
            ['from_age' => 1, 'to_age' => 10],
            ['from_age' => 12, 'to_age' => 150],
        ]);

        $this->assertStringContainsString('Gap between age row 1', $error);
        $this->assertStringContainsString('age row 2', $error);
    }

    public function test_age_validator_rejects_overlap_with_row_details(): void
    {
        $validator = new SessionAgeValidator;

        $error = $validator->validate([
            ['from_age' => 1, 'to_age' => 15],
            ['from_age' => 10, 'to_age' => 150],
        ]);

        $this->assertStringContainsString('Age row 2', $error);
        $this->assertStringContainsString('overlaps with age row 1', $error);
    }

    public function test_batch_validator_allows_touching_boundaries(): void
    {
        $validator = new SessionBatchValidator;

        $this->assertNull($validator->validate([
            ['start_time' => '05:20:00', 'end_time' => '06:00:00'],
            ['start_time' => '06:00:00', 'end_time' => '07:00:00'],
        ]));
    }

    public function test_batch_validator_reports_overlapping_rows(): void
    {
        $validator = new SessionBatchValidator;

        $error = $validator->validate([
            ['start_time' => '08:00:00', 'end_time' => '09:00:00'],
            ['start_time' => '08:30:00', 'end_time' => '09:40:00'],
        ]);

        $this->assertStringContainsString('Batch row 2', $error);
        $this->assertStringContainsString('overlaps with batch row 1', $error);
    }

    public function test_membership_validator_rejects_duplicate_slots_with_row_details(): void
    {
        $validator = new SessionMembershipValidator;

        $error = $validator->validate([
            ['card_name' => 'Gold', 'no_of_slot' => 1],
            ['card_name' => 'Silver', 'no_of_slot' => 1],
        ]);

        $this->assertStringContainsString('Membership row 2 (Silver)', $error);
        $this->assertStringContainsString('row 1 (Gold)', $error);
    }

    public function test_membership_validator_rejects_duplicate_card_names_with_row_details(): void
    {
        $validator = new SessionMembershipValidator;

        $error = $validator->validate([
            ['card_name' => 'Gold', 'no_of_slot' => 1],
            ['card_name' => 'Gold', 'no_of_slot' => 2],
        ]);

        $this->assertStringContainsString('Membership row 2 (Gold)', $error);
        $this->assertStringContainsString('row 1 (Gold)', $error);
    }
}
