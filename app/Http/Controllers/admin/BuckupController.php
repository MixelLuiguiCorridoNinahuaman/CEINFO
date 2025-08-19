<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class BuckupController extends Controller
{
    public function generarBackup(Request $request)
    {
        Artisan::call('backup:run');
        $log = Artisan::output();

        Log::info('Backup generado desde panel admin.', ['log' => $log]);

        return back()->with([
            'toast_message' => 'Backup generado correctamente',
            'toast_type' => 'success'
        ]);
    }
}
