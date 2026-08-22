<?php

namespace App\Services\Admin;

use App\Enums\CourierEnum;
use App\Http\Resources\CourierSettingResource;
use App\Models\CourierSetting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class CourierSettingService
{
    public function index()
    {
        $settings = CourierSetting::all();

        return responseSuccess(CourierSettingResource::collection($settings));
    }

    public function updateOrCreate($request)
    {
        try {
            // Validate incoming payload
            $validator = Validator::make($request->all(), [
                'courier_name' => ['required', new Enum(CourierEnum::class)],
                'is_enabled' => ['required', 'boolean'],
                'is_default' => ['required', 'boolean'],
                'credentials' => ['required', 'array'],
            ]);

            if ($validator->fails()) {
                return responseError($validator->errors()->first(), 422);
            }

            $courierName = $request->input('courier_name');
            $isEnabled = $request->boolean('is_enabled');
            $isDefault = $request->boolean('is_default');
            $credentials = $request->input('credentials');

            // Business logic: Ensure at least one enabled courier is default, and only one is default.
            if (! $isEnabled) {
                $isDefault = false;
            } else {
                // If it is enabled, and not explicitly marked as default,
                // check if there is another courier currently enabled AND marked as default.
                if (! $isDefault) {
                    $hasOtherDefault = CourierSetting::where('courier_name', '!=', $courierName)
                        ->where('is_enabled', true)
                        ->where('is_default', true)
                        ->exists();

                    if (! $hasOtherDefault) {
                        $isDefault = true;
                    }
                }
            }

            // Find existing config or prepare new one
            $setting = CourierSetting::updateOrCreate(
                [
                    'courier_name' => $courierName,
                ],
                [
                    'is_enabled' => $isEnabled,
                    'is_default' => $isDefault,
                    'credentials' => $credentials,
                ]
            );

            // If this courier is set as default, disable default status for all other couriers
            if ($isDefault) {
                CourierSetting::where('courier_name', '!=', $courierName)
                    ->update(['is_default' => false]);
            } else {
                // If this courier is NOT default (e.g. it was just disabled or saved as non-default),
                // make sure there is still at least one default if there are any other enabled couriers.
                $hasDefault = CourierSetting::where('is_enabled', true)
                    ->where('is_default', true)
                    ->exists();

                if (! $hasDefault) {
                    $firstEnabled = CourierSetting::where('is_enabled', true)
                        ->first();
                    if ($firstEnabled) {
                        $firstEnabled->update(['is_default' => true]);
                    }
                }
            }

            return responseSuccess(
                CourierSettingResource::make($setting),
                ucfirst($courierName).' courier settings saved successfully.'
            );
        } catch (\Exception $e) {
            return responseError('Failed to save courier settings: '.$e->getMessage(), 500);
        }
    }
}
