<?php

use App\Support\LineItemMath;

test('line item subtotal subtracts discount from quantity times unit amount', function () {
    expect(LineItemMath::subtotal(3, 15, 5))->toBe('40.00');
    expect(LineItemMath::subtotal(20, 5, 10))->toBe('90.00');
    expect(LineItemMath::subtotal(2, 10, 0))->toBe('20.00');
});
