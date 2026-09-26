<?php

use App\Support\Vouchers\VoucherCode;

test('generated codes use only the unambiguous alphabet', function () {
    foreach (range(1, 50) as $ignored) {
        expect(VoucherCode::isWellFormed(VoucherCode::generate()))->toBeTrue();
    }
});

test('typed and scanned codes are normalized', function (string $input) {
    expect(VoucherCode::normalize($input))->toBe('ABCDE12345');
})->with([
    'plain' => 'ABCDE12345',
    'lower case' => 'abcde12345',
    'displayed with a dash' => 'ABCDE-12345',
    'spaces' => ' abcde 12345 ',
    'scanned QR value' => 'V1:ABCDE12345',
]);

test('codes with the wrong length or ambiguous letters are not well formed', function (string $code) {
    expect(VoucherCode::isWellFormed($code))->toBeFalse();
})->with(['ABCDE1234', 'ABCDE123456', 'ABCDEI2345', 'ABCDEO2345', 'ABCDEU2345']);
