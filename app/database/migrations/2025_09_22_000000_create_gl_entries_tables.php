<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gl_entry_masters', function (Blueprint $table) {
            $table->id();
            $table->string('uploaded_by');
            $table->string('loft_username');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('status')->default('In Progress');
            $table->string('file_name')->nullable();
            $table->timestamps();
        });

        Schema::create('gl_entry_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gl_entry_master_id')->constrained('gl_entry_masters')->cascadeOnDelete();
            $table->date('posting_date')->nullable();
            $table->string('reference')->nullable();
            $table->string('journal_code')->nullable();
            $table->string('account_number');
            $table->string('posting_description')->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->unsignedInteger('row_number');
            $table->string('failure_reason')->nullable();
            $table->timestamps();
            $table->index(['gl_entry_master_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_entry_details');
        Schema::dropIfExists('gl_entry_masters');
    }
};


