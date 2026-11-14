<?php

namespace App\Services;

use App\Models\AccountHead;
use App\Models\Admission;
use App\Models\AdmissionSlot;
use App\Models\CashTransaction;
use App\Models\FeeBreakdown;
use App\Models\Session;
use App\Models\SessionAge;
use App\Models\SessionBatch;
use App\Models\SessionMembership;
use App\Models\User;
use App\Support\TimeHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionService
{
    public function create(array $data, User $doer): Admission
    {
        $quote = $this->quote(
            (int) $data['session_id'],
            (int) $data['session_membership_id'],
            (string) $data['date_of_birth'],
            Admission::asBoolean($data['is_extended_membership'] ?? false),
        );

        $session = $quote['session'];
        $membership = $quote['membership'];
        $sessionAge = $quote['session_age'];
        $isWildcard = $membership->no_of_slot === -1;
        $batchIds = $this->resolveBatchIds($session, $membership, $data['batch_ids'] ?? [], $isWildcard);

        if (! $isWildcard) {
            $this->validateBatchCapacity($batchIds);
        }

        return DB::transaction(function () use ($data, $quote, $session, $membership, $sessionAge, $isWildcard, $batchIds, $doer) {
            $cashTransaction = $this->recordTransaction($data, $quote['amount'], $doer);

            $admission = Admission::create([
                'session_id' => $session->id,
                'session_start_date' => $quote['session_start_date'],
                'session_end_date' => $quote['session_end_date'],
                'is_extended_membership' => $quote['is_extended'],
                'session_membership_id' => $membership->id,
                'session_age_id' => $sessionAge->id,
                'cash_transaction_id' => $cashTransaction->id,
                'full_name' => $data['full_name'],
                'physically_disabled' => Admission::asBoolean($data['physically_disabled']),
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'],
                'mobile_no' => $data['mobile_no'],
                'guardian_name' => $data['guardian_name'],
                'relation' => $data['relation'],
                'emergency_contact_no' => $data['emergency_contact_no'],
                'address' => $data['address'],
                'police_station' => $data['police_station'],
                'pin_code' => $data['pin_code'],
                'rfid_code' => $data['rfid_code'],
                'is_wildcard' => $isWildcard,
                'amount' => $quote['amount'],
                'payment_mode' => $data['payment_mode'],
                'admitted_by' => $doer->id,
            ]);

            FeeBreakdown::create([
                'session_id' => $session->id,
                'admission_id' => $admission->id,
                'membership_amount' => $quote['membership_amount'],
                'age_fee' => $quote['age_fee'],
                'form_fee' => $quote['form_fee'],
                'extended_fee' => $quote['extended_fee'],
            ]);

            foreach ($batchIds as $batchId) {
                AdmissionSlot::create([
                    'admission_id' => $admission->id,
                    'session_batch_id' => $batchId,
                ]);
            }

            $this->incrementCounters($session, $sessionAge, $membership, $batchIds);

            return $admission;
        });
    }

    /**
     * @return array{
     *     session: Session,
     *     membership: SessionMembership,
     *     session_age: SessionAge,
     *     is_extended: bool,
     *     session_start_date: string,
     *     session_end_date: string,
     *     membership_amount: float,
     *     age_fee: float,
     *     form_fee: float,
     *     extended_fee: float,
     *     amount: float,
     *     age: int,
     *     age_label: string
     * }
     */
    public function quote(int $sessionId, int $membershipId, string $dateOfBirth, bool $isExtended): array
    {
        $session = Session::findOrFail($sessionId);
        $membership = SessionMembership::where('session_id', $session->id)->findOrFail($membershipId);

        if (! $session->from_date || ! $session->to_date || ! $session->extended_date) {
            throw ValidationException::withMessages([
                'session_id' => 'This session does not have a start date, end date, and extended date.',
            ]);
        }

        $age = (int) floor(Carbon::parse($dateOfBirth)->diffInYears(now()));
        $sessionAge = SessionAge::where('session_id', $session->id)
            ->where('from_age', '<=', $age)
            ->where('to_age', '>=', $age)
            ->first();

        if (! $sessionAge) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'No age group found for the member\'s age ('.$age.' years).',
            ]);
        }

        $membershipAmount = round((float) $membership->membership_cost, 2);
        $ageFee = round((float) $sessionAge->fee, 2);
        $formFee = round((float) $session->form_fee, 2);
        $extendedFee = $isExtended ? round((float) $sessionAge->extended_fee, 2) : 0.0;

        return [
            'session' => $session,
            'membership' => $membership,
            'session_age' => $sessionAge,
            'is_extended' => $isExtended,
            'session_start_date' => $session->from_date->toDateString(),
            'session_end_date' => ($isExtended ? $session->extended_date : $session->to_date)->toDateString(),
            'membership_amount' => $membershipAmount,
            'age_fee' => $ageFee,
            'form_fee' => $formFee,
            'extended_fee' => $extendedFee,
            'amount' => round($membershipAmount + $ageFee + $formFee + $extendedFee, 2),
            'age' => $age,
            'age_label' => $sessionAge->from_age.'–'.$sessionAge->to_age.' years',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function quoteSummary(int $sessionId, int $membershipId, string $dateOfBirth, bool $isExtended): array
    {
        $quote = $this->quote($sessionId, $membershipId, $dateOfBirth, $isExtended);

        return [
            'amount' => $quote['amount'],
            'membership_name' => $quote['membership']->card_name,
            'membership_amount' => $quote['membership_amount'],
            'age_label' => $quote['age_label'],
            'age_fee' => $quote['age_fee'],
            'form_fee' => $quote['form_fee'],
            'extended_fee' => $quote['extended_fee'],
            'is_extended' => $quote['is_extended'],
        ];
    }

    /**
     * Replace time slots without changing the membership or the charged amount.
     * An Any card keeps the slots it already has. A fixed card must keep the same slot count.
     *
     * @param  array<int, mixed>  $submittedBatchIds
     */
    public function replaceBatches(Admission $admission, array $submittedBatchIds): void
    {
        $admission->loadMissing(['membership', 'slots']);

        if ($admission->membership->no_of_slot === -1) {
            return;
        }

        $batchIds = array_values(array_unique(array_map(fn ($id) => (int) $id, $submittedBatchIds)));
        $sessionBatchIds = SessionBatch::where('session_id', $admission->session_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($batchIds) !== $admission->membership->no_of_slot) {
            throw ValidationException::withMessages([
                'batch_ids' => 'You must select exactly '.$admission->membership->no_of_slot.' batch(es) for this membership.',
            ]);
        }

        if (array_diff($batchIds, $sessionBatchIds) !== []) {
            throw ValidationException::withMessages([
                'batch_ids' => 'One or more selected batches are invalid.',
            ]);
        }

        $oldBatchIds = $admission->slots
            ->pluck('session_batch_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $added = array_values(array_diff($batchIds, $oldBatchIds));
        $removed = array_values(array_diff($oldBatchIds, $batchIds));

        $this->validateBatchCapacity($added);

        if ($removed !== []) {
            SessionBatch::whereIn('id', $removed)
                ->where('admission_counter', '>', 0)
                ->decrement('admission_counter');
            $admission->slots()->whereIn('session_batch_id', $removed)->delete();
        }

        foreach ($added as $batchId) {
            AdmissionSlot::create([
                'admission_id' => $admission->id,
                'session_batch_id' => $batchId,
            ]);
        }

        if ($added !== []) {
            SessionBatch::whereIn('id', $added)->increment('admission_counter');
        }
    }

    public function syncWildcardSlotsForNewBatch(Session $session, SessionBatch $newBatch): void
    {
        $wildcardIds = Admission::query()
            ->where('session_id', $session->id)
            ->where('is_wildcard', true)
            ->pluck('id');

        if ($wildcardIds->isEmpty()) {
            return;
        }

        $alreadyAssigned = AdmissionSlot::query()
            ->where('session_batch_id', $newBatch->id)
            ->whereIn('admission_id', $wildcardIds)
            ->pluck('admission_id');

        $missingIds = $wildcardIds->diff($alreadyAssigned)->values();

        foreach ($missingIds as $admissionId) {
            AdmissionSlot::create([
                'admission_id' => $admissionId,
                'session_batch_id' => $newBatch->id,
            ]);
        }

        if ($missingIds->isNotEmpty()) {
            $newBatch->increment('admission_counter', $missingIds->count());
        }
    }

    /**
     * @return Collection<int, SessionMembership>
     */
    public function upgradeMembershipChoices(Admission $admission): Collection
    {
        $admission->loadMissing('membership');
        $current = $admission->membership;

        $memberships = SessionMembership::query()
            ->where('session_id', $admission->session_id)
            ->orderBy('membership_cost')
            ->orderBy('id')
            ->get();

        if ($admission->is_wildcard) {
            return collect([$current]);
        }

        return $memberships
            ->filter(function (SessionMembership $membership) use ($current) {
                return $membership->id === $current->id || $this->isHigherMembership($current, $membership);
            })
            ->sortBy(fn (SessionMembership $membership) => sprintf(
                '%d-%012.2f-%010d',
                $membership->id === $current->id ? 0 : 1,
                (float) $membership->membership_cost,
                $membership->id,
            ))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function upgradeQuote(Admission $admission, int $membershipId, bool $extend): array
    {
        $admission->loadMissing('membership');

        try {
            $plan = $this->describeUpgrade($admission, $membershipId, $extend);
        } catch (ValidationException $exception) {
            return [
                'amount' => 0,
                'membership_gap' => 0,
                'extended_fee' => 0,
                'extended_changed' => false,
                'membership_changed' => false,
                'is_late' => false,
                'is_wildcard' => (bool) $admission->is_wildcard,
                'no_of_slot' => (int) $admission->membership->no_of_slot,
                'extended_label' => 'Extended fee',
                'error' => collect($exception->errors())->flatten()->first(),
            ];
        }

        return [
            'amount' => $plan['amount'],
            'membership_gap' => $plan['membership_gap'],
            'extended_fee' => $plan['extended_fee'],
            'extended_changed' => $plan['extended_changed'],
            'membership_changed' => $plan['membership_changed'],
            'is_late' => $plan['is_late'],
            'is_wildcard' => $plan['is_wildcard'],
            'no_of_slot' => $plan['no_of_slot'],
            'extended_label' => $plan['extended_label'],
            'error' => null,
        ];
    }

    public function upgrade(Admission $admission, array $data, User $doer): Admission
    {
        return DB::transaction(function () use ($admission, $data, $doer) {
            $admission = Admission::query()->lockForUpdate()->findOrFail($admission->id);
            $admission->load(['membership', 'sessionAge', 'session', 'slots', 'feeBreakdown']);

            $plan = $this->describeUpgrade(
                $admission,
                (int) $data['session_membership_id'],
                Admission::asBoolean($data['is_extended_membership'] ?? false),
            );

            if ($plan['amount'] <= 0) {
                throw ValidationException::withMessages([
                    'upgrade' => 'Choose a higher membership or an extended session before upgrading.',
                ]);
            }

            $breakdown = $admission->feeBreakdown;

            if (! $breakdown || (int) $breakdown->session_id !== (int) $admission->session_id) {
                throw ValidationException::withMessages([
                    'upgrade' => 'Fee breakdown is missing for this admission.',
                ]);
            }

            $this->assertUpgradePayment($data);

            if ($plan['membership_changed']) {
                if ($plan['is_wildcard']) {
                    $this->assignWildcardBatches($admission);
                } else {
                    $this->applyFixedSlotUpgrade($admission, $plan['membership'], $data['batch_ids'] ?? []);
                }
            }

            $this->recordTransaction(
                $data,
                $plan['amount'],
                $doer,
                'Admission upgrade: '.$admission->full_name,
            );

            $updates = [
                'amount' => $this->money((float) $admission->amount + $plan['amount']),
            ];

            if ($plan['membership_changed']) {
                $updates['session_membership_id'] = $plan['membership']->id;
                $updates['is_wildcard'] = $plan['is_wildcard'];
                $this->moveMembershipCounter($admission->membership, $plan['membership']);
            }

            if ($plan['extended_changed']) {
                $updates['is_extended_membership'] = true;
                $updates['session_end_date'] = $admission->session->extended_date->toDateString();
            }

            $admission->update($updates);

            $breakdownUpdates = [];

            if ($plan['membership_changed']) {
                $breakdownUpdates['membership_amount'] = $this->money($plan['membership']->membership_cost);
            }

            if ($plan['extended_changed']) {
                $breakdownUpdates['extended_fee'] = $plan['extended_fee'];
            }

            if ($breakdownUpdates !== []) {
                $breakdown->update($breakdownUpdates);
            }

            return $admission->fresh(['membership', 'sessionAge', 'session', 'slots', 'feeBreakdown', 'cashTransaction']);
        });
    }

    private function resolveBatchIds(Session $session, SessionMembership $membership, array $submittedBatchIds, bool $isWildcard): array
    {
        $allBatchIds = SessionBatch::where('session_id', $session->id)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($isWildcard) {
            return $allBatchIds;
        }

        $submittedBatchIds = array_values(array_unique(array_map(fn ($id) => (int) $id, $submittedBatchIds)));
        $slotsAllowed = $membership->no_of_slot;

        if (count($submittedBatchIds) !== $slotsAllowed) {
            throw ValidationException::withMessages([
                'batch_ids' => 'You must select exactly '.$slotsAllowed.' batch(es) for this membership.',
            ]);
        }

        $invalidBatches = array_diff($submittedBatchIds, $allBatchIds);
        if (! empty($invalidBatches)) {
            throw ValidationException::withMessages([
                'batch_ids' => 'One or more selected batches are invalid.',
            ]);
        }

        return $submittedBatchIds;
    }

    private function validateBatchCapacity(array $batchIds): void
    {
        $batches = SessionBatch::whereIn('id', $batchIds)->get();

        foreach ($batches as $batch) {
            if ($batch->max_size > 0 && $batch->admission_counter >= $batch->max_size) {
                throw ValidationException::withMessages([
                    'batch_ids' => 'Batch ('.TimeHelper::format12Hour((string) $batch->start_time).' - '.TimeHelper::format12Hour((string) $batch->end_time).') is full (max '.$batch->max_size.').',
                ]);
            }
        }
    }

    private function recordTransaction(array $data, float $amount, User $doer, ?string $narration = null): CashTransaction
    {
        $head = AccountHead::findSystem(AccountHead::SYSTEM_ADMISSION);
        $mode = $data['payment_mode'];
        $narration = $narration ?? ('Admission: '.$data['full_name']);

        if ($mode === CashTransaction::MODE_BANK) {
            $bank = User::banks()->findOrFail($data['bank_id']);

            return CashTransaction::create([
                'user_id' => $bank->id,
                'account_head_id' => $head->id,
                'type' => CashTransaction::TYPE_CREDIT,
                'mode' => CashTransaction::MODE_BANK,
                'amount' => $amount,
                'transaction_date' => now()->toDateString(),
                'narration' => $narration,
                'transaction_id' => $data['transaction_id'],
                'entry_by' => $doer->id,
            ]);
        }

        return CashTransaction::create([
            'user_id' => $doer->id,
            'account_head_id' => $head->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_CASH,
            'amount' => $amount,
            'transaction_date' => now()->toDateString(),
            'narration' => $narration,
            'entry_by' => $doer->id,
        ]);
    }

    private function incrementCounters(Session $session, SessionAge $sessionAge, SessionMembership $membership, array $batchIds): void
    {
        $session->increment('admission_count');
        $sessionAge->increment('admission_counter');
        $membership->increment('admission_counter');

        SessionBatch::whereIn('id', $batchIds)->increment('admission_counter');
    }

    private function isHigherMembership(SessionMembership $current, SessionMembership $next): bool
    {
        if ($this->money($next->membership_cost) <= $this->money($current->membership_cost)) {
            return false;
        }

        if ($next->no_of_slot === -1) {
            return true;
        }

        return $next->no_of_slot >= $current->no_of_slot;
    }

    /**
     * @return array{
     *     membership: SessionMembership,
     *     membership_changed: bool,
     *     membership_gap: float,
     *     is_wildcard: bool,
     *     no_of_slot: int,
     *     extended_changed: bool,
     *     extended_fee: float,
     *     is_late: bool,
     *     extended_label: string,
     *     amount: float
     * }
     */
    private function describeUpgrade(Admission $admission, int $membershipId, bool $extend): array
    {
        $admission->loadMissing(['membership', 'sessionAge', 'session']);
        $current = $admission->membership;

        if ($admission->is_extended_membership && ! $extend) {
            throw ValidationException::withMessages([
                'is_extended_membership' => 'An extended session cannot be turned off.',
            ]);
        }

        $membershipChanged = $membershipId !== (int) $current->id;
        $next = $current;

        if ($membershipChanged) {
            if ($admission->is_wildcard) {
                throw ValidationException::withMessages([
                    'session_membership_id' => 'A wildcard membership cannot be changed.',
                ]);
            }

            $next = SessionMembership::query()
                ->where('session_id', $admission->session_id)
                ->find($membershipId);

            if (! $next || ! $this->isHigherMembership($current, $next)) {
                throw ValidationException::withMessages([
                    'session_membership_id' => 'Choose a higher-priced card with the same or more slots.',
                ]);
            }
        }

        $extendedChanged = $extend && ! $admission->is_extended_membership;
        $isLate = false;
        $extendedFee = 0.0;

        if ($extendedChanged) {
            if (! $admission->session?->extended_date || ! $admission->sessionAge) {
                throw ValidationException::withMessages([
                    'is_extended_membership' => 'This session cannot be extended.',
                ]);
            }

            $isLate = $this->extendedUpgradeIsLate($admission);
            $extendedFee = $this->money($isLate
                ? $admission->sessionAge->extended_fee_late
                : $admission->sessionAge->extended_fee);
        }

        $gap = $membershipChanged
            ? $this->money($this->money($next->membership_cost) - $this->money($current->membership_cost))
            : 0.0;

        return [
            'membership' => $next,
            'membership_changed' => $membershipChanged,
            'membership_gap' => $gap,
            'is_wildcard' => $next->no_of_slot === -1,
            'no_of_slot' => (int) $next->no_of_slot,
            'extended_changed' => $extendedChanged,
            'extended_fee' => $extendedFee,
            'is_late' => $isLate,
            'extended_label' => $isLate ? 'Extended fee (late)' : 'Extended fee',
            'amount' => $this->money($gap + $extendedFee),
        ];
    }

    private function extendedUpgradeIsLate(Admission $admission): bool
    {
        $grace = max(0, (int) config('admission.extended_upgrade_grace_days', 30));
        $admittedOn = $admission->created_at->copy()->startOfDay();
        $today = now()->copy()->startOfDay();

        if ($today->lt($admittedOn)) {
            return false;
        }

        return $admittedOn->diff($today)->days > $grace;
    }

    private function assertUpgradePayment(array $data): void
    {
        $mode = $data['payment_mode'] ?? null;

        if (! in_array($mode, [CashTransaction::MODE_CASH, CashTransaction::MODE_BANK], true)) {
            throw ValidationException::withMessages([
                'payment_mode' => 'Payment mode is required.',
            ]);
        }

        if ($mode !== CashTransaction::MODE_BANK) {
            return;
        }

        $bankId = $data['bank_id'] ?? null;

        if (! $bankId || ! User::banks()->active()->whereKey($bankId)->exists()) {
            throw ValidationException::withMessages([
                'bank_id' => 'Please select an active bank.',
            ]);
        }

        if (blank($data['transaction_id'] ?? null)) {
            throw ValidationException::withMessages([
                'transaction_id' => 'Reference transaction ID is required for bank payment mode.',
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $submittedBatchIds
     */
    private function applyFixedSlotUpgrade(Admission $admission, SessionMembership $membership, array $submittedBatchIds): void
    {
        $batchIds = array_values(array_unique(array_map(fn ($id) => (int) $id, $submittedBatchIds)));
        $sessionBatchIds = SessionBatch::query()
            ->where('session_id', $admission->session_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($batchIds) !== $membership->no_of_slot) {
            throw ValidationException::withMessages([
                'batch_ids' => 'You must select exactly '.$membership->no_of_slot.' batch(es) for this membership.',
            ]);
        }

        if (array_diff($batchIds, $sessionBatchIds) !== []) {
            throw ValidationException::withMessages([
                'batch_ids' => 'One or more selected batches are invalid.',
            ]);
        }

        $oldBatchIds = $admission->slots
            ->pluck('session_batch_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $added = array_values(array_diff($batchIds, $oldBatchIds));
        $removed = array_values(array_diff($oldBatchIds, $batchIds));

        $this->validateBatchCapacity($added);

        if ($removed !== []) {
            SessionBatch::query()
                ->whereIn('id', $removed)
                ->where('admission_counter', '>', 0)
                ->decrement('admission_counter');
            $admission->slots()->whereIn('session_batch_id', $removed)->delete();
        }

        foreach ($added as $batchId) {
            AdmissionSlot::create([
                'admission_id' => $admission->id,
                'session_batch_id' => $batchId,
            ]);
        }

        if ($added !== []) {
            SessionBatch::query()->whereIn('id', $added)->increment('admission_counter');
        }
    }

    private function assignWildcardBatches(Admission $admission): void
    {
        $allBatchIds = SessionBatch::query()
            ->where('session_id', $admission->session_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $heldBatchIds = $admission->slots
            ->pluck('session_batch_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $added = array_values(array_diff($allBatchIds, $heldBatchIds));

        foreach ($added as $batchId) {
            AdmissionSlot::create([
                'admission_id' => $admission->id,
                'session_batch_id' => $batchId,
            ]);
        }

        if ($added !== []) {
            SessionBatch::query()->whereIn('id', $added)->increment('admission_counter');
        }
    }

    private function moveMembershipCounter(SessionMembership $from, SessionMembership $to): void
    {
        SessionMembership::query()
            ->whereKey($from->id)
            ->where('admission_counter', '>', 0)
            ->decrement('admission_counter');

        SessionMembership::query()->whereKey($to->id)->increment('admission_counter');
    }

    private function money(mixed $value): float
    {
        return round((float) $value, 2);
    }
}
