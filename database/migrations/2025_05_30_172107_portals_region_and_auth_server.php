<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('portals', function (Blueprint $table) {
            $table->string('oauth_server')->after('app_secret')->nullable();
            $table->string('region')->after('user_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('portals', function (Blueprint $table) {
            $table->dropColumn(['oauth_server', 'region']);
        });
    }
};