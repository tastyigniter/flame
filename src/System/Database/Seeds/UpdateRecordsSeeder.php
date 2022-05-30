<?php

namespace Igniter\System\Database\Seeds;

use Igniter\Admin\Models\Category;
use Igniter\Admin\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fill newly created permalink_slug column with values from permalinks table
 * Truncate the permalinks table
 */
class UpdateRecordsSeeder extends Seeder
{
    /**
     * Run the demo schema seeds.
     * @return void
     */
    public function run()
    {
        $this->updateMorphsOnStatusHistory();

        $this->fixPermalinkSlugColumns();

        $this->fillColumnsOnMailTemplatesData();
    }

    protected function updateMorphsOnStatusHistory()
    {
        if (DB::table('status_history')->where('object_type', \Igniter\Admin\Models\Order::class)->count())
            return;

        $morphs = [
            'order' => \Igniter\Admin\Models\Order::class,
            'reserve' => \Igniter\Admin\Models\Reservation::class,
        ];

        DB::table('status_history')->get()->each(function ($model) use ($morphs) {
            $status = DB::table('statuses')->where('status_id', $model->status_id)->first();
            if (!$status || !isset($morphs[$status->status_for]))
                return false;

            DB::table('status_history')->where('status_history_id', $model->status_history_id)->update([
                'object_type' => $morphs[$status->status_for],
            ]);
        });
    }

    protected function fixPermalinkSlugColumns()
    {
        Category::all()->each(function (Category $model) {
            $model->save();
        });

        Location::all()->each(function (Location $model) {
            $model->save();
        });
    }

    protected function fillColumnsOnMailTemplatesData()
    {
        DB::table('mail_templates')->update(['is_custom' => 1]);
    }
}
