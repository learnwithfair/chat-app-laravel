<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EmailSettingsController extends Controller
{
    public function getSettings()
    {
        $keys = ['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name'];
        $settings = DB::table('settings')->whereIn('key', $keys)->pluck('value', 'key');

        if ($settings->has('mail_password') && !empty($settings['mail_password'])) {
            try {
                $settings['mail_password'] = Crypt::decryptString($settings['mail_password']);
            } catch (\Exception $e) {
                $settings['mail_password'] = '';
            }
        }
        return response()->json($settings);
    }

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'mail_host' => 'required|string',
            'mail_port' => 'required|integer',
            'mail_username' => 'required|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        $settingsToSave = $validated;
        $settingsToSave['mail_mailer'] = 'smtp';

        if ($request->filled('mail_password')) {
            $settingsToSave['mail_password'] = Crypt::encryptString($validated['mail_password']);
        } else {
            unset($settingsToSave['mail_password']);
        }

        foreach ($settingsToSave as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Email settings saved successfully!']);
    }
}