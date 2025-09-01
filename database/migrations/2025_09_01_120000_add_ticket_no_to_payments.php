<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'ticket_no')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('ticket_no', 16)->nullable()->unique()->after('vehicle_id');
            });
        }

        // Backfill existing rows with generated sequential ticket numbers
        if (Schema::hasTable('payments')) {
            $payments = DB::table('payments')->select('id', 'ticket_no')->orderBy('created_at')->get();
            $seq = 1;
            foreach ($payments as $p) {
                if (! $p->ticket_no) {
                    $ticket = sprintf('QPT%08d', $seq);
                    // Ensure uniqueness in case of collisions
                    while (DB::table('payments')->where('ticket_no', $ticket)->exists()) {
                        $seq++;
                        $ticket = sprintf('QPT%08d', $seq);
                    }
                    DB::table('payments')->where('id', $p->id)->update(['ticket_no' => $ticket]);
                    $seq++;
                }
            }
        }

        // Enforce not null after backfill
        Schema::table('payments', function (Blueprint $table) {
            $table->string('ticket_no', 16)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'ticket_no')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique(['ticket_no']);
                $table->dropColumn('ticket_no');
            });
        }
    }
};
