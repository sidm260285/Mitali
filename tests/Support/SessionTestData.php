<?php

namespace Tests\Support;

class SessionTestData
{
    public static function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Session '.uniqid(),
            'status' => 'upcoming',
            'form_fee' => 0,
            'ages' => [
                ['from_age' => 1, 'to_age' => 150, 'fee' => 3000],
            ],
            'batches' => [
                [
                    'start_hour' => 5,
                    'start_minute' => 20,
                    'start_period' => 'AM',
                    'end_hour' => 6,
                    'end_minute' => 0,
                    'end_period' => 'AM',
                    'buffer_time' => 10,
                    'max_size' => 30,
                ],
            ],
            'memberships' => [
                [
                    'card_name' => 'Gold Card',
                    'no_of_slot' => 1,
                    'membership_cost' => 500,
                ],
            ],
        ], $overrides);
    }
}
