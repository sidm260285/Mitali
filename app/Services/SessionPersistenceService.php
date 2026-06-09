<?php

namespace App\Services;

use App\Models\Session;
use App\Models\SessionAge;
use App\Models\SessionBatch;
use App\Models\SessionMembership;
use App\Support\TimeHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SessionPersistenceService
{
    public function __construct(
        private SessionAgeValidator $ageValidator,
        private SessionBatchValidator $batchValidator,
        private SessionMembershipValidator $membershipValidator,
        private SessionStatusValidator $statusValidator,
    ) {}

    public function create(array $data): Session
    {
        $this->validateChildData($data);
        $this->assertStatus($data['status'] ?? Session::STATUS_UPCOMING, new Session);

        return DB::transaction(function () use ($data) {
            $session = Session::create([
                'name' => $data['name'],
                'status' => Session::STATUS_UPCOMING,
            ]);

            $this->syncChildren($session, $data, collect(), collect(), collect());

            return $session->load(['ages', 'batches', 'memberships']);
        });
    }

    public function update(Session $session, array $data): Session
    {
        if ($session->isOver()) {
            return $this->updateOverSession($session, $data);
        }

        $this->validateChildData($data);
        $this->assertStatus($data['status'], $session);
        $this->assertFrozenRows($session, $data);

        return DB::transaction(function () use ($session, $data) {
            $session->update([
                'name' => $data['name'],
                'status' => $data['status'],
            ]);

            $this->syncChildren(
                $session,
                $data,
                $session->ages()->get()->keyBy('id'),
                $session->batches()->get()->keyBy('id'),
                $session->memberships()->get()->keyBy('id'),
            );

            return $session->fresh(['ages', 'batches', 'memberships']);
        });
    }

    public function delete(Session $session): void
    {
        if ($session->admission_count > 0 || $session->attendance_count > 0) {
            throw ValidationException::withMessages([
                'session' => 'Session cannot be deleted while it has admissions or attendance.',
            ]);
        }

        $session->delete();
    }

    private function updateOverSession(Session $session, array $data): Session
    {
        $this->assertStatus($data['status'], $session);

        $session->update(['status' => $data['status']]);

        return $session->fresh();
    }

    private function validateChildData(array $data): void
    {
        $errors = [];

        if ($message = $this->ageValidator->validate($data['ages'] ?? [])) {
            $errors['ages'] = $message;
        }

        if ($message = $this->batchValidator->validate($this->normalizeBatches($data['batches'] ?? []))) {
            $errors['batches'] = $message;
        }

        if ($message = $this->membershipValidator->validate($data['memberships'] ?? [])) {
            $errors['memberships'] = $message;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function assertStatus(string $status, Session $session): void
    {
        if ($message = $this->statusValidator->validateTransition($session, $status, $session->id)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function assertFrozenRows(Session $session, array $data): void
    {
        $errors = [];

        foreach ($session->ages as $age) {
            if ($age->admission_counter <= 0) {
                continue;
            }

            $row = $this->findRowNumber($data['ages'] ?? [], $age->id);
            $submitted = collect($data['ages'] ?? [])->firstWhere('id', $age->id);

            if (! $submitted
                || (int) $submitted['from_age'] !== $age->from_age
                || (int) $submitted['to_age'] !== $age->to_age
                || (float) $submitted['fee'] !== (float) $age->fee) {
                $errors['ages'] = sprintf(
                    'Age row %d (%d–%d): cannot be modified because it has admissions.',
                    $row,
                    $age->from_age,
                    $age->to_age,
                );
                break;
            }
        }

        foreach ($session->batches as $batch) {
            if ($batch->admission_counter <= 0) {
                continue;
            }

            $row = $this->findRowNumber($data['batches'] ?? [], $batch->id);
            $submitted = collect($data['batches'] ?? [])->firstWhere('id', $batch->id);
            $normalized = $this->normalizeBatchRow($submitted ?? []);

            if (! $submitted
                || $normalized['start_time'] !== substr((string) $batch->start_time, 0, 8)
                || $normalized['end_time'] !== substr((string) $batch->end_time, 0, 8)
                || (int) $normalized['buffer_time'] !== $batch->buffer_time) {
                $errors['batches'] = sprintf(
                    'Batch row %d (%s – %s): cannot be modified because it has admissions.',
                    $row,
                    TimeHelper::format12Hour((string) $batch->start_time),
                    TimeHelper::format12Hour((string) $batch->end_time),
                );
                break;
            }
        }

        foreach ($session->memberships as $membership) {
            if ($membership->admission_counter <= 0) {
                continue;
            }

            $row = $this->findRowNumber($data['memberships'] ?? [], $membership->id);
            $submitted = collect($data['memberships'] ?? [])->firstWhere('id', $membership->id);

            if (! $submitted
                || trim((string) $submitted['card_name']) !== $membership->card_name
                || (int) $submitted['no_of_slot'] !== $membership->no_of_slot
                || (float) $submitted['membership_cost'] !== (float) $membership->membership_cost) {
                $errors['memberships'] = sprintf(
                    'Membership row %d (%s): cannot be modified because it has admissions.',
                    $row,
                    $membership->card_name,
                );
                break;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function findRowNumber(array $rows, int $id): int
    {
        foreach ($rows as $index => $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $index + 1;
            }
        }

        return 0;
    }

    private function syncChildren(
        Session $session,
        array $data,
        $existingAges,
        $existingBatches,
        $existingMemberships,
    ): void {
        $this->syncAges($session, $data['ages'] ?? [], $existingAges);
        $this->syncBatches($session, $this->normalizeBatches($data['batches'] ?? []), $existingBatches);
        $this->syncMemberships($session, $data['memberships'] ?? [], $existingMemberships);
    }

    private function syncAges(Session $session, array $ages, $existing): void
    {
        $keptIds = [];

        foreach ($ages as $ageData) {
            $payload = [
                'from_age' => (int) $ageData['from_age'],
                'to_age' => (int) $ageData['to_age'],
                'fee' => $ageData['fee'],
            ];

            if (! empty($ageData['id']) && $existing->has($ageData['id'])) {
                $existing->get($ageData['id'])->update($payload);
                $keptIds[] = (int) $ageData['id'];
            } else {
                $created = $session->ages()->create($payload);
                $keptIds[] = $created->id;
            }
        }

        $session->ages()
            ->whereNotIn('id', $keptIds)
            ->where('admission_counter', 0)
            ->delete();
    }

    private function syncBatches(Session $session, array $batches, $existing): void
    {
        $keptIds = [];

        foreach ($batches as $batchData) {
            $payload = [
                'start_time' => $batchData['start_time'],
                'end_time' => $batchData['end_time'],
                'buffer_time' => (int) $batchData['buffer_time'],
            ];

            if (! empty($batchData['id']) && $existing->has($batchData['id'])) {
                $existing->get($batchData['id'])->update($payload);
                $keptIds[] = (int) $batchData['id'];
            } else {
                $created = $session->batches()->create($payload);
                $keptIds[] = $created->id;
            }
        }

        $session->batches()
            ->whereNotIn('id', $keptIds)
            ->where('admission_counter', 0)
            ->delete();
    }

    private function syncMemberships(Session $session, array $memberships, $existing): void
    {
        $keptIds = [];

        foreach ($memberships as $membershipData) {
            $payload = [
                'card_name' => trim((string) $membershipData['card_name']),
                'no_of_slot' => (int) $membershipData['no_of_slot'],
                'membership_cost' => $membershipData['membership_cost'],
            ];

            if (! empty($membershipData['id']) && $existing->has($membershipData['id'])) {
                $existing->get($membershipData['id'])->update($payload);
                $keptIds[] = (int) $membershipData['id'];
            } else {
                $created = $session->memberships()->create($payload);
                $keptIds[] = $created->id;
            }
        }

        $session->memberships()
            ->whereNotIn('id', $keptIds)
            ->where('admission_counter', 0)
            ->delete();
    }

    private function normalizeBatches(array $batches): array
    {
        return array_map(fn (array $batch) => $this->normalizeBatchRow($batch), $batches);
    }

    private function normalizeBatchRow(array $batch): array
    {
        if (! empty($batch['start_time']) && ! empty($batch['end_time'])) {
            return $batch;
        }

        return [
            'id' => $batch['id'] ?? null,
            'start_time' => TimeHelper::fromParts(
                (int) $batch['start_hour'],
                (int) $batch['start_minute'],
                (string) $batch['start_period'],
            ),
            'end_time' => TimeHelper::fromParts(
                (int) $batch['end_hour'],
                (int) $batch['end_minute'],
                (string) $batch['end_period'],
            ),
            'buffer_time' => (int) ($batch['buffer_time'] ?? 0),
        ];
    }
}
