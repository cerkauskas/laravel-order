<?php

namespace App\Services;

use Auth;

class CartService
{
    public function getSingleProductPrice($product){
        $total = $product->price * $product->quantity;
        return $total;
    }
    public function getTotalCartPrice($order)
    {
         $totalCartPrice = 0;
         foreach ($order->orderProducts as $product)
         {
             $totalCartPrice += $product->quantity * $product->price;
         }
         return $totalCartPrice;
    }
    public function getTotalCartQuantity($order)
    {
        $totalCartQuantity = 0;
        foreach ($order->orderProducts as $product)
        {
            $totalCartQuantity += $product->quantity;
        }
        return $totalCartQuantity;
    }
	
	public function getUserOrderTotalPrice() {
		$order = Auth::user()->orders->where('status', 0)->first();
		if (!empty($order)){
			$price = $this->getTotalCartPrice($order);
			return $price;
		}else{
			return 0;
		}
    }
	
	public function getUserOrderTotalQuantity() {
		$order = Auth::user()->orders->where('status', 0)->first();
		if (!empty($order)){
			$quantity = $this->getTotalCartQuantity($order);
			return $quantity;
		}else{
			return 0;
		}
	}
}