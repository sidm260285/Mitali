<?php

namespace App\Services;

use App\Models\AccountHead;
use App\Models\Admission;
use App\Models\AdmissionSlot;
use App\Models\CashTransaction;
use App\Models\Session;
use App\Models\SessionAge;
use App\Models\SessionBatch;
use App\Models\SessionMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionService
{

    public function create(array $data, User $doer): Admission
    {
        $session = Session::findOrFail($data['session_id']);
        $membership = SessionMembership::where('session_id', $session->id)->findOrFail($data['session_membership_id']);
        $dob = \Carbon\Carbon::parse($data['date_of_birth']);
        $age = (int) floor($dob->diffInYears(now()));

        $sessionAge = SessionAge::where('session_id', $session->id)
            ->where('from_age', '<=', $age)
            ->where('to_age', '>=', $age)
            ->first();

        if (! $sessionAge) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'No age group found for the member\'s age ('.$age.' years).',
            ]);
        }

        $amount = (float) $membership->membership_cost + (float) $sessionAge->fee + $session->form_fee;
        $isWildcard = $membership->no_of_slot === -1;
        $batchIds = $this->resolveBatchIds($session, $membership, $data['batch_ids'] ?? [], $isWildcard);

        $this->validateBatchCapacity($batchIds);

        return DB::transaction(function () use ($data, $session, $membership, $sessionAge, $amount, $isWildcard, $batchIds, $doer) {
            $cashTransaction = $this->recordTransaction($data, $amount, $doer);

            $admission = Admission::create([
                'session_id' => $session->id,
                'session_membership_id' => $membership->id,
                'session_age_id' => $sessionAge->id,
                'cash_transaction_id' => $cashTransaction->id,
                'full_name' => $data['full_name'],
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
                'amount' => $amount,
                'payment_mode' => $data['payment_mode'],
                'admitted_by' => $doer->id,
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

    public function syncWildcardSlotsForNewBatch(Session $session, SessionBatch $newBatch): void
    {
        $wildcardAdmissions = Admission::where('session_id', $session->id)
            ->where('is_wildcard', true)
            ->get();

        foreach ($wildcardAdmissions as $admission) {
            $exists = AdmissionSlot::where('admission_id', $admission->id)
                ->where('session_batch_id', $newBatch->id)
                ->exists();

            if (! $exists) {
                AdmissionSlot::create([
                    'admission_id' => $admission->id,
                    'session_batch_id' => $newBatch->id,
                ]);
            }
        }

        if ($wildcardAdmissions->count() > 0) {
            $newBatch->increment('admission_counter', $wildcardAdmissions->count());
        }
    }

    private function resolveBatchIds(Session $session, SessionMembership $membership, array $submittedBatchIds, bool $isWildcard): array
    {
        $allBatchIds = SessionBatch::where('session_id', $session->id)->pluck('id')->toArray();

        if ($isWildcard) {
            return $allBatchIds;
        }

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
                    'batch_ids' => 'Batch ('.\App\Support\TimeHelper::format12Hour((string) $batch->start_time).' - '.\App\Support\TimeHelper::format12Hour((string) $batch->end_time).') is full (max '.$batch->max_size.').',
                ]);
            }
        }
    }

    private function recordTransaction(array $data, float $amount, User $doer): CashTransaction
    {
        $head = AccountHead::findSystem(AccountHead::SYSTEM_ADMISSION);
        $mode = $data['payment_mode'];
        $narration = 'Admission: '.$data['full_name'];

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
}
