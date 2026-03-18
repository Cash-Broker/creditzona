<?php

use App\Support\Phone\PhoneNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('normalized_phone')->nullable()->after('phone');
            $table->index(['normalized_phone', 'created_at'], 'leads_normalized_phone_created_at_index');
        });

        DB::table('leads')
            ->select(['id', 'phone'])
            ->orderBy('id')
            ->chunkById(100, function ($leads): void {
                foreach ($leads as $lead) {
                    DB::table('leads')
                        ->where('id', $lead->id)
                        ->update([
                            'normalized_phone' => PhoneNormalizer::normalize($lead->phone),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_normalized_phone_created_at_index');
            $table->dropColumn('normalized_phone');
        });
    }
};
