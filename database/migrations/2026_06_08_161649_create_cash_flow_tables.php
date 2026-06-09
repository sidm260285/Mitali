<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0)->after('must_change_password');
        });

        Schema::create('account_heads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['name', 'type']);
        });

        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_head_id')->constrained('account_heads')->restrictOnDelete();
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->string('narration', 400)->nullable();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->uuid('transfer_group_id')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index('type');
        });

        $this->createBalanceTrigger();
    }

    public function down(): void
    {
        $this->dropBalanceTrigger();

        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('account_heads');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }

    private function createBalanceTrigger(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
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

            return;
        }

        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS cash_transactions_after_insert');
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER cash_transactions_after_insert
                AFTER INSERT ON cash_transactions
                FOR EACH ROW
                BEGIN
                    UPDATE users
                    SET balance = CASE
                        WHEN NEW.type = 'credit' THEN balance + NEW.amount
                        ELSE balance - NEW.amount
                    END
                    WHERE id = NEW.user_id;

                    UPDATE cash_transactions
                    SET current_balance = (SELECT balance FROM users WHERE id = NEW.user_id)
                    WHERE id = NEW.id;
                END
            SQL);
        }
    }

    private function dropBalanceTrigger(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'sqlite'], true)) {
            DB::unprepared('DROP TRIGGER IF EXISTS cash_transactions_after_insert');
        }
    }
};
