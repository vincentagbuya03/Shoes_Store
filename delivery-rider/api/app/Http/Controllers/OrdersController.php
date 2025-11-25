<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrdersController extends Controller
{
    /**
     * Sample orders data
     * In production, this would come from a database
     */
    private function getSampleOrders()
    {
        return [
            [
                'id' => 'ORD-001',
                'customer_name' => 'Maria Santos',
                'customer_phone' => '+63 912 345 6789',
                'address' => '123 Rizal Street, Makati City, Metro Manila',
                'distance' => '2.5 km',
                'items_count' => 3,
                'payment_method' => 'COD',
                'subtotal' => 2400,
                'delivery_fee' => 50,
                'total' => 2450,
                'status' => 'pending',
                'created_at' => date('c', strtotime('-30 minutes')),
                'items' => [
                    ['id' => 1, 'name' => 'Nike Air Max 90', 'size' => '42', 'quantity' => 1, 'price' => 1200],
                    ['id' => 2, 'name' => 'Adidas Ultraboost', 'size' => '41', 'quantity' => 1, 'price' => 800],
                    ['id' => 3, 'name' => 'Shoe Care Kit', 'size' => 'N/A', 'quantity' => 1, 'price' => 400]
                ]
            ],
            [
                'id' => 'ORD-002',
                'customer_name' => 'Juan Dela Cruz',
                'customer_phone' => '+63 917 654 3210',
                'address' => '456 EDSA, Quezon City, Metro Manila',
                'distance' => '4.2 km',
                'items_count' => 1,
                'payment_method' => 'Paid Online',
                'subtotal' => 3150,
                'delivery_fee' => 50,
                'total' => 3200,
                'status' => 'accepted',
                'created_at' => date('c', strtotime('-1 hour')),
                'items' => [
                    ['id' => 4, 'name' => 'New Balance 574', 'size' => '44', 'quantity' => 1, 'price' => 3150]
                ]
            ],
            [
                'id' => 'ORD-003',
                'customer_name' => 'Ana Garcia',
                'customer_phone' => '+63 918 765 4321',
                'address' => '789 Ayala Avenue, BGC, Taguig City',
                'distance' => '1.8 km',
                'items_count' => 2,
                'payment_method' => 'COD',
                'subtotal' => 1800,
                'delivery_fee' => 50,
                'total' => 1850,
                'status' => 'pending',
                'created_at' => date('c', strtotime('-45 minutes')),
                'items' => [
                    ['id' => 5, 'name' => 'Converse Chuck Taylor', 'size' => '39', 'quantity' => 1, 'price' => 900],
                    ['id' => 6, 'name' => 'Vans Old Skool', 'size' => '39', 'quantity' => 1, 'price' => 900]
                ]
            ],
            [
                'id' => 'ORD-004',
                'customer_name' => 'Pedro Reyes',
                'customer_phone' => '+63 919 876 5432',
                'address' => '321 Ortigas Center, Pasig City',
                'distance' => '3.5 km',
                'items_count' => 4,
                'payment_method' => 'GCash',
                'subtotal' => 5550,
                'delivery_fee' => 50,
                'total' => 5600,
                'status' => 'in_transit',
                'created_at' => date('c', strtotime('-2 hours')),
                'items' => [
                    ['id' => 7, 'name' => 'Puma Suede Classic', 'size' => '43', 'quantity' => 2, 'price' => 2700],
                    ['id' => 8, 'name' => 'Reebok Classic', 'size' => '43', 'quantity' => 2, 'price' => 2850]
                ]
            ]
        ];
    }

    /**
     * Sample history data
     */
    private function getSampleHistory()
    {
        return [
            [
                'id' => 'ORD-150',
                'customer_name' => 'Carlos Mendoza',
                'address' => '45 Aurora Blvd, Quezon City',
                'total' => 3200,
                'delivery_time' => '22 min',
                'rating' => 5,
                'status' => 'completed',
                'completed_at' => date('c', strtotime('-2 hours'))
            ],
            [
                'id' => 'ORD-149',
                'customer_name' => 'Isabella Cruz',
                'address' => '123 Makati Ave, Makati City',
                'total' => 1850,
                'delivery_time' => '35 min',
                'rating' => 4,
                'status' => 'completed',
                'completed_at' => date('c', strtotime('-5 hours'))
            ],
            [
                'id' => 'ORD-148',
                'customer_name' => 'Miguel Santos',
                'address' => '78 Ortigas Center, Pasig',
                'total' => 4500,
                'delivery_time' => '28 min',
                'rating' => 5,
                'status' => 'completed',
                'completed_at' => date('c', strtotime('-1 day'))
            ],
            [
                'id' => 'ORD-147',
                'customer_name' => 'Sofia Garcia',
                'address' => '256 BGC, Taguig City',
                'total' => 2100,
                'delivery_time' => '18 min',
                'rating' => 5,
                'status' => 'completed',
                'completed_at' => date('c', strtotime('-2 days'))
            ],
            [
                'id' => 'ORD-146',
                'customer_name' => 'Antonio Reyes',
                'address' => '89 Alabang, Muntinlupa',
                'total' => 5600,
                'delivery_time' => '42 min',
                'rating' => 4,
                'status' => 'completed',
                'completed_at' => date('c', strtotime('-3 days'))
            ]
        ];
    }

    /**
     * Get active orders
     * 
     * GET /api/orders
     */
    public function index(Request $request)
    {
        $orders = array_filter($this->getSampleOrders(), function($order) {
            return in_array($order['status'], ['pending', 'accepted', 'in_transit']);
        });

        return response()->json([
            'success' => true,
            'orders' => array_values($orders),
            'total' => count($orders)
        ]);
    }

    /**
     * Get order history
     * 
     * GET /api/orders/history
     */
    public function history(Request $request)
    {
        return response()->json([
            'success' => true,
            'orders' => $this->getSampleHistory(),
            'total' => count($this->getSampleHistory())
        ]);
    }

    /**
     * Get single order details
     * 
     * GET /api/orders/{id}
     */
    public function show($id)
    {
        $allOrders = array_merge($this->getSampleOrders(), $this->getSampleHistory());
        
        foreach ($allOrders as $order) {
            if ($order['id'] === $id) {
                return response()->json([
                    'success' => true,
                    'order' => $order
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Order not found'
        ], 404);
    }

    /**
     * Accept an order
     * 
     * POST /api/orders/{id}/accept
     */
    public function accept($id)
    {
        // In production, update database
        return response()->json([
            'success' => true,
            'message' => 'Order accepted successfully',
            'order' => [
                'id' => $id,
                'status' => 'accepted',
                'accepted_at' => date('c')
            ]
        ]);
    }

    /**
     * Complete an order
     * 
     * POST /api/orders/{id}/complete
     */
    public function complete($id)
    {
        // In production, update database
        return response()->json([
            'success' => true,
            'message' => 'Order marked as delivered',
            'order' => [
                'id' => $id,
                'status' => 'delivered',
                'completed_at' => date('c')
            ]
        ]);
    }
}
