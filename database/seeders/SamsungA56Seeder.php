<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds Samsung Galaxy A56 128GB as a configurable product with 3 color variants.
 * Images are read from /home/marvin/sumsung A56 / on the host (already volume-mounted
 * or copied before this seeder runs — the seeder copies them into
 * storage/app/public/product/{id}/ via Laravel's Storage facade).
 *
 * Run:  php artisan db:seed --class=SamsungA56Seeder --force
 */
class SamsungA56Seeder extends Seeder
{
    // ─── tuneable constants ────────────────────────────────────────────────────
    private const PRICE       = 1350000.00;
    private const CHANNEL     = 'default';
    private const LOCALE      = 'en';
    private const INV_SRC_ID  = 1;      // default inventory source
    private const CHANNEL_ID  = 1;
    private const CATEGORY_ID = 3;      // Smartphones
    private const ATTR_FAM_ID = 1;      // Default attribute family
    private const COLOR_ATTR  = 23;     // attribute id for color
    private const IMAGE_DIR   = '/tmp/a56';

    // Images assigned to each color by dominant-colour analysis
    private const COLOR_IMAGES = [
        'Awesome Graphite' => [
            'Samsung Galaxy A56 128GB (Awesome Graphite).png',
            'Samsung Galaxy A56 128GB (2).png',
        ],
        'Awesome Lightgray' => [
            'Samsung Galaxy A56 128GB (Awesome Lightgray).png',
            'Samsung Galaxy A56 128GB.png',
            'Samsung Galaxy A56 128GB (1).png',
            'Samsung Galaxy A56 128GB (3).png',
            'Samsung Galaxy A56 128GB (4).png',
            'Samsung Galaxy A56 128GB (5).png',
            'Samsung Galaxy A56 128GB (7).png',
            'Samsung Galaxy A56 128GB (9).png',
        ],
        'Awesome Olive' => [
            'Samsung Galaxy A56 128GB (Awesome Olive).png',
            'Samsung Galaxy A56 128GB (6).png',
            'Samsung Galaxy A56 128GB (8).png',
        ],
    ];

    // Samsung A56 color swatches (hex)
    private const COLOR_HEX = [
        'Awesome Graphite'  => '#474E5B',
        'Awesome Lightgray' => '#C0BDBD',
        'Awesome Olive'     => '#545A43',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            // 1. Seed color options ─────────────────────────────────────────────
            $colorOptionIds = $this->seedColorOptions();

            // 2. Parent configurable product ────────────────────────────────────
            $parentSku = 'samsung-galaxy-a56-128gb';
            if (DB::table('products')->where('sku', $parentSku)->exists()) {
                $this->command?->info('Samsung A56 already seeded. Skipping.');
                return;
            }

            $parentId = DB::table('products')->insertGetId([
                'sku'                => $parentSku,
                'type'               => 'configurable',
                'attribute_family_id'=> self::ATTR_FAM_ID,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // super-attribute: configurable on color
            DB::table('product_super_attributes')->insert([
                'product_id'   => $parentId,
                'attribute_id' => self::COLOR_ATTR,
            ]);

            // parent attribute values
            $this->insertAttrValues($parentId, [
                'sku'                  => $parentSku,
                'name'                 => 'Samsung Galaxy A56 128GB 5G',
                'url_key'              => 'samsung-galaxy-a56-128gb-5g',
                'new'                  => 1,
                'featured'             => 1,
                'visible_individually' => 1,
                'status'               => 1,
                'short_description'    => $this->shortDesc(),
                'description'          => $this->longDesc(),
                'price'                => self::PRICE,
                'weight'               => 0.198,
                'meta_title'           => 'Samsung Galaxy A56 128GB Price in Uganda | Buy Online – Mutindo Express',
                'meta_keywords'        => 'Samsung Galaxy A56 price Uganda, buy Samsung A56 Kampala, Samsung A56 5G Uganda, Samsung phone 128GB Uganda, Mutindo Express Samsung, best Android phone Uganda 2025',
                'meta_description'     => 'Buy the Samsung Galaxy A56 128GB 5G in Uganda at UGX 1,350,000. Available in Awesome Graphite, Lightgray & Olive. Fast countrywide delivery from Mutindo Express Kampala.',
                'product_number'       => 'SM-A566B',
            ]);

            // parent → category + channel
            DB::table('product_categories')->insert(['product_id'=>$parentId,'category_id'=>self::CATEGORY_ID]);
            DB::table('product_channels')->insert(['product_id'=>$parentId,'channel_id'=>self::CHANNEL_ID]);

            // parent product_flat (configurable products need this for listing)
            $this->insertFlat($parentId, null, 'samsung-galaxy-a56-128gb', 'Samsung Galaxy A56 128GB 5G');

            // price index for parent
            DB::table('product_price_indices')->insert([
                'product_id'        => $parentId,
                'customer_group_id' => null,
                'channel_id'        => self::CHANNEL_ID,
                'min_price'         => self::PRICE,
                'regular_min_price' => self::PRICE,
                'max_price'         => self::PRICE,
                'regular_max_price' => self::PRICE,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            // 3. Simple variants (one per color) ──────────────────────────────
            $pos = 0;
            foreach (self::COLOR_IMAGES as $colorName => $images) {
                $colorOptionId = $colorOptionIds[$colorName];
                $slug          = Str::slug($colorName);
                $childSku      = "samsung-galaxy-a56-128gb-{$slug}";

                $childId = DB::table('products')->insertGetId([
                    'sku'                => $childSku,
                    'type'               => 'simple',
                    'parent_id'          => $parentId,
                    'attribute_family_id'=> self::ATTR_FAM_ID,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                $childName = "Samsung Galaxy A56 128GB 5G – {$colorName}";
                $this->insertAttrValues($childId, [
                    'sku'                  => $childSku,
                    'name'                 => $childName,
                    'url_key'              => "samsung-galaxy-a56-128gb-5g-{$slug}",
                    'new'                  => 1,
                    'featured'             => 1,
                    'visible_individually' => 0,   // variants hidden individually
                    'status'               => 1,
                    'short_description'    => $this->shortDesc($colorName),
                    'description'          => $this->longDesc($colorName),
                    'price'                => self::PRICE,
                    'weight'               => 0.198,
                    'color'                => $colorOptionId,
                    'meta_title'           => "Samsung Galaxy A56 128GB {$colorName} Price Uganda | Mutindo Express",
                    'meta_keywords'        => "Samsung Galaxy A56 {$colorName} Uganda, buy Samsung A56 {$colorName} Kampala, Samsung A56 5G {$colorName}",
                    'meta_description'     => "Buy Samsung Galaxy A56 128GB 5G in {$colorName} at UGX 1,350,000. Fast delivery in Kampala & Uganda. Shop at Mutindo Express.",
                    'product_number'       => 'SM-A566B',
                ]);

                // copy + attach images
                $this->attachImages($childId, $images, $pos === 0);

                // inventory
                DB::table('product_inventories')->insert([
                    'product_id'          => $childId,
                    'qty'                 => 50,
                    'vendor_id'           => 0,
                    'inventory_source_id' => self::INV_SRC_ID,
                ]);

                // price index
                DB::table('product_price_indices')->insert([
                    'product_id'        => $childId,
                    'customer_group_id' => null,
                    'channel_id'        => self::CHANNEL_ID,
                    'min_price'         => self::PRICE,
                    'regular_min_price' => self::PRICE,
                    'max_price'         => self::PRICE,
                    'regular_max_price' => self::PRICE,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                // product_flat for child variant
                $this->insertFlat($childId, $parentId, $childSku, $childName);

                $pos++;
            }

            // inventory index for parent (sum of children)
            DB::table('product_inventory_indices')->insert([
                'product_id' => $parentId,
                'qty'        => 150,
                'channel_id' => self::CHANNEL_ID,
            ]);

            $this->command?->info("Samsung Galaxy A56 seeded. Parent ID: {$parentId}");
        });
    }

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function seedColorOptions(): array
    {
        $ids = [];
        foreach (self::COLOR_HEX as $label => $hex) {
            $existing = DB::table('attribute_option_translations')
                ->where('label', $label)
                ->join('attribute_options', 'attribute_options.id', '=', 'attribute_option_translations.attribute_option_id')
                ->where('attribute_options.attribute_id', self::COLOR_ATTR)
                ->value('attribute_option_translations.attribute_option_id');

            if ($existing) {
                // update hex swatch if missing
                DB::table('attribute_options')->where('id', $existing)->update(['swatch_value' => $hex]);
                $ids[$label] = $existing;
            } else {
                $optId = DB::table('attribute_options')->insertGetId([
                    'attribute_id'  => self::COLOR_ATTR,
                    'swatch_value'  => $hex,
                    'sort_order'    => 0,
                ]);
                DB::table('attribute_option_translations')->insert([
                    'attribute_option_id' => $optId,
                    'locale'              => self::LOCALE,
                    'label'               => $label,
                ]);
                $ids[$label] = $optId;
                $this->command?->info("Added color: {$label} ({$hex})");
            }
        }
        return $ids;
    }

    /** Insert product_attribute_values for a product */
    private function insertAttrValues(int $productId, array $data): void
    {
        // attribute meta: id → [type, value_per_channel, value_per_locale, column]
        $attrMeta = [
            'sku'                  => [1,  'text',    0, 0, 'text_value'],
            'name'                 => [2,  'text',    0, 1, 'text_value'],
            'url_key'              => [3,  'text',    0, 1, 'text_value'],
            'new'                  => [5,  'boolean', 0, 0, 'boolean_value'],
            'featured'             => [6,  'boolean', 0, 0, 'boolean_value'],
            'visible_individually' => [7,  'boolean', 0, 0, 'boolean_value'],
            'status'               => [8,  'boolean', 1, 0, 'boolean_value'],
            'short_description'    => [9,  'textarea',0, 1, 'text_value'],
            'description'          => [10, 'textarea',0, 1, 'text_value'],
            'price'                => [11, 'price',   0, 0, 'float_value'],
            'meta_title'           => [16, 'textarea',0, 1, 'text_value'],
            'meta_keywords'        => [17, 'textarea',0, 1, 'text_value'],
            'meta_description'     => [18, 'textarea',0, 1, 'text_value'],
            'weight'               => [22, 'text',    0, 0, 'text_value'],
            'color'                => [23, 'select',  0, 0, 'integer_value'],
            'product_number'       => [27, 'text',    0, 0, 'text_value'],
        ];

        $rows = [];
        foreach ($data as $code => $value) {
            if (! isset($attrMeta[$code])) continue;
            [$attrId, $type, $perChannel, $perLocale, $col] = $attrMeta[$code];

            $ch  = $perChannel ? self::CHANNEL : null;
            $loc = $perLocale  ? self::LOCALE  : null;

            $uniqueParts = array_filter([$ch, $loc, $productId, $attrId]);
            $uniqueId    = implode('|', $uniqueParts);

            // cast value to correct column
            $row = [
                'product_id'   => $productId,
                'attribute_id' => $attrId,
                'channel'      => $ch,
                'locale'       => $loc,
                'unique_id'    => $uniqueId,
                'text_value'   => null,
                'boolean_value'=> null,
                'integer_value'=> null,
                'float_value'  => null,
                'datetime_value'=>null,
                'date_value'   => null,
                'json_value'   => null,
            ];
            $row[$col] = in_array($type, ['boolean'])
                ? (int) $value
                : (in_array($type, ['price']) ? (float)$value : $value);

            $rows[] = $row;

            // For locale-based attributes, also insert with null locale for channel scope
            // (Bagisto sometimes reads without locale context)
        }

        DB::table('product_attribute_values')->insert($rows);
    }

    /** Copy images from host folder into Bagisto storage and create product_images rows */
    private function attachImages(int $productId, array $filenames, bool $isDefault): void
    {
        $storageDir = "product/{$productId}";
        Storage::makeDirectory($storageDir);

        $position = 1;
        foreach ($filenames as $filename) {
            $srcPath = self::IMAGE_DIR . '/' . $filename;
            if (! file_exists($srcPath)) {
                $this->command?->warn("Image not found: {$srcPath}");
                continue;
            }
            $destName = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
            $storagePath = "{$storageDir}/{$destName}";

            Storage::put($storagePath, file_get_contents($srcPath));

            DB::table('product_images')->insert([
                'type'       => 'images',
                'path'       => $storagePath,
                'product_id' => $productId,
                'position'   => $position++,
            ]);
        }
    }

    /** Insert product_flat row for listing/search */
    private function insertFlat(int $productId, ?int $parentId, string $sku, string $name): void
    {
        DB::table('product_flat')->insert([
            'sku'                  => $sku,
            'type'                 => $parentId ? 'simple' : 'configurable',
            'product_number'       => 'SM-A566B',
            'name'                 => $name,
            'short_description'    => $this->shortDesc(),
            'description'          => $this->longDesc(),
            'url_key'              => $sku,
            'new'                  => 1,
            'featured'             => 1,
            'status'               => 1,
            'visible_individually' => $parentId ? 0 : 1,
            'meta_title'           => 'Samsung Galaxy A56 128GB Price in Uganda | Mutindo Express',
            'meta_keywords'        => 'Samsung Galaxy A56 price Uganda, buy Samsung A56 Kampala, Samsung A56 5G Uganda',
            'meta_description'     => 'Buy the Samsung Galaxy A56 128GB 5G in Uganda at UGX 1,350,000. Available in Graphite, Lightgray & Olive. Countrywide delivery from Mutindo Express.',
            'price'                => self::PRICE,
            'weight'               => 0.198,
            'created_at'           => now(),
            'locale'               => self::LOCALE,
            'channel'              => self::CHANNEL,
            'attribute_family_id'  => self::ATTR_FAM_ID,
            'product_id'           => $productId,
            'updated_at'           => now(),
            'parent_id'            => $parentId,
        ]);
    }

    private function shortDesc(?string $color = null): string
    {
        $colorPart = $color ? " in {$color}" : '';
        return "Samsung Galaxy A56 128GB 5G{$colorPart} — 6.7\" Super AMOLED, 50MP quad camera, 5000mAh battery, Snapdragon 7s Gen 3. Available in Uganda at UGX 1,350,000 with countrywide delivery from Mutindo Express Kampala.";
    }

    private function longDesc(?string $color = null): string
    {
        $colorPart = $color ? "in {$color} " : '';
        return <<<HTML
<h2>Samsung Galaxy A56 128GB 5G {$colorPart}– Buy in Uganda | Mutindo Express</h2>
<p>Get the brand-new <strong>Samsung Galaxy A56 128GB 5G</strong> {$colorPart}at the best price in Uganda — <strong>UGX 1,350,000</strong> with fast countrywide delivery from <strong>Mutindo Express, Kampala</strong>.</p>

<h3>Key Features</h3>
<ul>
  <li><strong>Display:</strong> 6.7" Full HD+ Super AMOLED, 120Hz refresh rate</li>
  <li><strong>Processor:</strong> Snapdragon 7s Gen 3 Octa-core</li>
  <li><strong>RAM &amp; Storage:</strong> 8GB RAM / 128GB internal storage (expandable)</li>
  <li><strong>Camera:</strong> 50MP + 12MP + 5MP triple rear camera | 12MP selfie</li>
  <li><strong>Battery:</strong> 5000mAh with 45W fast charging</li>
  <li><strong>OS:</strong> Android 15 with One UI 7</li>
  <li><strong>Connectivity:</strong> 5G, Wi-Fi 6, Bluetooth 5.3, NFC, USB-C</li>
  <li><strong>Security:</strong> In-display fingerprint sensor + Face recognition</li>
  <li><strong>Water Resistance:</strong> IP67 dust and water resistant</li>
</ul>

<h3>Why Buy from Mutindo Express?</h3>
<ul>
  <li>✅ 100% genuine Samsung products</li>
  <li>✅ Countrywide delivery across Uganda</li>
  <li>✅ Pay via MTN MoMo, Airtel Money, or cash on delivery (Kampala)</li>
  <li>✅ 7-day return policy</li>
  <li>✅ Official Samsung warranty</li>
</ul>

<h3>In the Box</h3>
<p>Samsung Galaxy A56 handset, USB-C cable, SIM ejector pin, documentation. (Charger sold separately.)</p>

<p><em>Order now and receive your Samsung Galaxy A56 anywhere in Uganda — Kampala, Entebbe, Jinja, Mbarara, Gulu, Mbale and more.</em></p>
HTML;
    }
}
