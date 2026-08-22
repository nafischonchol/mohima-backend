<?php

namespace App\Services\Admin;

use App\Http\Requests\Admin\Attribute\StoreAttributeRequest;
use App\Http\Requests\Admin\Attribute\UpdateAttributeRequest;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use Illuminate\Support\Facades\DB;

class AttributeService
{
    public function index()
    {
        $attributes = Attribute::latest()->get();

        return responseSuccess(AttributeResource::collection($attributes));
    }

    public function show(string $id)
    {
        $attribute = Attribute::findOrFail($id);

        return responseSuccess(AttributeResource::make($attribute));
    }

    public function store(StoreAttributeRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            // Set is_active and is_default_specification correctly
            $data['is_active'] = $request->boolean('is_active', true);
            $data['is_default_specification'] = $request->boolean('is_default_specification', false);

            // Clean values if type is text or rich_text
            if ($data['type'] === 'text' || $data['type'] === 'rich_text') {
                $data['values'] = null;
            }

            $attribute = Attribute::create($data);
            $this->syncAttributeValues($attribute, $data['values'] ?? []);

            DB::commit();

            return responseSuccess(AttributeResource::make($attribute), 'Attribute created successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return responseError('Failed to create attribute: '.$e->getMessage(), 500);
        }
    }

    public function update(Attribute $attribute, UpdateAttributeRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            // Set is_active and is_default_specification correctly
            $data['is_active'] = $request->boolean('is_active', true);
            $data['is_default_specification'] = $request->boolean('is_default_specification', false);

            // Clean values if type is text or rich_text
            if ($data['type'] === 'text' || $data['type'] === 'rich_text') {
                $data['values'] = null;
            }

            $attribute->update($data);
            $this->syncAttributeValues($attribute, $data['values'] ?? []);

            DB::commit();

            return responseSuccess(AttributeResource::make($attribute), 'Attribute updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return responseError('Failed to update attribute: '.$e->getMessage(), 500);
        }
    }

    private function syncAttributeValues(Attribute $attribute, ?array $values): void
    {
        if (is_null($values)) {
            $values = [];
        }

        $values = array_map('trim', $values);
        $values = array_filter($values, fn ($val) => $val !== '');

        // Fetch existing attribute values (including soft-deleted ones)
        $existingValues = $attribute->attributeValues()->withTrashed()->get();
        $existingByValue = $existingValues->keyBy('value');

        $processedIds = [];

        foreach ($values as $valString) {
            if ($existingByValue->has($valString)) {
                $existingVal = $existingByValue->get($valString);
                if ($existingVal->trashed()) {
                    $existingVal->restore();
                }
                $processedIds[] = $existingVal->id;
            } else {
                $newVal = $attribute->attributeValues()->create([
                    'value' => $valString,
                ]);
                $processedIds[] = $newVal->id;
            }
        }

        // Soft-delete any existing attribute values that are NOT in the processed list
        $attribute->attributeValues()
            ->whereNotIn('id', $processedIds)
            ->delete();
    }
}
