<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPiColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pi_id')->nullable()->after('id');
            $table->string('pi_access_token')->nullable()->after('pi_id');
            $table->string('pi_refresh_token')->nullable()->after('pi_access_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pi_id', 'pi_access_token', 'pi_refresh_token']);
        });
    }
}
