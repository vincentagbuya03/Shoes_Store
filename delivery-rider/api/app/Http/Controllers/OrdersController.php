<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Orders Controller
 * 
 * Handles all order-related operations for delivery riders.
 * Returns sample JSON responses for development and testing.
 */
class OrdersController extends Controller
{
    /**
     * Sample orders data
     * In production, this would come from the database
     */
    private function getSampleOrders()
    {
        return [
            [
                'id' => 1001,
                'customer_name' => 'Maria Santos',
                'customer_phone' => '+63 912 345 6789',
                'address' => '123 Rizal Street, Makati City, Metro Manila',
                'distance' => '2.3 km',
                'items_count' => 2,
                'payment_method' => 'Cash on Delivery',
                'status' => 'pending',
                'delivery_fee' => 50,
                'subtotal' => 2500,
                'total' => 2550,
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
                'items' => [
                    ['id' => 1, 'name' => 'Nike Air Max 90', 'quantity' => 1, 'price' => 1500],
                    ['id' => 2, 'name' => 'Adidas Ultraboost', 'quantity' => 1, 'price' => 1000]
                ]
            ],
            [
                'id' => 1002,
                'customer_name' => 'Juan Dela Cruz',
                'customer_phone' => '+63 917 234 5678',
                'address' => '456 Bonifacio Avenue, BGC, Taguig City',
                'distance' => '4.1 km',
                'items_count' => 1,
                'payment_method' => 'GCash',
                'status' => 'pending',
                'delivery_fee' => 75,
                'subtotal' => 3200,
                'total' => 3275,
                'created_at' => date('Y-m-d H:i:s', strtotime('-45 minutes')),
                'items' => [
                    ['id' => 3, 'name' => 'Puma RS-X', 'quantity' => 1, 'price' => 3200]
                ]
            ],
            [
                'id' => 1003,
                'customer_name' => 'Ana Garcia',
                'customer_phone' => '+63 918 765 4321',
                'address' => '789 Ayala Avenue, Makati City',
                'distance' => '1.8 km',
                'items_count' => 3,
                'payment_method' => 'Credit Card',
                'status' => 'accepted',
                'delivery_fee' => 45,
                'subtotal' => 4500,
                'total' => 4545,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'items' => [
                    ['id' => 4, 'name' => 'New Balance 574', 'quantity' => 1, 'price' => 1800],
                    ['id' => 5, 'name' => 'Asics Gel Kayano', 'quantity' => 1, 'price' => 1500],
                    ['id' => 6, 'name' => 'Sports Socks Pack', 'quantity' => 1, 'price' => 1200]
                ]
            ],
            [
                'id' => 1004,
                'customer_name' => 'Pedro Reyes',
                'customer_phone' => '+63 919 876 5432',
                'address' => '321 EDSA, Mandaluyong City',
                'distance' => '3.5 km',
                'items_count' => 2,
                'payment_method' => 'PayMaya',
                'status' => 'in_progress',
                'delivery_fee' => 60,
                'subtotal' => 2800,
                'total' => 2860,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'items' => [
                    ['id' => 7, 'name' => 'Nike Dunk Low', 'quantity' => 1, 'price' => 1600],
                    ['id' => 8, 'name' => 'Jordan 1 Low', 'quantity' => 1, 'price' => 1200]
                ]
            ]
        ];
    }
    
