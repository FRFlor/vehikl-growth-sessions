<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZoomMeetingIdColumnToGrowthSessionsTable extends Migration
{
    public function up()
    {
        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->bigInteger('zoom_meeting_id')->unsigned()->nullable();
        });
    }

    public function down()
    {
        if (Schema::hasColumn('growth_sessions', 'zoom_meeting_id')) {
            Schema::table('growth_sessions', function (Blueprint $table) {
                $table->dropColumn('zoom_meeting_id');
            });
        }
    }
}
