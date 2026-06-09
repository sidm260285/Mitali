<?php

namespace App\Services;

class SessionMembershipValidator
{
    public const ALLOWED_SLOTS = [1, 2, 3, 4, -1];

    public function validate(array $memberships): ?string
    {
        if (count($memberships) < 1) {
            return 'At least one membership card is required.';
        }

        $cardNames = [];
        $slots = [];

        foreach ($memberships as $index => $membership) {
            $row = $index + 1;
            $cardName = trim((string) ($membership['card_name'] ?? ''));
            $slot = (int) ($membership['no_of_slot'] ?? 0);
            $slotLabel = self::slotLabel($slot);

            if ($cardName === '') {
                return sprintf('Membership row %d: card name is required.', $row);
            }

            if (strlen($cardName) > 200) {
                return sprintf('Membership row %d (%s): card name must not exceed 200 characters.', $row, $cardName);
            }

            if (! in_array($slot, self::ALLOWED_SLOTS, true)) {
                return sprintf('Membership row %d (%s): invalid number of slots selected.', $row, $cardName);
            }

            if (isset($cardNames[$cardName])) {
                return sprintf(
                    'Membership row %d (%s): card name is already used in row %d (%s).',
                    $row,
                    $cardName,
                    $cardNames[$cardName]['row'],
                    $cardNames[$cardName]['name'],
                );
            }

            if (isset($slots[$slot])) {
                return sprintf(
                    'Membership row %d (%s): %s slot type is already used in row %d (%s).',
                    $row,
                    $cardName,
                    $slotLabel,
                    $slots[$slot]['row'],
                    $slots[$slot]['name'],
                );
            }

            $cardNames[$cardName] = ['row' => $row, 'name' => $cardName];
            $slots[$slot] = ['row' => $row, 'name' => $cardName];
        }

        return null;
    }

    public static function slotLabel(int $slot): string
    {
        return match ($slot) {
            -1 => 'Any',
            1 => '1 Slot',
            2 => '2 Slots',
            3 => '3 Slots',
            4 => '4 Slots',
            default => (string) $slot,
        };
    }
}
