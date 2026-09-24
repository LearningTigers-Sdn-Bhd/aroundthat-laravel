<?php

namespace App\Data\Forms;

use App\Enums\MembershipRole;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Who to invite to a business, as what, and for managers and cashiers, at which outlets.
 */
#[MapName(SnakeCaseMapper::class)]
class InviteStaffData extends Data
{
    /**
     * @param  array<int, string>  $outletIds
     */
    public function __construct(
        #[Max(255), Email]
        public string $email,
        public MembershipRole $role,
        public array $outletIds = [],
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'outlet_ids.*' => ['uuid'],
        ];
    }
}
