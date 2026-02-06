<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 menu categories
        $categories = [
            [
                'name' => 'Appetizers',
                'description' => 'Starters and light bites to begin your meal',
                'display_order' => 1,
                'status' => 'active',
            ],
            [
                'name' => 'Main Courses',
                'description' => 'Hearty main dishes and entrees',
                'display_order' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Desserts',
                'description' => 'Sweet treats to end your meal',
                'display_order' => 3,
                'status' => 'active',
            ],
            [
                'name' => 'Beverages',
                'description' => 'Refreshing drinks and cocktails',
                'display_order' => 4,
                'status' => 'active',
            ],
            [
                'name' => 'Specials',
                'description' => 'Chef specials and seasonal items',
                'display_order' => 5,
                'status' => 'active',
            ],
        ];

        $createdCategories = [];
        foreach ($categories as $category) {
            $createdCategories[$category['name']] = MenuCategory::create($category);
        }

        // Create 30+ menu items across 5 categories
        $menuItems = [
            // Appetizers (8 items)
            [
                'category_id' => $createdCategories['Appetizers']->id,
                'name' => 'Chicken Wings',
                'description' => 'Crispy chicken wings with BBQ sauce',
                'price' => 15000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 15,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Appetizers']->id,
                'name' => 'Spring Rolls',
                'description' => 'Vegetable spring rolls with sweet chili sauce',
                'price' => 12000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 10,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Appetizers']->id,
                'name' => 'Calamari',
                'description' => 'Fried calamari with tartar sauce',
                'price' => 18000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 12,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Appetizers']->id,
                'name' => 'Samosas',
                'description' => 'Crispy beef samosas',
                'price' => 8000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 8,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Appetizers']->id,
                'name' => 'Bruschetta',
                'description' => 'Tomato and basil bruschetta',
                'price' => 10000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 8,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Appetizers']->id,
                'name' => 'Garlic Bread',
                'description' => 'Toasted garlic bread with herbs',
                'price' => 7000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 8,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Appetizers']->id,
                'name' => 'Nachos Supreme',
                'description' => 'Loaded nachos with cheese, salsa, and guacamole',
                'price' => 14000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 10,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Appetizers']->id,
                'name' => 'Mozzarella Sticks',
                'description' => 'Deep-fried mozzarella sticks with marinara sauce',
                'price' => 13000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 10,
                'status' => 'available',
            ],

            // Main Courses (10 items)
            [
                'category_id' => $createdCategories['Main Courses']->id,
                'name' => 'Grilled Tilapia',
                'description' => 'Whole grilled tilapia with chips and salad',
                'price' => 25000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 20,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Main Courses']->id,
                'name' => 'Beef Steak',
                'description' => '250g beef steak with mashed potatoes and vegetables',
                'price' => 35000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 25,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Main Courses']->id,
                'name' => 'Chicken Biriyani',
                'description' => 'Spiced chicken biriyani with raita',
                'price' => 18000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 15,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Main Courses']->id,
                'name' => 'Fish & Chips',
                'description' => 'Battered fish with french fries',
                'price' => 22000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 18,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Main Courses']->id,
                'name' => 'Vegetable Pasta',
                'description' => 'Creamy vegetable pasta',
                'price' => 16000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 15,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Main Courses']->id,
                'name' => 'Grilled Chicken',
                'description' => 'Grilled chicken breast with rice and vegetables',
                'price' => 20000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 20,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Main Courses']->id,
                'name' => 'Pilau Rice with Beef',
                'description' => 'Spiced pilau rice with tender beef',
                'price' => 18000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 18,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Main Courses']->id,
                'name' => 'Seafood Platter',
                'description' => 'Mixed seafood with garlic butter',
                'price' => 45000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 30,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Main Courses']->id,
                'name' => 'Lamb Chops',
                'description' => 'Grilled lamb chops with rosemary and mint sauce',
                'price' => 40000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 25,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Main Courses']->id,
                'name' => 'Butter Chicken',
                'description' => 'Creamy butter chicken with naan bread',
                'price' => 22000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 20,
                'status' => 'available',
            ],

            // Desserts (6 items)
            [
                'category_id' => $createdCategories['Desserts']->id,
                'name' => 'Chocolate Cake',
                'description' => 'Rich chocolate cake with vanilla ice cream',
                'price' => 10000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 5,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Desserts']->id,
                'name' => 'Tiramisu',
                'description' => 'Classic Italian tiramisu',
                'price' => 12000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 5,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Desserts']->id,
                'name' => 'Ice Cream Sundae',
                'description' => 'Three scoops with toppings',
                'price' => 8000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 5,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Desserts']->id,
                'name' => 'Fruit Salad',
                'description' => 'Fresh tropical fruit salad',
                'price' => 7000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 5,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Desserts']->id,
                'name' => 'Cheesecake',
                'description' => 'New York style cheesecake with berry compote',
                'price' => 11000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 5,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Desserts']->id,
                'name' => 'Crème Brûlée',
                'description' => 'Classic French vanilla custard with caramelized sugar',
                'price' => 13000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 8,
                'status' => 'available',
            ],

            // Beverages (12 items)
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Mojito',
                'description' => 'Classic mint mojito',
                'price' => 12000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 5,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Piña Colada',
                'description' => 'Tropical piña colada',
                'price' => 15000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 5,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Fresh Orange Juice',
                'description' => 'Freshly squeezed orange juice',
                'price' => 6000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 3,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Passion Juice',
                'description' => 'Fresh passion fruit juice',
                'price' => 6000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 3,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Mango Smoothie',
                'description' => 'Creamy mango smoothie',
                'price' => 8000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 5,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Coffee',
                'description' => 'Espresso or Americano',
                'price' => 5000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 3,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Cappuccino',
                'description' => 'Classic cappuccino',
                'price' => 6000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 4,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Coca Cola',
                'description' => 'Chilled Coca Cola',
                'price' => 3000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 1,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Bottled Water',
                'description' => 'Still or sparkling water',
                'price' => 2000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 1,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Red Wine (Glass)',
                'description' => 'House red wine',
                'price' => 10000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 2,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'White Wine (Glass)',
                'description' => 'House white wine',
                'price' => 10000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 2,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Beverages']->id,
                'name' => 'Local Beer',
                'description' => 'Kilimanjaro or Safari',
                'price' => 5000,
                'prep_area' => 'bar',
                'prep_time_minutes' => 1,
                'status' => 'available',
            ],

            // Specials (6 items)
            [
                'category_id' => $createdCategories['Specials']->id,
                'name' => 'Sunday Roast',
                'description' => 'Traditional Sunday roast with all the trimmings',
                'price' => 38000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 35,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Specials']->id,
                'name' => 'Lobster Thermidor',
                'description' => 'Creamy lobster thermidor with seasonal vegetables',
                'price' => 65000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 40,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Specials']->id,
                'name' => 'Sushi Platter',
                'description' => 'Assorted sushi and sashimi (Chef selection)',
                'price' => 55000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 30,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Specials']->id,
                'name' => 'Vegan Buddha Bowl',
                'description' => 'Quinoa, roasted vegetables, and tahini dressing',
                'price' => 19000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 15,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Specials']->id,
                'name' => 'Seafood Paella',
                'description' => 'Traditional Spanish seafood paella (serves 2)',
                'price' => 50000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 45,
                'status' => 'available',
            ],
            [
                'category_id' => $createdCategories['Specials']->id,
                'name' => 'Tasting Menu',
                'description' => '5-course chef tasting menu with wine pairing',
                'price' => 85000,
                'prep_area' => 'kitchen',
                'prep_time_minutes' => 90,
                'status' => 'available',
            ],
        ];

        foreach ($menuItems as $item) {
            MenuItem::create($item);
        }

        $this->command->info('✓ Menu categories and items seeded successfully!');
        $this->command->info('  - 5 categories created');
        $this->command->info('  - 42 menu items created across all categories');
    }
}
