<?php
// Test the dynamic pricing calculation

function calculateLayoutPaymentAmount($area_sqft) {
    // Base price: ₹8000 for first 2000 sq ft
    $base_price = 8000;
    $base_area = 2000;
    
    // Additional price: ₹1000 for every additional 1000 sq ft
    $additional_price_per_1000_sqft = 1000;
    
    if ($area_sqft <= $base_area) {
        return $base_price;
    } else {
        $additional_area = $area_sqft - $base_area;
        $additional_blocks = ceil($additional_area / 1000);
        $additional_price = $additional_blocks * $additional_price_per_1000_sqft;
        return $base_price + $additional_price;
    }
}

// Test cases
$test_cases = [
    1000,  // 1000 sq ft - should be ₹8000
    2000,  // 2000 sq ft - should be ₹8000
    2500,  // 2500 sq ft - should be ₹9000
    3000,  // 3000 sq ft - should be ₹9000
    3500,  // 3500 sq ft - should be ₹10000
    4000,  // 4000 sq ft - should be ₹10000
    5000,  // 5000 sq ft - should be ₹11000
];

echo "Testing dynamic pricing calculation:\n\n";

foreach ($test_cases as $area) {
    $price = calculateLayoutPaymentAmount($area);
    echo "Area: {$area} sq ft => Price: ₹{$price}\n";
}
?>