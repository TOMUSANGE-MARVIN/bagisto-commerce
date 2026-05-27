<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ElectronicsSeeder extends Seeder
{
    public function run()
    {
        $this->seedCategories();
        $this->seedColors();
        $this->seedSizes();
        $this->seedBrands();
        $this->seedExtraAttributes();
        $this->command->info('✅ Electronics seeding complete!');
    }

    private function makeCategory($name, $parentId = null, $position = 1)
    {
        $existing = DB::table('category_translations')->where('name', $name)->first();
        if ($existing) return $existing->category_id;

        if ($parentId) {
            $parent = DB::table('categories')->where('id', $parentId)->first();
            DB::table('categories')->where('_lft', '>=', $parent->_rgt)->increment('_lft', 2);
            DB::table('categories')->where('_rgt', '>=', $parent->_rgt)->increment('_rgt', 2);
            $lft = $parent->_rgt; $rgt = $lft + 1;
        } else {
            $max = DB::table('categories')->max('_rgt') ?? 0;
            $lft = $max + 1; $rgt = $lft + 1;
        }

        $catId = DB::table('categories')->insertGetId([
            'position'     => $position,
            'status'       => 1,
            'display_mode' => 'products_and_description',
            '_lft'         => $lft,
            '_rgt'         => $rgt,
            'parent_id'    => $parentId,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $slug   = Str::slug($name);
        $parentTrans = $parentId ? DB::table('category_translations')->where('category_id', $parentId)->first() : null;

        DB::table('category_translations')->insert([
            'category_id' => $catId,
            'name'        => $name,
            'slug'        => $slug,
            'url_path'    => $parentTrans ? $parentTrans->slug . '/' . $slug : $slug,
            'locale'      => 'en',
        ]);

        return $catId;
    }

    private function addOption($attributeId, $adminName, $swatchValue = null, $sortOrder = 0)
    {
        $exists = DB::table('attribute_options')
            ->where('attribute_id', $attributeId)
            ->where('admin_name', $adminName)
            ->exists();
        if ($exists) return;

        $optId = DB::table('attribute_options')->insertGetId([
            'attribute_id' => $attributeId,
            'admin_name'   => $adminName,
            'swatch_value' => $swatchValue,
            'sort_order'   => $sortOrder,
        ]);

        DB::table('attribute_option_translations')->insert([
            'attribute_option_id' => $optId,
            'locale'              => 'en',
            'label'               => $adminName,
        ]);
    }

    private function ensureAttribute($code, $adminName, $type, $swatchType = null, $filterable = 1, $configurable = 1)
    {
        $existing = DB::table('attributes')->where('code', $code)->first();
        if ($existing) return $existing->id;

        $id = DB::table('attributes')->insertGetId([
            'code'                => $code,
            'admin_name'          => $adminName,
            'type'                => $type,
            'swatch_type'         => $swatchType,
            'is_filterable'       => $filterable,
            'is_comparable'       => 1,
            'is_configurable'     => $configurable,
            'is_user_defined'     => 1,
            'is_visible_on_front' => 1,
            'value_per_locale'    => 0,
            'value_per_channel'   => 0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        DB::table('attribute_translations')->insert([
            'attribute_id' => $id,
            'locale'       => 'en',
            'name'         => $adminName,
        ]);

        return $id;
    }

    private function seedCategories()
    {
        $this->command->info('Seeding categories...');
        $categories = [
            'Smartphones'  => ['iPhone', 'Samsung Galaxy', 'Google Pixel', 'OnePlus', 'Xiaomi', 'OPPO'],
            'Tablets'      => ['iPad', 'Samsung Tab', 'Lenovo Tab', 'Huawei MatePad'],
            'Laptops'      => ['MacBook', 'Dell XPS', 'HP Spectre', 'Lenovo ThinkPad', 'ASUS ZenBook'],
            'Smartwatches' => ['Apple Watch', 'Samsung Galaxy Watch', 'Garmin', 'Fitbit'],
            'Headphones'   => ['AirPods', 'Sony WH', 'Bose', 'Samsung Buds', 'JBL'],
            'Cameras'      => ['DSLR', 'Mirrorless', 'Action Cam', 'Instant Camera'],
            'Gaming'       => ['PlayStation', 'Xbox', 'Nintendo Switch', 'Gaming Accessories'],
            'Accessories'  => ['Cases & Covers', 'Chargers & Cables', 'Power Banks', 'Screen Protectors'],
        ];

        $pos = 1;
        foreach ($categories as $parentName => $subs) {
            $parentId = $this->makeCategory($parentName, null, $pos++);
            $this->command->line("  ✓ $parentName");
            foreach ($subs as $sub) {
                $this->makeCategory($sub, $parentId, $pos++);
                $this->command->line("      - $sub");
            }
        }
    }

    private function seedColors()
    {
        $this->command->info('Seeding colors...');
        DB::table('attributes')->where('id', 23)->update(['swatch_type' => 'color']);

        $colors = [
            ['Titanium Gray',    '#6B6B6B'],
            ['Space Black',      '#1C1C1E'],
            ['Starlight',        '#F2EFE7'],
            ['Midnight',         '#2C2C2E'],
            ['Deep Purple',      '#4B3869'],
            ['Gold',             '#F5D376'],
            ['Silver',           '#C8C8CC'],
            ['Rose Gold',        '#E8C4B8'],
            ['Coral Red',        '#E8473F'],
            ['Ocean Blue',       '#1A6B9A'],
            ['Sage Green',       '#8DB48E'],
            ['Pearl White',      '#F8F6F0'],
            ['Graphite',         '#41424C'],
            ['Natural Titanium', '#9A9186'],
            ['White Titanium',   '#E8E4DC'],
            ['Black Titanium',   '#3A3A3C'],
            ['Desert Titanium',  '#C4A882'],
            ['Ultramarine',      '#3D5A99'],
        ];

        foreach ($colors as $i => [$name, $hex]) {
            $this->addOption(23, $name, $hex, $i + 1);
            $this->command->line("  ✓ $name ($hex)");
        }
    }

    private function seedSizes()
    {
        $this->command->info('Seeding screen sizes...');
        $sizes = ['4.7"', '5.4"', '6.1"', '6.7"', '6.9"', '8.3"', '10.2"', '10.9"', '11"', '12.9"', '13"', '14"', '15.3"', '16"'];
        foreach ($sizes as $i => $size) {
            $this->addOption(24, $size, null, $i + 1);
            $this->command->line("  ✓ $size");
        }
    }

    private function seedBrands()
    {
        $this->command->info('Seeding brands...');
        $brands = ['Apple', 'Samsung', 'Google', 'Sony', 'OnePlus', 'Xiaomi', 'OPPO', 'Huawei', 'Lenovo', 'Dell', 'HP', 'ASUS', 'Bose', 'JBL', 'Garmin', 'Fitbit', 'Canon', 'Nikon', 'GoPro'];
        foreach ($brands as $i => $brand) {
            $this->addOption(25, $brand, null, $i + 1);
            $this->command->line("  ✓ $brand");
        }
    }

    private function seedExtraAttributes()
    {
        $this->command->info('Seeding extra attributes...');

        $ramId = $this->ensureAttribute('ram', 'RAM', 'select', null, 1, 1);
        foreach (['2GB', '4GB', '6GB', '8GB', '12GB', '16GB', '32GB'] as $i => $v)
            $this->addOption($ramId, $v, null, $i + 1);
        $this->command->line('  ✓ RAM');

        $storageId = $this->ensureAttribute('storage_capacity', 'Storage', 'select', null, 1, 1);
        foreach (['32GB', '64GB', '128GB', '256GB', '512GB', '1TB', '2TB'] as $i => $v)
            $this->addOption($storageId, $v, null, $i + 1);
        $this->command->line('  ✓ Storage');

        $connId = $this->ensureAttribute('connectivity', 'Connectivity', 'select', null, 1, 0);
        foreach (['4G LTE', '5G', 'Wi-Fi Only', 'Wi-Fi 6', 'Bluetooth 5.0'] as $i => $v)
            $this->addOption($connId, $v, null, $i + 1);
        $this->command->line('  ✓ Connectivity');

        $osId = $this->ensureAttribute('operating_system', 'Operating System', 'select', null, 1, 0);
        foreach (['iOS 17', 'iOS 18', 'Android 13', 'Android 14', 'Android 15', 'Windows 11', 'macOS Sequoia', 'watchOS 11', 'Wear OS'] as $i => $v)
            $this->addOption($osId, $v, null, $i + 1);
        $this->command->line('  ✓ Operating System');

        $battId = $this->ensureAttribute('battery_capacity', 'Battery Capacity', 'select', null, 0, 0);
        foreach (['2000mAh', '3000mAh', '4000mAh', '4500mAh', '5000mAh', '6000mAh'] as $i => $v)
            $this->addOption($battId, $v, null, $i + 1);
        $this->command->line('  ✓ Battery');

        $condId = $this->ensureAttribute('condition', 'Condition', 'select', null, 1, 0);
        foreach (['Brand New', 'Refurbished', 'Open Box'] as $i => $v)
            $this->addOption($condId, $v, null, $i + 1);
        $this->command->line('  ✓ Condition');
    }
}
