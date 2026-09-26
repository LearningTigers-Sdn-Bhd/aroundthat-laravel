<?php

namespace App\Support\Vouchers;

/**
 * What one bill costs after a voucher, as decimal strings with two places.
 */
final readonly class Discount
{
    public function __construct(
        public string $billAmount,
        public string $discountAmount,
        public string $netAmount,
        public bool $capped,
    ) {}

    /**
     * @return array{bill_amount: string, discount_amount: string, net_amount: string, capped: bool}
     */
    public function toArray(): array
    {
        return [
            'bill_amount' => $this->billAmount,
            'discount_amount' => $this->discountAmount,
            'net_amount' => $this->netAmount,
            'capped' => $this->capped,
        ];
    }
}
