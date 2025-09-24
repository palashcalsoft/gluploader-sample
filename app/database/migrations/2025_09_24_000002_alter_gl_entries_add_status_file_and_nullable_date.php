<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gl_entry_masters', function (Blueprint $table) {
            if (!Schema::hasColumn('gl_entry_masters', 'status')) {
                $table->string('status')->default('In Progress')->after('failed_rows');
            }
            if (!Schema::hasColumn('gl_entry_masters', 'file_name')) {
                $table->string('file_name')->nullable()->after('status');
            }
        });

        Schema::table('gl_entry_details', function (Blueprint $table) {
            // Make posting_date nullable to allow persisting failed rows
            $table->date('posting_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gl_entry_details', function (Blueprint $table) {
            $table->date('posting_date')->nullable(false)->change();
        });

        Schema::table('gl_entry_masters', function (Blueprint $table) {
            if (Schema::hasColumn('gl_entry_masters', 'file_name')) {
                $table->dropColumn('file_name');
            }
            if (Schema::hasColumn('gl_entry_masters', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};



