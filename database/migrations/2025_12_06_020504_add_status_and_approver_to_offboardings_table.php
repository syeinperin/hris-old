<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('offboardings', function (Blueprint $table) {
            // Only add columns that do NOT exist yet
            if (!Schema::hasColumn('offboardings', 'supervisor_id')) {
                $table->unsignedBigInteger('supervisor_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('offboardings', 'hr_id')) {
                $table->unsignedBigInteger('hr_id')->nullable()->after('supervisor_id');
            }
            if (!Schema::hasColumn('offboardings', 'hr_remarks')) {
                $table->text('hr_remarks')->nullable()->after('status');
            }

            // Update status default if needed
            $table->string('status')->default('pending')->change();
        });
    }

    public function down()
    {
        Schema::table('offboardings', function (Blueprint $table) {
            $table->dropColumn(['supervisor_id', 'hr_id', 'hr_remarks']);
        });
    }
};
