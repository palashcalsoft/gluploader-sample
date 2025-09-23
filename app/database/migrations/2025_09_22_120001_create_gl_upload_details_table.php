<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gl_upload_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gl_upload_id')->constrained('gl_uploads')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->date('posting_date');
            $table->string('reference')->nullable();
            $table->string('journal_code')->nullable();
            $table->string('account_number')->nullable();
            $table->text('posting_description')->nullable();
            $table->decimal('debit', 18, 2)->nullable();
            $table->decimal('credit', 18, 2)->nullable();
            $table->string('validation_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_upload_details');
    }
};


