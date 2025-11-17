<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
    public function run()
    {
        $year = 2025;

        // Compute movable dates (Holy Week)
        $easter = Carbon::createFromTimestamp(easter_date($year));
        $maundyThursday = $easter->copy()->subDays(3);
        $goodFriday     = $easter->copy()->subDays(2);

        /** 
         * DOLE-Classified Holidays (2025)
         * type = regular | special
         */

        $holidays = [

            // ───── REGULAR HOLIDAYS (with pay even if absent) ─────
            ['name' => 'New Year’s Day',        'date' => "$year-01-01", 'type' => 'regular'],
            ['name' => 'Maundy Thursday',       'date' => $maundyThursday->toDateString(), 'type' => 'regular'],
            ['name' => 'Good Friday',           'date' => $goodFriday->toDateString(),     'type' => 'regular'],
            ['name' => 'Araw ng Kagitingan',    'date' => "$year-04-09", 'type' => 'regular'],
            ['name' => 'Labor Day',             'date' => "$year-05-01", 'type' => 'regular'],
            ['name' => 'Independence Day',      'date' => "$year-06-12", 'type' => 'regular'],
            ['name' => 'National Heroes Day',   'date' => Carbon::parse("last monday of august $year")->toDateString(), 'type' => 'regular'],
            ['name' => 'Bonifacio Day',         'date' => "$year-11-30", 'type' => 'regular'],
            ['name' => 'Christmas Day',         'date' => "$year-12-25", 'type' => 'regular'],
            ['name' => 'Rizal Day',             'date' => "$year-12-30", 'type' => 'regular'],

            // ───── SPECIAL NON-WORKING HOLIDAYS (no work = no pay) ─────
            ['name' => 'Chinese New Year',      'date' => "$year-01-29", 'type' => 'special'],
            ['name' => 'EDSA People Power',     'date' => "$year-02-25", 'type' => 'special'],
            ['name' => 'Black Saturday',        'date' => $easter->copy()->subDay()->toDateString(), 'type' => 'special'],
            ['name' => 'Ninoy Aquino Day',      'date' => "$year-08-21", 'type' => 'special'],
            ['name' => 'All Saints’ Day',       'date' => "$year-11-01", 'type' => 'special'],
            ['name' => 'All Souls’ Day',        'date' => "$year-11-02", 'type' => 'special'],
            ['name' => 'Feast of the Immaculate Conception', 'date' => "$year-12-08", 'type' => 'special'],
            ['name' => 'Christmas Eve',         'date' => "$year-12-24", 'type' => 'special'],
            ['name' => 'Last Day of the Year',  'date' => "$year-12-31", 'type' => 'special'],

        ];

        foreach ($holidays as $h) {
            Holiday::updateOrCreate(
                ['date' => Carbon::parse($h['date'])->toDateString()],
                ['name' => $h['name'], 'type' => $h['type'], 'is_recurring' => false]
            );
        }
    }
}
