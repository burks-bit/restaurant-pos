<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Carbon\Carbon;

class PhilippineHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $year = 2026;

        $holidays = [

            // REGULAR HOLIDAYS
            [
                'name' => 'New Year\'s Day',
                'holiday_date' => Carbon::create($year, 1, 1),
                'type' => 'regular',
            ],
            [
                'name' => 'Araw ng Kagitingan',
                'holiday_date' => Carbon::create($year, 4, 9),
                'type' => 'regular',
            ],
            [
                'name' => 'Labor Day',
                'holiday_date' => Carbon::create($year, 5, 1),
                'type' => 'regular',
            ],
            [
                'name' => 'Independence Day',
                'holiday_date' => Carbon::create($year, 6, 12),
                'type' => 'regular',
            ],
            [
                'name' => 'National Heroes Day',
                'holiday_date' => Carbon::parse("last monday of august $year"),
                'type' => 'regular',
            ],
            [
                'name' => 'Bonifacio Day',
                'holiday_date' => Carbon::create($year, 11, 30),
                'type' => 'regular',
            ],
            [
                'name' => 'Christmas Day',
                'holiday_date' => Carbon::create($year, 12, 25),
                'type' => 'regular',
            ],
            [
                'name' => 'Rizal Day',
                'holiday_date' => Carbon::create($year, 12, 30),
                'type' => 'regular',
            ],

            // SPECIAL (NON-WORKING) HOLIDAYS
            [
                'name' => 'Chinese New Year',
                'holiday_date' => Carbon::create($year, 2, 17), // update yearly if needed
                'type' => 'special',
            ],
            [
                'name' => 'EDSA People Power Revolution Anniversary',
                'holiday_date' => Carbon::create($year, 2, 25),
                'type' => 'special',
            ],
            [
                'name' => 'Ninoy Aquino Day',
                'holiday_date' => Carbon::create($year, 8, 21),
                'type' => 'special',
            ],
            [
                'name' => 'All Saints\' Day',
                'holiday_date' => Carbon::create($year, 11, 1),
                'type' => 'special',
            ],
            [
                'name' => 'Feast of the Immaculate Conception',
                'holiday_date' => Carbon::create($year, 12, 8),
                'type' => 'special',
            ],
            [
                'name' => 'Last Day of the Year',
                'holiday_date' => Carbon::create($year, 12, 31),
                'type' => 'special',
            ],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['holiday_date' => $holiday['holiday_date']],
                $holiday
            );
        }
    }
}
