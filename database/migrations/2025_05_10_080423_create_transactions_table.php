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
        Schema::create('transactions', function (Blueprint $table) {
            // Sesuai model: $primaryKey = 'transaction_id', $keyType = 'bigint', $incrementing = true
            $table->bigIncrements('transaction_id'); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

            // Foreign key ke tabel users, kolom customer_id
            // Pastikan tipe datanya cocok dengan primary key di tabel users (BIGINT UNSIGNED)
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')
                  ->references('customer_id') // referensi ke kolom 'customer_id'
                  ->on('users')             // di tabel 'users'
                  ->onDelete('cascade');     // Opsional: jika customer dihapus, transaksinya juga dihapus

            // Sesuai model: $casts = ['transaction_date' => 'datetime']
            $table->dateTime('transaction_date');

            // Sesuai model: $casts = ['amount' => 'integer']
            $table->integer('amount');

            $table->string('generated_token')->unique()->nullable(); // Sebaiknya unik
            $table->string('status')->default('owing'); // Bisa diberi nilai default
            $table->string('payment_method');
            $table->string('payment_code');

            // Sesuai model: $timestamps = true;
            $table->timestamps(); // Ini akan membuat kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};