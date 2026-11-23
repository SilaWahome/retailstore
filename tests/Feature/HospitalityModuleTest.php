<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DiningArea;
use App\Models\DiningTable;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\MenuRecipe;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalityModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_plans_are_seeded_with_ugx_tiers(): void
    {
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'retail-basic', 'price_ugx' => 25000]);
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'restaurant-only', 'price_ugx' => 100000]);
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'hotel-starter', 'price_ugx' => 170000, 'room_limit' => 15]);
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'hotel-pro', 'price_ugx' => 250000, 'room_limit' => null]);
    }

    public function test_registration_assigns_business_type_and_plan(): void
    {
        $hotelPro = SubscriptionPlan::where('slug', 'hotel-pro')->firstOrFail();

        $response = $this->post('/register', [
            'shop_name' => 'Serena Test Hotel',
            'business_name' => 'Serena Ltd',
            'business_type' => 'hotel',
            'plan_id' => $hotelPro->id,
            'name' => 'Alice Admin',
            'email' => 'alice@serena.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'currency_code' => 'UGX',
            'currency_symbol' => 'UGX',
        ]);

        $response->assertRedirect('/');
        $tenant = Tenant::where('name', 'Serena Test Hotel')->firstOrFail();
        $this->assertSame('hotel', $tenant->business_type);
        $this->assertSame($hotelPro->id, $tenant->current_plan_id);
        $this->assertTrue($tenant->canAccess('charge_to_room'));
    }

    public function test_role_routing_and_access_isolation(): void
    {
        $hotelPro = SubscriptionPlan::where('slug', 'hotel-pro')->firstOrFail();
        $tenant = Tenant::create([
            'name' => 'Luxe Resort',
            'business_type' => 'hotel',
            'current_plan_id' => $hotelPro->id,
            'subscription_status' => 'active',
        ]);

        $kitchen = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Chef Bob',
            'email' => 'chef@luxe.test',
            'password' => 'password',
            'role' => 'kitchen',
        ]);

        $receptionist = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Grace Frontdesk',
            'email' => 'grace@luxe.test',
            'password' => 'password',
            'role' => 'receptionist',
        ]);

        $this->actingAs($kitchen)->get('/')->assertRedirect(route('restaurant.kds'));
        $this->actingAs($kitchen)->get('/restaurant/kds')->assertOk();
        $this->actingAs($kitchen)->get('/hotel/rooms')->assertForbidden();

        $this->actingAs($receptionist)->get('/')->assertRedirect(route('hotel.rooms'));
        $this->actingAs($receptionist)->get('/hotel/rooms')->assertOk();
        $this->actingAs($receptionist)->get('/restaurant/kds')->assertForbidden();
    }

    public function test_restaurant_fire_order_deducts_bom_and_hotel_charge_to_room_posts_to_folio(): void
    {
        $hotelPro = SubscriptionPlan::where('slug', 'hotel-pro')->firstOrFail();
        $tenant = Tenant::create([
            'name' => 'Grand Hotel',
            'business_type' => 'hotel',
            'current_plan_id' => $hotelPro->id,
            'subscription_status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager',
            'email' => 'mgr@grand.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        // Setup Restaurant
        $area = DiningArea::create(['tenant_id' => $tenant->id, 'name' => 'Main Dining']);
        $table = DiningTable::create(['tenant_id' => $tenant->id, 'area_id' => $area->id, 'table_number' => 'T-01', 'seating_capacity' => 4]);
        $burger = MenuItem::create(['tenant_id' => $tenant->id, 'name' => 'Beef Burger', 'price_ugx' => 25000, 'station' => 'kitchen']);
        $beef = Ingredient::create(['tenant_id' => $tenant->id, 'name' => 'Ground Beef', 'unit' => 'g', 'stock_qty' => 1000]);
        MenuRecipe::create(['tenant_id' => $tenant->id, 'menu_item_id' => $burger->id, 'ingredient_id' => $beef->id, 'quantity_required' => 150]);

        // Fire 2 burgers
        $fireRes = $this->postJson('/restaurant/orders/fire', [
            'table_id' => $table->id,
            'items' => [
                ['menu_item_id' => $burger->id, 'quantity' => 2, 'seat_number' => 1, 'special_notes' => 'No onions'],
            ],
        ]);
        $fireRes->assertOk();
        $orderId = $fireRes->json('order.id');

        $this->assertEquals(700, (float) $beef->fresh()->stock_qty);
        $this->assertEquals('occupied', $table->fresh()->status);
        $this->assertDatabaseHas('kitchen_tickets', ['order_id' => $orderId, 'station' => 'kitchen', 'status' => 'pending']);

        // Setup Hotel Room & Check-In
        $roomType = RoomType::create(['tenant_id' => $tenant->id, 'name' => 'Deluxe', 'base_price_ugx' => 150000, 'capacity' => 2]);
        $room = Room::create(['tenant_id' => $tenant->id, 'room_type_id' => $roomType->id, 'room_number' => '204']);

        $this->post('/hotel/bookings', [
            'room_id' => $room->id,
            'guest_name' => 'John Okello',
            'guest_phone' => '0772000000',
            'check_in_date' => '2026-11-14',
            'check_out_date' => '2026-11-16',
            'check_in_now' => true,
        ])->assertRedirect();

        $booking = Booking::where('room_id', $room->id)->firstOrFail();
        $this->assertEquals(300000, (float) $booking->folio->total_charges_ugx);

        // Post Restaurant Order to Room 204
        $chargeRes = $this->postJson('/hotel/charge-to-room', [
            'order_id' => $orderId,
            'room_id' => $room->id,
        ]);
        $chargeRes->assertOk();
        $this->assertEquals(350000, (float) $booking->folio->fresh()->total_charges_ugx);
        $this->assertEquals('available', $table->fresh()->status);
    }

    public function test_hotel_starter_enforces_15_room_limit(): void
    {
        $starter = SubscriptionPlan::where('slug', 'hotel-starter')->firstOrFail();
        $tenant = Tenant::create([
            'name' => 'Starter Inn',
            'business_type' => 'hotel',
            'current_plan_id' => $starter->id,
            'subscription_status' => 'active',
        ]);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'email' => 'owner@starter.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $rt = RoomType::create(['tenant_id' => $tenant->id, 'name' => 'Standard', 'base_price_ugx' => 80000, 'capacity' => 2]);
        for ($i = 1; $i <= 15; $i++) {
            Room::create(['tenant_id' => $tenant->id, 'room_type_id' => $rt->id, 'room_number' => "10{$i}"]);
        }

        $res = $this->post('/hotel/rooms', [
            'room_type_id' => $rt->id,
            'room_number' => '116',
        ]);
        $res->assertSessionHasErrors('room_limit');
        $this->assertEquals(15, Room::count());
    }
}

