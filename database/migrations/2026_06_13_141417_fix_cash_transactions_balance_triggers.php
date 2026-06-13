<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->recreateBalanceTriggers();
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS cash_transactions_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS cash_transactions_after_insert');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER cash_transactions_after_insert
            AFTER INSERT ON cash_transactions
            FOR EACH ROW
            BEGIN
                IF NEW.type = 'credit' THEN
                    UPDATE users SET balance = balance + NEW.amount WHERE id = NEW.user_id;
                ELSE
                    UPDATE users SET balance = balance - NEW.amount WHERE id = NEW.user_id;
                END IF;

                UPDATE cash_transactions
                SET current_balance = (SELECT balance FROM users WHERE id = NEW.user_id)
                WHERE id = NEW.id;
            END
        SQL);
    }

    private function recreateBalanceTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS cash_transactions_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS cash_transactions_after_insert');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER cash_transactions_before_insert
            BEFORE INSERT ON cash_transactions
            FOR EACH ROW
            BEGIN
                DECLARE user_balance DECIMAL(15,2);

                SELECT balance INTO user_balance FROM users WHERE id = NEW.user_id;

                IF NEW.type = 'credit' THEN
                    SET NEW.current_balance = user_balance + NEW.amount;
                ELSE
                    SET NEW.current_balance = user_balance - NEW.amount;
                END IF;
            END
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER cash_transactions_after_insert
            AFTER INSERT ON cash_transactions
            FOR EACH ROW
            BEGIN
                IF NEW.type = 'credit' THEN
                    UPDATE users SET balance = balance + NEW.amount WHERE id = NEW.user_id;
                ELSE
                    UPDATE users SET balance = balance - NEW.amount WHERE id = NEW.user_id;
                END IF;
            END
        SQL);
    }
};
