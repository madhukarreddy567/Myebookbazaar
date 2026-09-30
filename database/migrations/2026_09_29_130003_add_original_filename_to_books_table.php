<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remember the customer's original upload filename so downloads are
     * served with the real name (Content-Disposition) instead of the
     * hashed storage name.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('original_filename')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('original_filename');
        });
    }
};
