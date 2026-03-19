<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_guarantors', function (Blueprint $table): void {
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('email')->nullable()->after('phone');
            $table->string('city')->nullable()->after('email');
            $table->string('workplace')->nullable()->after('city');
            $table->string('job_title')->nullable()->after('workplace');
            $table->integer('salary')->nullable()->after('job_title');
            $table->string('marital_status')->nullable()->after('salary');
            $table->integer('children_under_18')->nullable()->after('marital_status');
            $table->string('salary_bank')->nullable()->after('children_under_18');
            $table->string('credit_bank')->nullable()->after('salary_bank');
            $table->integer('amount')->nullable()->after('credit_bank');
            $table->string('property_type')->nullable()->after('amount');
            $table->string('property_location')->nullable()->after('property_type');
            $table->json('documents')->nullable()->after('property_location');
            $table->json('document_file_names')->nullable()->after('documents');
            $table->longText('internal_notes')->nullable()->after('document_file_names');
        });
    }

    public function down(): void
    {
        Schema::table('lead_guarantors', function (Blueprint $table): void {
            $table->dropColumn([
                'middle_name',
                'email',
                'city',
                'workplace',
                'job_title',
                'salary',
                'marital_status',
                'children_under_18',
                'salary_bank',
                'credit_bank',
                'amount',
                'property_type',
                'property_location',
                'documents',
                'document_file_names',
                'internal_notes',
            ]);
        });
    }
};
