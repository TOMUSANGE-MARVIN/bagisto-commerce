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

    private function makeCategory($name, $parentId = null, $position = 1, $seo = [])
    {
        $existing = DB::table('category_translations')->where('name', $name)->first();
        if ($existing) {
            // Update SEO if provided
            if (!empty($seo)) {
                DB::table('category_translations')->where('id', $existing->id)->update([
                    'meta_title'       => $seo[0] ?? null,
                    'meta_description' => $seo[1] ?? null,
                    'meta_keywords'    => $seo[2] ?? null,
                ]);
            }
            return $existing->category_id;
        }

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
            'parent_id'    => $parentId ?? 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $slug        = Str::slug($name);
        $parentTrans = $parentId ? DB::table('category_translations')->where('category_id', $parentId)->first() : null;

        DB::table('category_translations')->insert([
            'category_id'      => $catId,
            'name'             => $name,
            'slug'             => $slug,
            'url_path'         => $parentTrans ? $parentTrans->slug . '/' . $slug : $slug,
            'locale'           => 'en',
            'meta_title'       => $seo[0] ?? null,
            'meta_description' => $seo[1] ?? null,
            'meta_keywords'    => $seo[2] ?? null,
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
            'Smartphones' => [
                'seo' => ['Buy Smartphones in Uganda | Best Prices | Mutindo Express', 'Buy smartphones in Uganda at the best prices. Shop iPhone, Samsung, Tecno, Infinix, Google Pixel and more. Fast delivery in Kampala and across Uganda. Pay on delivery available.', 'buy smartphones Uganda, phones Kampala, cheap smartphones Uganda, iPhone Uganda, Samsung Uganda, Tecno Uganda, mobile phones Uganda, Mutindo Express'],
                'subs' => [
                    'iPhone'         => ['Buy iPhone in Uganda | All Models | Best Price | Mutindo Express', 'Buy original iPhone in Uganda - iPhone 15, 14, 13 and more. Genuine Apple phones at competitive prices in Kampala. Fast delivery across Uganda. Pay on delivery.', 'buy iPhone Uganda, iPhone price Uganda, iPhone 15 Uganda, original iPhone Kampala, Apple iPhone Uganda, Mutindo Express'],
                    'Samsung Galaxy' => ['Buy Samsung Galaxy Phones in Uganda | Mutindo Express', 'Buy Samsung Galaxy phones in Uganda - Galaxy S24, A55, Z Fold and more. Genuine Samsung smartphones at best prices in Kampala. Delivery countrywide.', 'buy Samsung Galaxy Uganda, Samsung phone Uganda, Galaxy S24 Uganda, Samsung Kampala, Mutindo Express'],
                    'Google Pixel'   => ['Buy Google Pixel Phones in Uganda | Mutindo Express', 'Buy Google Pixel phones in Uganda. Pixel 8, Pixel 7 - pure Android with the best camera experience. Available in Kampala with countrywide delivery.', 'buy Google Pixel Uganda, Pixel phone Uganda, Android phone Uganda, Google phone Kampala, Mutindo Express'],
                    'OnePlus'        => ['Buy OnePlus Phones in Uganda | Fast Charging | Mutindo Express', 'Buy OnePlus smartphones in Uganda. Flagship performance with ultra-fast charging at competitive prices. Delivered in Kampala and across Uganda.', 'buy OnePlus Uganda, OnePlus phone Uganda, fast charging phone Uganda, OnePlus Kampala, Mutindo Express'],
                    'Xiaomi'         => ['Buy Xiaomi & Redmi Phones in Uganda | Mutindo Express', 'Buy Xiaomi, Redmi and POCO phones in Uganda. Premium features at affordable prices. Best value smartphones in Kampala. Countrywide delivery available.', 'buy Xiaomi Uganda, Redmi Uganda, cheap smartphones Uganda, Xiaomi Kampala, POCO Uganda, Mutindo Express'],
                    'OPPO'           => ['Buy OPPO Phones in Uganda | Mutindo Express', 'Buy OPPO smartphones in Uganda - Find X, Reno and A series. Great cameras and fast charging at the best prices in Kampala and across Uganda.', 'buy OPPO Uganda, OPPO phone Uganda, OPPO Kampala, OPPO Reno Uganda, Mutindo Express'],
                ],
            ],
            'Tablets' => [
                'seo' => ['Buy Tablets & iPads in Uganda | Mutindo Express', 'Shop tablets and iPads in Uganda. iPad, Samsung Tab, Huawei MatePad and more at affordable prices. Delivery across Kampala and Uganda. Order online today.', 'buy tablets Uganda, iPad Uganda, Samsung Tab Uganda, tablets Kampala, cheap tablets Uganda, Mutindo Express'],
                'subs' => [
                    'iPad'           => ['Buy iPad in Uganda | iPad Pro, Air, Mini | Mutindo Express', 'Buy original Apple iPad in Uganda. iPad Pro, iPad Air, iPad mini and standard iPad. Best iPad prices in Kampala with delivery across Uganda.', 'buy iPad Uganda, iPad price Uganda, iPad Pro Uganda, iPad Air Uganda, Apple iPad Kampala, Mutindo Express'],
                    'Samsung Tab'    => ['Buy Samsung Galaxy Tab in Uganda | Mutindo Express', 'Buy Samsung Galaxy Tab S and A series tablets in Uganda at best prices in Kampala. Delivery countrywide.', 'buy Samsung Tab Uganda, Galaxy Tab Uganda, Samsung tablet Kampala, Android tablet Uganda, Mutindo Express'],
                    'Lenovo Tab'     => ['Buy Lenovo Tablets in Uganda | Mutindo Express', 'Buy Lenovo Tab P and M series tablets in Uganda. Affordable and powerful Android tablets at great prices in Kampala.', 'buy Lenovo Tab Uganda, Lenovo tablet Uganda, Android tablet Kampala, affordable tablet Uganda, Mutindo Express'],
                    'Huawei MatePad' => ['Buy Huawei MatePad in Uganda | Mutindo Express', 'Buy Huawei MatePad Pro and MatePad tablets in Uganda at competitive prices. Premium displays, great performance. Delivery in Kampala and nationwide.', 'buy Huawei MatePad Uganda, Huawei tablet Uganda, MatePad Pro Uganda, Huawei Kampala, Mutindo Express'],
                ],
            ],
            'Laptops' => [
                'seo' => ['Buy Laptops in Uganda | MacBook, Dell, HP | Mutindo Express', 'Buy laptops in Uganda at great prices. MacBook, Dell XPS, HP, Lenovo ThinkPad, ASUS ZenBook available. Fast delivery in Kampala. Best laptop deals in Uganda.', 'buy laptops Uganda, laptops Kampala, MacBook Uganda, Dell laptop Uganda, HP laptop Uganda, Lenovo Uganda, cheap laptops Uganda, Mutindo Express'],
                'subs' => [
                    'MacBook'          => ['Buy MacBook in Uganda | MacBook Pro & Air | Mutindo Express', 'Buy MacBook Pro and MacBook Air in Uganda. Apple Silicon M1 M2 M3 laptops at the best prices in Kampala. Original MacBooks with delivery countrywide.', 'buy MacBook Uganda, MacBook Pro Uganda, MacBook Air Uganda, Apple laptop Uganda, MacBook Kampala, Mutindo Express'],
                    'Dell XPS'         => ['Buy Dell XPS Laptops in Uganda | Mutindo Express', 'Buy Dell XPS 13 and XPS 15 laptops in Uganda. Premium thin and light laptops for professionals in Kampala. Best Dell prices with nationwide delivery.', 'buy Dell laptop Uganda, Dell XPS Uganda, Dell Kampala, professional laptop Uganda, Mutindo Express'],
                    'HP Spectre'       => ['Buy HP Laptops in Uganda | HP Spectre & Envy | Mutindo Express', 'Buy HP Spectre, HP Envy and HP EliteBook laptops in Uganda at best prices in Kampala. Delivery across Uganda.', 'buy HP laptop Uganda, HP Spectre Uganda, HP Envy Uganda, HP Kampala, Mutindo Express'],
                    'Lenovo ThinkPad'  => ['Buy Lenovo ThinkPad in Uganda | Business Laptops | Mutindo Express', 'Buy Lenovo ThinkPad business laptops in Uganda. Reliable, durable laptops for professionals and students in Kampala. Countrywide delivery.', 'buy Lenovo ThinkPad Uganda, business laptop Uganda, Lenovo laptop Kampala, ThinkPad Uganda, Mutindo Express'],
                    'ASUS ZenBook'     => ['Buy ASUS Laptops in Uganda | ZenBook | Mutindo Express', 'Buy ASUS ZenBook laptops in Uganda. Slim, stylish and powerful at competitive prices in Kampala. Delivery across Uganda.', 'buy ASUS laptop Uganda, ASUS ZenBook Uganda, ASUS Kampala, slim laptop Uganda, Mutindo Express'],
                ],
            ],
            'Smartwatches' => [
                'seo' => ['Buy Smartwatches in Uganda | Apple Watch, Samsung | Mutindo Express', 'Shop smartwatches and fitness trackers in Uganda. Apple Watch, Samsung Galaxy Watch, Garmin, Fitbit at the best prices. Delivery in Kampala and nationwide.', 'buy smartwatches Uganda, Apple Watch Uganda, Samsung watch Uganda, fitness tracker Uganda, smartwatch Kampala, Mutindo Express'],
                'subs' => [
                    'Apple Watch'          => ['Buy Apple Watch in Uganda | Series 9, Ultra | Mutindo Express', 'Buy Apple Watch in Uganda - Series 9, Ultra 2 and SE. Health and fitness on your wrist. Best prices in Kampala with delivery countrywide.', 'buy Apple Watch Uganda, Apple Watch price Uganda, Apple Watch Series 9 Uganda, Apple Watch Kampala, Mutindo Express'],
                    'Samsung Galaxy Watch' => ['Buy Samsung Galaxy Watch in Uganda | Mutindo Express', 'Buy Samsung Galaxy Watch 6 and Watch 5 in Uganda at great prices in Kampala. Delivery countrywide.', 'buy Samsung Galaxy Watch Uganda, Samsung watch Uganda, Galaxy Watch Kampala, Mutindo Express'],
                    'Garmin'               => ['Buy Garmin Watches in Uganda | GPS Sports Watches | Mutindo Express', 'Buy Garmin Fenix, Forerunner GPS watches in Uganda. Best sports watches for athletes in Kampala. Delivery across Uganda.', 'buy Garmin Uganda, Garmin watch Uganda, GPS watch Uganda, sports watch Uganda, Mutindo Express'],
                    'Fitbit'               => ['Buy Fitbit in Uganda | Fitness Trackers | Mutindo Express', 'Buy Fitbit Charge, Sense and Versa fitness trackers in Uganda. Track your health and fitness at best prices in Kampala.', 'buy Fitbit Uganda, fitness tracker Uganda, Fitbit Kampala, health tracker Uganda, Mutindo Express'],
                ],
            ],
            'Headphones' => [
                'seo' => ['Buy Headphones & Earbuds in Uganda | AirPods, Sony, Bose | Mutindo Express', 'Shop headphones, earbuds and speakers in Uganda. AirPods, Sony, Bose, JBL, Samsung Buds at amazing prices. Delivery in Kampala and all over Uganda.', 'buy headphones Uganda, AirPods Uganda, earbuds Uganda, Bose Uganda, JBL Uganda, wireless headphones Kampala, Mutindo Express'],
                'subs' => [
                    'AirPods'      => ['Buy AirPods in Uganda | AirPods Pro & Max | Mutindo Express', 'Buy original Apple AirPods in Uganda - AirPods Pro, AirPods 3rd gen and AirPods Max. Best prices in Kampala with delivery across Uganda.', 'buy AirPods Uganda, AirPods Pro Uganda, AirPods price Uganda, Apple earbuds Uganda, AirPods Kampala, Mutindo Express'],
                    'Sony WH'      => ['Buy Sony Headphones in Uganda | Noise Cancelling | Mutindo Express', 'Buy Sony WH-1000XM5 and Sony headphones in Uganda. Industry-leading noise cancellation at best prices in Kampala.', 'buy Sony headphones Uganda, Sony WH-1000XM5 Uganda, noise cancelling headphones Uganda, Sony Kampala, Mutindo Express'],
                    'Bose'         => ['Buy Bose Headphones & Speakers in Uganda | Mutindo Express', 'Buy Bose QuietComfort and SoundLink headphones and speakers in Uganda at competitive prices in Kampala.', 'buy Bose Uganda, Bose headphones Uganda, Bose QuietComfort Uganda, premium headphones Uganda, Mutindo Express'],
                    'Samsung Buds' => ['Buy Samsung Galaxy Buds in Uganda | Mutindo Express', 'Buy Samsung Galaxy Buds 2 Pro and Buds Live in Uganda at best prices in Kampala. Delivery countrywide.', 'buy Samsung Buds Uganda, Galaxy Buds Uganda, Samsung earbuds Uganda, wireless earbuds Kampala, Mutindo Express'],
                    'JBL'          => ['Buy JBL Headphones & Speakers in Uganda | Mutindo Express', 'Buy JBL Tune, Flip, Charge and Xtreme in Uganda. Powerful bass-heavy sound at best JBL prices in Kampala.', 'buy JBL Uganda, JBL speaker Uganda, JBL headphones Uganda, portable speaker Uganda, Mutindo Express'],
                ],
            ],
            'Cameras' => [
                'seo' => ['Buy Cameras in Uganda | DSLR, GoPro, Canon | Mutindo Express', 'Buy digital cameras in Uganda. Canon, Nikon, Sony DSLR, mirrorless, GoPro action cameras at competitive prices. Delivery to your door in Kampala and across Uganda.', 'buy cameras Uganda, DSLR Uganda, Canon Uganda, Nikon Uganda, GoPro Uganda, cameras Kampala, photography equipment Uganda, Mutindo Express'],
                'subs' => [
                    'DSLR'          => ['Buy DSLR Cameras in Uganda | Canon, Nikon | Mutindo Express', 'Buy DSLR cameras in Uganda from Canon, Nikon and Sony. Professional photography equipment at best prices in Kampala.', 'buy DSLR camera Uganda, Canon DSLR Uganda, Nikon DSLR Uganda, camera Kampala, professional camera Uganda, Mutindo Express'],
                    'Mirrorless'    => ['Buy Mirrorless Cameras in Uganda | Sony, Canon | Mutindo Express', 'Buy mirrorless cameras in Uganda - Sony Alpha, Fujifilm, Canon EOS R at great prices in Kampala.', 'buy mirrorless camera Uganda, Sony mirrorless Uganda, Canon mirrorless Uganda, camera Kampala, Mutindo Express'],
                    'Action Cam'    => ['Buy Action Cameras in Uganda | GoPro | Mutindo Express', 'Buy GoPro HERO and DJI Osmo action cameras in Uganda. Capture adventures in 4K at best prices in Kampala.', 'buy GoPro Uganda, action camera Uganda, GoPro HERO Uganda, DJI Uganda, adventure camera Kampala, Mutindo Express'],
                    'Instant Camera'=> ['Buy Instant Cameras in Uganda | Fujifilm Instax | Mutindo Express', 'Buy Fujifilm Instax and Polaroid instant cameras in Uganda. Print your memories on the spot at best prices in Kampala.', 'buy instant camera Uganda, Fujifilm Instax Uganda, Polaroid Uganda, photo printer camera Kampala, Mutindo Express'],
                ],
            ],
            'Gaming' => [
                'seo' => ['Buy Gaming Consoles in Uganda | PS5, Xbox, Nintendo | Mutindo Express', 'Buy PlayStation, Xbox, Nintendo Switch and gaming accessories in Uganda. Best gaming deals in Kampala. Order online and get delivery anywhere in Uganda.', 'buy PS5 Uganda, Xbox Uganda, Nintendo Switch Uganda, gaming consoles Uganda, gaming Kampala, PlayStation Uganda, Mutindo Express'],
                'subs' => [
                    'PlayStation'       => ['Buy PlayStation in Uganda | PS5 & PS4 | Mutindo Express', 'Buy PS5 and PS4 consoles and games in Uganda. Original Sony PlayStation at the best prices in Kampala. Games, controllers and accessories available.', 'buy PS5 Uganda, buy PS4 Uganda, PlayStation Uganda, PS5 price Uganda, PlayStation Kampala, Mutindo Express'],
                    'Xbox'              => ['Buy Xbox in Uganda | Xbox Series X & S | Mutindo Express', 'Buy Xbox Series X, Series S and Xbox accessories in Uganda at competitive prices in Kampala. Delivery across Uganda.', 'buy Xbox Uganda, Xbox Series X Uganda, Xbox Series S Uganda, gaming console Uganda, Mutindo Express'],
                    'Nintendo Switch'   => ['Buy Nintendo Switch in Uganda | Mutindo Express', 'Buy Nintendo Switch, Switch OLED and Switch Lite in Uganda. Best Nintendo prices in Kampala with countrywide delivery.', 'buy Nintendo Switch Uganda, Nintendo Uganda, Switch OLED Uganda, Switch Lite Uganda, Nintendo Kampala, Mutindo Express'],
                    'Gaming Accessories'=> ['Buy Gaming Accessories in Uganda | Controllers, Headsets | Mutindo Express', 'Buy gaming controllers, headsets, charging docks and accessories in Uganda. Compatible with PS5, Xbox, Nintendo Switch at best prices in Kampala.', 'gaming accessories Uganda, game controller Uganda, gaming headset Uganda, PS5 accessories Uganda, Mutindo Express'],
                ],
            ],
            'Accessories' => [
                'seo' => ['Buy Phone & Tech Accessories in Uganda | Mutindo Express', 'Shop phone cases, chargers, power banks, screen protectors and more in Uganda. Accessories for iPhone, Samsung, Tecno and all brands. Best prices in Kampala.', 'phone accessories Uganda, phone cases Uganda, chargers Uganda, power banks Uganda, screen protectors Kampala, tech accessories Uganda, Mutindo Express'],
                'subs' => [
                    'Cases & Covers'    => ['Buy Phone Cases & Covers in Uganda | Mutindo Express', 'Buy phone cases and covers for iPhone, Samsung, Tecno, Infinix and more in Uganda. Slim, tough and wallet cases at best prices in Kampala.', 'phone cases Uganda, iPhone case Uganda, Samsung case Uganda, Tecno case Uganda, phone covers Kampala, Mutindo Express'],
                    'Chargers & Cables' => ['Buy Phone Chargers & Cables in Uganda | Fast Charging | Mutindo Express', 'Buy original USB-C, Lightning and wireless chargers in Uganda. Fast charging adapters and cables for all phones at best prices in Kampala.', 'buy charger Uganda, USB-C charger Uganda, iPhone charger Uganda, fast charger Uganda, phone charger Kampala, Mutindo Express'],
                    'Power Banks'       => ['Buy Power Banks in Uganda | Portable Chargers | Mutindo Express', 'Buy portable power banks in Uganda from Anker, Xiaomi and more. Never run out of battery. Best power bank prices in Kampala with delivery across Uganda.', 'buy power bank Uganda, portable charger Uganda, Anker Uganda, backup battery Uganda, power bank Kampala, Mutindo Express'],
                    'Screen Protectors' => ['Buy Screen Protectors in Uganda | Tempered Glass | Mutindo Express', 'Buy tempered glass screen protectors for smartphones and tablets in Uganda. Compatible with iPhone, Samsung, Tecno and more. Best prices in Kampala.', 'screen protector Uganda, tempered glass Uganda, iPhone screen protector Uganda, Samsung screen protector Uganda, Mutindo Express'],
                ],
            ],
        ];

        $pos = 1;
        foreach ($categories as $parentName => $data) {
            $parentId = $this->makeCategory($parentName, 1, $pos++, $data['seo']);
            $this->command->line("  ✓ $parentName");
            foreach ($data['subs'] as $subName => $subSeo) {
                $this->makeCategory($subName, $parentId, $pos++, $subSeo);
                $this->command->line("      - $subName");
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
