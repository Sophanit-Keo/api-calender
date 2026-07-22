<?php

namespace Database\Seeders;

use App\Models\WorkShiftTemplate;
use Illuminate\Database\Seeder;

class GlobalShiftTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            ['code' => '8', 'name' => 'Shift 1 (06:00-14:00)', 'category' => 'shift', 'start_time' => '06:00', 'end_time' => '14:00', 'color' => 'blue', 'sort_order' => 1],
            ['code' => '8N', 'name' => 'Shift 2 (14:00-22:00)', 'category' => 'shift', 'start_time' => '14:00', 'end_time' => '22:00', 'color' => 'green', 'sort_order' => 2],
            ['code' => '8D', 'name' => 'Shift 3 (22:00-06:00)', 'category' => 'shift', 'start_time' => '22:00', 'end_time' => '06:00', 'color' => 'amber', 'sort_order' => 3],
            ['code' => 'AL', 'name' => 'Annual Leave', 'category' => 'leave', 'start_time' => null, 'end_time' => null, 'color' => 'red', 'sort_order' => 4],
            ['code' => 'AL~4', 'name' => 'Annual Leave (4 hours)', 'category' => 'leave', 'start_time' => null, 'end_time' => null, 'color' => 'red', 'sort_order' => 5],
            ['code' => 'ML', 'name' => 'Medical Leave', 'category' => 'leave', 'start_time' => null, 'end_time' => null, 'color' => 'red', 'sort_order' => 6],
        ];

        foreach ($templates as $template) {
            WorkShiftTemplate::query()->updateOrCreate(
                ['user_id' => null, 'code' => $template['code']],
                $template,
            );
        }
    }
}
