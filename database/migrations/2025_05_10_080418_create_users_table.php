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
        Schema::create('users', function (Blueprint $table) {
            // Sesuai dengan model User Anda: $primaryKey = 'customer_id';
            // Laravel 9+ $table->id() akan membuat bigInteger auto-increment.
            // Jika Anda ingin nama kolom primary key kustom:
            $table->id('customer_id'); // Ini akan membuat BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY bernama customer_id

            $table->string('name');
            $table->string('kwh_meter_code')->unique(); // Sebaiknya unik
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken(); // Untuk fitur "remember me"
            $table->timestamps(); // Ini akan membuat kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};