    /**
     * Get active orders
     * 
     * GET /api/orders
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $orders = array_filter($this->getSampleOrders(), function($order) {
            return in_array($order['status'], ['pending', 'accepted', 'in_progress']);
        });
        
        return response()->json([
            'success' => true,
            'orders' => array_values($orders),
            'stats' => [
                'pending' => count(array_filter($orders, fn($o) => $o['status'] === 'pending')),
                'in_progress' => count(array_filter($orders, fn($o) => in_array($o['status'], ['accepted', 'in_progress']))),
                'completed' => 12, // Sample completed today
                'earnings' => 600 // Sample earnings today
            ]
        ]);
    }
    
    /**
     * Get order history
     * 
     * GET /api/orders/history
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        $historyOrders = [
            [
                'id' => 995,
                'customer_name' => 'Carlos Mendoza',
                'address' => '888 Shaw Boulevard, Pasig City',
                'distance' => '2.1 km',
                'items_count' => 2,
                'payment_method' => 'Cash on Delivery',
                'status' => 'completed',
                'delivery_fee' => 50,
                'total' => 50,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'completed_at' => date('Y-m-d H:i:s', strtotime('-1 day +45 minutes'))
            ],
            [
                'id' => 994,
                'customer_name' => 'Lisa Fernandez',
                'address' => '555 Ortigas Avenue, Pasig City',
                'distance' => '3.2 km',
                'items_count' => 1,
                'payment_method' => 'GCash',
                'status' => 'completed',
                'delivery_fee' => 65,
                'total' => 65,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day -2 hours')),
                'completed_at' => date('Y-m-d H:i:s', strtotime('-1 day -1 hour'))
            ],
            [
                'id' => 993,
                'customer_name' => 'Roberto Lim',
                'address' => '111 Quezon Avenue, Quezon City',
                'distance' => '5.8 km',
                'items_count' => 4,
                'payment_method' => 'Credit Card',
                'status' => 'completed',
                'delivery_fee' => 95,
                'total' => 95,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'completed_at' => date('Y-m-d H:i:s', strtotime('-2 days +1 hour'))
            ],
            [
                'id' => 992,
                'customer_name' => 'Elena Cruz',
                'address' => '777 Roxas Boulevard, Manila',
                'distance' => '4.5 km',
                'items_count' => 2,
                'payment_method' => 'PayMaya',
                'status' => 'cancelled',
                'delivery_fee' => 0,
                'total' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'completed_at' => null
            ],
            [
                'id' => 991,
                'customer_name' => 'Miguel Torres',
                'address' => '222 Taft Avenue, Pasay City',
                'distance' => '2.7 km',
                'items_count' => 3,
                'payment_method' => 'Cash on Delivery',
                'status' => 'completed',
                'delivery_fee' => 55,
                'total' => 55,
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days -3 hours')),
                'completed_at' => date('Y-m-d H:i:s', strtotime('-3 days -2 hours'))
            ]
        ];
        
        return response()->json([
            'success' => true,
            'orders' => $historyOrders,
            'stats' => [
                'completed' => 156,
                'cancelled' => 8,
                'totalEarnings' => 45680,
                'avgDistance' => '2.8 km'
            ]
        ]);
    }
    
    /**
     * Get single order details
     * 
     * GET /api/orders/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $orders = $this->getSampleOrders();
        
        foreach ($orders as $order) {
            if ($order['id'] == $id) {
                return response()->json([
                    'success' => true,
                    'order' => $order
                ]);
            }
        }
        
        // Return a sample order for any ID not found
        return response()->json([
            'success' => true,
            'order' => [
                'id' => $id,
                'customer_name' => 'Sample Customer',
                'customer_phone' => '+63 912 345 6789',
                'address' => '123 Sample Street, Metro Manila',
                'distance' => '2.5 km',
                'items_count' => 2,
                'payment_method' => 'Cash on Delivery',
                'status' => 'pending',
                'delivery_fee' => 50,
                'subtotal' => 2000,
                'total' => 2050,
                'created_at' => date('Y-m-d H:i:s'),
                'items' => [
                    ['id' => 1, 'name' => 'Sample Shoe', 'quantity' => 1, 'price' => 1200],
                    ['id' => 2, 'name' => 'Sample Accessory', 'quantity' => 1, 'price' => 800]
                ]
            ]
        ]);
    }
    
    /**
     * Accept an order
     * 
     * POST /api/orders/{id}/accept
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function accept($id)
    {
        // In production, update the order status in database
        return response()->json([
            'success' => true,
            'message' => "Order #{$id} accepted successfully",
            'order' => [
                'id' => $id,
                'status' => 'accepted'
            ]
        ]);
    }
    
    /**
     * Complete an order
     * 
     * POST /api/orders/{id}/complete
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete($id)
    {
        // In production, update the order status in database
        return response()->json([
            'success' => true,
            'message' => "Order #{$id} completed successfully",
            'order' => [
                'id' => $id,
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }
}
