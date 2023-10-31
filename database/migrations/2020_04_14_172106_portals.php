<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Portals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portals', function (Blueprint $table) {
            $table->id();
            $table->string('app_code');
            $table->string('app_id')->nullable();
            $table->string('app_secret')->nullable();
            $table->text('scope');
            $table->integer('user_id');
            $table->string('domain');
            $table->string('member_id');
            $table->string('access_token');
            $table->string('refresh_token');
            $table->timestamp('expires')->nullable();
            $table->string('lang')->nullable();
            $table->tinyInteger('admin_only')->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->integer('portal_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('portals');

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('portal_id');
        });
    }
}
