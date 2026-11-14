<?php

declare(strict_types=1);

namespace App\Service;

use App\Catalog\Finish;
use App\Catalog\Product;

/**
 * Validated input of the checkout form. Build it with fromInput(); it is never in an invalid state.
 */
final class CheckoutRequest
{
    public const COUNTRY_CODES = ['973', '966', '971', '965', '974', '968'];

    private function __construct(
        public readonly CustomerDetails $customer,
        public readonly Finish $finish,
        public readonly int $quantity,
    ) {
    }

    /**
     * @param array<string, string> $input raw form fields
     * @throws ValidationException with a message per invalid field
     */
    public static function fromInput(array $input, Product $product): self
    {
        $name       = preg_replace('/\s+/', ' ', trim($input['name'] ?? ''));
        $email      = strtolower(trim($input['email'] ?? ''));
        $mobileCode = preg_replace('/\D/', '', $input['mobile_code'] ?? '');
        $mobile     = preg_replace('/\D/', '', $input['mobile'] ?? '');
        $quantity   = filter_var($input['quantity'] ?? '', FILTER_VALIDATE_INT) ?: 0;
        $finish     = $product->finish($input['finish'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 64) {
            $errors['name'] = 'Enter your full name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 64) {
            $errors['email'] = 'Enter an email address like name@example.com.';
        }
        if (!in_array($mobileCode, self::COUNTRY_CODES, true)) {
            $errors['mobile_code'] = 'Choose your country code.';
        }
        if (!preg_match('/^\d{7,12}$/', $mobile)) {
            $errors['mobile'] = 'Enter a mobile number of 7 to 12 digits.';
        }
        if ($quantity < 1 || $quantity > $product->maxQuantity) {
            $errors['quantity'] = "Choose between 1 and {$product->maxQuantity}.";
        }
        if ($finish === null) {
            $errors['finish'] = 'Choose a finish.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new self(new CustomerDetails($name, $email, $mobileCode, $mobile), $finish, $quantity);
    }
}
