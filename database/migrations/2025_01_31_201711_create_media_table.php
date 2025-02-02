<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->index();
            $table->string('file_name', 255);
            $table->string('mime_type', 100);
            $table->enum('type', ['image', 'video', 'document']);
            $table->string('path', 255);
            $table->string('disk', 50)->default('public');
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();
    
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media');
    }
}
