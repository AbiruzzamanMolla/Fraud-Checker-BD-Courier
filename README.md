# Courier Fraud Checker BD for Laravel

A Laravel package to detect potential fraudulent orders by checking customer delivery behavior through Pathao and Steadfast courier services in Bangladesh.

---

## ✨ Features

- Check customer delivery history across multiple couriers
- Validate Bangladeshi phone numbers
- Get success/cancel/total delivery statistics
- Supports both Pathao and Steadfast courier services

---

## ⚙️ Installation

### Install via Composer:

```bash
composer require azmolla/fraud-checker-bd-courier
```

### Add Service Provider (Laravel 5.4 and below)

In `config/app.php`:

```php
'providers' => [
    Azmolla\FraudCheckerBdCourier\FraudCheckerBdCourierServiceProvider::class,
],
```

### Add Facade Alias (optional)

In `config/app.php`:

```php
'aliases' => [
    'FraudCheckerBdCourier' => Azmolla\FraudCheckerBdCourier\Facade\FraudCheckerBdCourier::class,
],
```

### Publish Configuration

Publish the config file with:

```bash
php artisan vendor:publish --tag="config"
```

---

## 🔧 Configuration

Add these environment variables to your `.env` file:

```env
# Pathao Credentials
PATHAO_USER=your_pathao_email
PATHAO_PASSWORD=your_pathao_password

# Steadfast Credentials
STEADFAST_USER=your_steadfast_email
STEADFAST_PASSWORD=your_steadfast_password

# Redx Credentials
REDX_PHONE=your_redx_login_phone_number # e.g. 01712345678 (no +880)
REDX_PASSWORD=your_redx_password
```

---

## 🚀 Usage

### Basic Usage

```php
use FraudCheckerBdCourier;

$result = FraudCheckerBdCourier::check('01712345678');

print_r($result);
```

**Output:**

```php
[
    'steadfast' => ['success' => 3, 'cancel' => 1, 'total' => 4, 'success_ratio' => 75.0],
    'pathao' => ['success' => 5, 'cancel' => 2, 'total' => 7, 'success_ratio' => 71.43],
    'redx' => ['success' => 20, 'cancel' => 5, 'total' => 25, 'success_ratio' => 80.0],
    'aggregate' => [
        'total_success' => 28,
        'total_cancel' => 8,
        'total_deliveries' => 36,
        'success_ratio' => 77.78,
        'cancel_ratio' => 22.22
    ]
]
```

---

## ☎️ Phone Number Validation

The package automatically validates phone numbers with this regex:

```php
/^01[3-9][0-9]{8}$/
```

✅ Valid examples:

- `01712345678`
- `01876543219`

❌ Invalid examples:

- `+8801712345678` (includes country code)
- `1234567890` (too short)
- `02171234567` (invalid prefix)

---

## 🛠️ Advanced Usage (SOLID Principles)

In Version 1.0.0, the package was refactored to adhere to SOLID principles. All specific courier services now implement the `Azmolla\FraudCheckerBdCourier\Contracts\CourierServiceInterface`, which enforces the standard `getDeliveryStats(string $phoneNumber): array` method. This makes the codebase extremely DRY and allows for easy expansion in the future simply by binding new classes to the interface.

### Using Individual Services

```php
use Azmolla\FraudCheckerBdCourier\Services\PathaoService;
use Azmolla\FraudCheckerBdCourier\Services\SteadfastService;
use Azmolla\FraudCheckerBdCourier\Services\RedxService;

// All classes implement CourierServiceInterface
$pathao = (new PathaoService())->getDeliveryStats('01712345678');
$steadfast = (new SteadfastService())->getDeliveryStats('01712345678');
$redx = (new RedxService())->getDeliveryStats('01712345678');
```

### Custom Validation Rules

```php
use Azmolla\FraudCheckerBdCourier\Helpers\CourierFraudCheckerHelper;

CourierFraudCheckerHelper::validatePhoneNumber('01712345678');
```

---

## ✅ Testing

Run the tests using PHPUnit. Tests use mocked HTTP responses to execute locally without hitting actual courier API's or needing `.env` credentials.

```bash
composer require --dev orchestra/testbench:"^6.0" phpunit/phpunit:"^9.5" guzzlehttp/promises:"^1.5.3"
./vendor/bin/phpunit
```

---

## 🧹 Troubleshooting

### Common Issues

1. **Missing Environment Variables**
   - Ensure all required credentials are set in `.env`
   - Run `php artisan config:clear` after updating

2. **Invalid Phone Number Format**
   - Must use local (BD) format like `01712345678`
   - Do **not** include `+88` prefix

---

## 📝 License

This package is open-source software licensed under the [GNU General Public License v3.0 (GPL-3.0)](https://opensource.org/licenses/GPL-3.0).

Under this license:

✅ **You are allowed to:**

- Use the package for personal or commercial projects.
- Modify the source code for your own use.
- Distribute the modified or original source code **provided** you also license it under **GPL-3.0**.
- Study and learn from the source code freely.

❌ **You are NOT allowed to:**

- Re-license the package under a different license.
- Distribute the package as part of a proprietary/commercial closed-source software without making your source code public.
- Sub-license or sell the software under a restrictive license.

**Important:**  
If you distribute modified versions of this package, you must also release your changes under the GPL-3.0 license and include the original copyright.

> GPL-3.0 promotes **freedom** to use, share, and modify, but ensures that any distributed version remains **free and open-source**.

---

## 💬 Support

For issues and feature requests, please open an issue in the repository.
