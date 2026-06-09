<?php

namespace App\Services;

use App\Models\Session;

class SessionStatusValidator
{
    public function allowedStatusesForCreate(): array
    {
        return [Session::STATUS_UPCOMING];
    }

    public function allowedNextStatuses(Session $session): array
    {
        return match ($session->status) {
            Session::STATUS_UPCOMING => [Session::STATUS_UPCOMING, Session::STATUS_CURRENT],
            Session::STATUS_CURRENT => [Session::STATUS_CURRENT, Session::STATUS_UPCOMING, Session::STATUS_OVER],
            Session::STATUS_OVER => [Session::STATUS_OVER, Session::STATUS_CURRENT],
            default => [],
        };
    }

    public function validateTransition(Session $session, string $newStatus, ?int $excludeSessionId = null): ?string
    {
        if (! in_array($newStatus, [Session::STATUS_UPCOMING, Session::STATUS_CURRENT, Session::STATUS_OVER], true)) {
            return 'Invalid session status.';
        }

        if ($session->exists && ! in_array($newStatus, $this->allowedNextStatuses($session), true)) {
            return 'Invalid status transition.';
        }

        if ($newStatus === Session::STATUS_OVER) {
            if ($session->admission_count <= 0) {
                return 'A session can only be marked as over when it has admissions.';
            }

            if ($session->status !== Session::STATUS_CURRENT) {
                return 'Only a current session can be marked as over.';
            }
        }

        if ($newStatus === Session::STATUS_UPCOMING && $session->attendance_count > 0) {
            return 'A session with attendance cannot be changed to upcoming.';
        }

        if ($newStatus === Session::STATUS_CURRENT) {
            if ($session->attendance_count > 0) {
                return 'A session with attendance cannot be changed to current.';
            }

            $existingCurrent = Session::query()
                ->where('status', Session::STATUS_CURRENT)
                ->when($excludeSessionId, fn ($query) => $query->where('id', '!=', $excludeSessionId))
                ->exists();

            if ($existingCurrent) {
                return 'Another session is already current. Please change it first.';
            }
        }

        return null;
    }
}
