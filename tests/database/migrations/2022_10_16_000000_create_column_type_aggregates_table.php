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
        Schema::create('column_type_aggregates', function (Blueprint $table) {
            // @todo Unsupported: bit, binary, varchar, tinyblob, longblob, varbinary, mediumblob
            $table->integer('integer');
            $table->decimal('decimal');
            $table->set('set', ['a', 'b', 'c']);
            $table->char('char');
            $table->text('text');
            $table->binary('blob');
            $table->date('date');
            $table->time('time');
            $table->year('year');
            $table->boolean('boolean');
            $table->float('float');
            $table->bigInteger('bigInteger');
            $table->double('double');
            $table->tinyText('tinyText');
            $table->longText('longText');
            $table->dateTime('dateTime');
            $table->smallInteger('smallInteger');
            $table->mediumInteger('mediumInteger');
            $table->timestamp('timestamp');
            $table->mediumText('mediumText');
            $table->enum('enum', ['a', 'b', 'c']);

            $table->integer('castInt');
            $table->integer('castReal');
            $table->date('castDate');
            $table->float('castFloat');
            $table->boolean('castBool');
            $table->double('castDouble');
            $table->string('castString');
            $table->integer('castInteger');
            $table->dateTime('castDatetime');
            $table->boolean('castBoolean');
            $table->json('castArray');
            $table->string('castEncrypted');
            $table->timestamp('castTimestamp')->useCurrent();
            $table->date('castImmutableDate');
            $table->string('castAsStringable');
            $table->dateTime('castImmutableDateTime');
            $table->json('castObject');
            $table->float('castDecimal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('column_type_aggregates');
    }
};
