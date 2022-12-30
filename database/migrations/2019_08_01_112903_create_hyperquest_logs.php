<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHyperQuestLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('hyperquest.log.table', 'hyper_request_logs'), function (Blueprint $table) {
            $table->engine = 'MyISAM';

            $table->bigIncrements('id');
            $table->integer('user_id');
            $table->string('request_class', 512);
            $table->string('remote_path', 2048);
            $table->integer('http_status')->default(0);
            $table->json('request')->nullable();
            $table->text('response')->nullable();
            $table->json('errors')->nullable();
            $table->boolean('failed')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('hyperquest.log.table', 'hyper_request_logs'));
    }
}
