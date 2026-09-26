<?php

namespace App\Support\Vouchers;

use Illuminate\Validation\ValidationException;

/**
 * Every voucher of an offer has been issued. Staff see it as a form error; the partner API answers 409.
 */
class VoucherLimitReached extends ValidationException {}
