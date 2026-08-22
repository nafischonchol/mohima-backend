<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CosmeticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedCategories();
        $this->seedBrands();
        $this->seedAttributes();
    }

    /**
     * Seed Korean Cosmetics & Skincare Categories
     */
    private function seedCategories(): void
    {
        $categoriesData = [
            [
                'name' => 'Skincare',
                'meta_title' => 'Korean Skincare Products',
                'meta_description' => 'Explore authentic Korean skincare essentials including cleansers, toners, serums, and moisturizers.',
                'children' => [
                    ['name' => 'Cleansers & Makeup Removers'],
                    ['name' => 'Toners & Mists'],
                    ['name' => 'Essences, Serums & Ampoules'],
                    ['name' => 'Moisturizers & Creams'],
                    ['name' => 'Sun Care & Sunscreens'],
                    ['name' => 'Eye Care'],
                    ['name' => 'Sheet Masks & Sleeping Packs'],
                    ['name' => 'Exfoliators & Peels'],
                ],
            ],
            [
                'name' => 'Makeup',
                'meta_title' => 'Korean Makeup & Beauty',
                'meta_description' => 'Discover trending Korean cosmetics, cushion foundations, lip tints, and eye makeup.',
                'children' => [
                    ['name' => 'Cushions & BB Creams'],
                    ['name' => 'Lip Tints & Lipsticks'],
                    ['name' => 'Blushes & Highlighters'],
                    ['name' => 'Eyeliner & Mascara'],
                    ['name' => 'Eye Shadow Palettes'],
                    ['name' => 'Primers & Setting Powders'],
                ],
            ],
            [
                'name' => 'Hair & Body Care',
                'meta_title' => 'Hair & Body Care Products',
                'meta_description' => 'Nourishing scalp care, hair serums, and hydrating body lotions.',
                'children' => [
                    ['name' => 'Scalp & Shampoo Care'],
                    ['name' => 'Hair Serums & Oils'],
                    ['name' => 'Body Washes & Lotions'],
                    ['name' => 'Hand & Foot Care'],
                ],
            ],
        ];

        foreach ($categoriesData as $parentCat) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($parentCat['name'])],
                [
                    'name' => $parentCat['name'],
                    'parent_id' => null,
                    'is_active' => true,
                    'meta_title' => $parentCat['meta_title'] ?? $parentCat['name'],
                    'meta_description' => $parentCat['meta_description'] ?? null,
                ]
            );

            if (isset($parentCat['children'])) {
                foreach ($parentCat['children'] as $childCat) {
                    Category::updateOrCreate(
                        ['slug' => Str::slug($childCat['name'])],
                        [
                            'name' => $childCat['name'],
                            'parent_id' => $parent->id,
                            'is_active' => true,
                            'meta_title' => $childCat['name'],
                        ]
                    );
                }
            }
        }

        $this->command->info('Korean Cosmetics Categories seeded successfully.');
    }

    /**
     * Seed Top Korean Cosmetics & Skincare Brands
     */
    private function seedBrands(): void
    {
        $brands = [
            'COSRX',
            'Beauty of Joseon',
            'Anua',
            'Laneige',
            'Innisfree',
            'Etude House',
            'Missha',
            'Some By Mi',
            'SKIN1004',
            'Sulwhasoo',
            'Torriden',
            'rom&nd',
            'Peripera',
            'Round Lab',
            'Pyunkang Yul',
            'Mediheal',
            'Dr.Jart+',
            'Dear, Klairs',
            'Isntree',
            'AHC',
        ];

        foreach ($brands as $brandName) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($brandName)],
                [
                    'name' => $brandName,
                    'is_active' => true,
                    'meta_title' => $brandName.' Official K-Beauty Products',
                    'meta_description' => 'Buy 100% authentic '.$brandName.' Korean skincare and cosmetics.',
                ]
            );
        }

        $this->command->info('Korean Cosmetics Brands seeded successfully.');
    }

    /**
     * Seed Cosmetic Attributes & Attribute Values
     */
    private function seedAttributes(): void
    {
        $attributesData = [
            [
                'name' => 'Skin Type',
                'type' => 'multi_select',
                'values' => ['All Skin Types', 'Dry', 'Oily', 'Combination', 'Sensitive', 'Acne-Prone'],
            ],
            [
                'name' => 'Skin Concern',
                'type' => 'multi_select',
                'values' => ['Acne & Blemishes', 'Anti-Aging & Wrinkles', 'Dark Spots & Hyperpigmentation', 'Dryness & Dehydration', 'Redness & Irritation', 'Pore Care & Oil Control'],
            ],
            [
                'name' => 'Key Ingredient',
                'type' => 'rich_text',
                'values' => ['Snail Mucin', 'Centella Asiatica (Cica)', 'Hyaluronic Acid', 'Niacinamide', 'Heartleaf', 'Ginseng', 'Retinol / Retinoid', 'Propolis & Honey', 'Tea Tree', 'BHA / Salicylic Acid'],
            ],
            [
                'name' => 'Finish Type',
                'type' => 'multi_select',
                'values' => ['Dewy / Glowy', 'Matte', 'Semi-Matte', 'Natural / Satin'],
            ],
            [
                'name' => 'Formulation',
                'type' => 'multi_select',
                'values' => ['Cream', 'Gel', 'Serum / Liquid', 'Balm', 'Oil', 'Stick', 'Sheet Mask'],
            ],
            [
                'name' => 'SPF Rating',
                'type' => 'multi_select',
                'values' => ['SPF 30', 'SPF 50+ PA++++', 'SPF 50+ PA+++'],
            ],
            [
                'name' => 'Volume / Size',
                'type' => 'multi_select',
                'values' => ['30ml', '50ml', '100ml', '150ml', '200ml', '250ml', '500ml', '1 Sheet', '10 Sheets'],
            ],
            [
                'name' => 'Shade / Color',
                'type' => 'multi_select',
                'values' => ['#21 Light Beige', '#23 Natural Beige', '#25 Warm Beige', 'Clear / Transparent'],
            ],
        ];

        foreach ($attributesData as $attrData) {
            $attribute = Attribute::updateOrCreate(
                ['name' => $attrData['name']],
                [
                    'type' => $attrData['type'],
                    'values' => $attrData['values'],
                    'is_active' => true,
                ]
            );

            // Sync attribute values to AttributeValue model
            $existingValues = $attribute->attributeValues()->withTrashed()->pluck('value', 'id')->toArray();
            $processedValues = [];

            foreach ($attrData['values'] as $valString) {
                $existingId = array_search($valString, $existingValues);
                if ($existingId !== false) {
                    $attrVal = AttributeValue::withTrashed()->find($existingId);
                    if ($attrVal && $attrVal->trashed()) {
                        $attrVal->restore();
                    }
                    $processedValues[] = $existingId;
                } else {
                    $newVal = $attribute->attributeValues()->create([
                        'value' => $valString,
                    ]);
                    $processedValues[] = $newVal->id;
                }
            }

            // Clean up old removed ones if any
            $attribute->attributeValues()->whereNotIn('id', $processedValues)->delete();
        }

        $this->command->info('Cosmetics Attributes & Values seeded successfully.');
    }
}
