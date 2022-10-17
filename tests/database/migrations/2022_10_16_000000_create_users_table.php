<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('forename');
            $table->string('surname')->nullable();
            $table->string('email')->unique();
            $table->string('password');

            $table->foreignId('role_id')->constrained();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('parent_id')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users');
    }
};
