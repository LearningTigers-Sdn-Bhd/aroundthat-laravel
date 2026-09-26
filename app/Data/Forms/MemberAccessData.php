<?php

namespace App\Data\Forms;

use App\Enums\MembershipRole;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A member's new role and, for managers and cashiers, the outlets they work at.
 */
#[MapName(SnakeCaseMapper::class)]
class MemberAccessData extends Data
{
    /**
     * @param  array<int, string>  $outletIds
     */
    public function __construct(
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
