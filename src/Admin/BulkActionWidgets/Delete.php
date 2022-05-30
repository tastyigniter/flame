<?php

namespace Igniter\Admin\BulkActionWidgets;

use Igniter\Admin\Classes\BaseBulkActionWidget;
use Illuminate\Support\Facades\DB;

class Delete extends BaseBulkActionWidget
{
    public function handleAction($requestData, $records)
    {
        // Delete records
        if ($count = $records->count()) {
            DB::transaction(function () use ($records) {
                foreach ($records as $record) {
                    $record->delete();
                }
            });

            $prefix = ($count > 1) ? ' records' : 'record';
            flash()->success(sprintf(lang('igniter::admin.alert_success'), '['.$count.']'.$prefix.' '.lang('igniter::admin.text_deleted')));
        }
        else {
            flash()->warning(sprintf(lang('igniter::admin.alert_error_nothing'), lang('igniter::admin.text_deleted')));
        }
    }
}
