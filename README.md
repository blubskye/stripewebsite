<div align="center">

# Stripe Payment Gateway

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-6.4%20LTS-000000.svg)](https://symfony.com)
[![Stripe](https://img.shields.io/badge/Stripe-API-635BFF.svg)](https://stripe.com)

A Stripe payment processor and webhook handler built with Symfony. Provides a secure bridge between merchants and Stripe's payment infrastructure.

[Features](#features) • [Requirements](#requirements) • [Installation](#installation) • [API](#api-documentation) • [License](#license)

</div>

---

## Features

- **Stripe Checkout Integration** - Secure payment processing via Stripe Checkout
- **Token-Based Purchases** - Unique, cryptographically secure purchase tokens
- **Merchant API** - REST API for creating and verifying payment tokens
- **Webhook Notifications** - Automatic notifications to merchant systems on payment completion
- **Retry Logic** - Automatic retry of failed webhook deliveries via cron job
- **Multi-Merchant Support** - Support for multiple merchant accounts with authentication

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 8.2+, Symfony 6.4 LTS |
| Database | MySQL with Doctrine ORM |
| Payments | Stripe PHP SDK |
| HTTP Client | Guzzle |
| Frontend | Bootstrap 4, Twig |

## Requirements

- PHP 8.2 or higher
- MySQL 5.7+
- Composer
- Node.js & npm/yarn (for building assets)
- Stripe Account with API keys

## Installation

### Quick Start

```bash
# Clone the repository
git clone https://github.com/blubskye/stripewebsite.git
cd stripewebsite

# Install PHP dependencies
composer install

# Install Node dependencies and build assets
npm install
npm run build

# Configure environment
cp .env .env.local
# Edit .env.local with your settings

# Run database migrations
bin/console doctrine:migrations:migrate
```

### Environment Configuration

Create a `.env.local` file with:

```env
APP_ENV=prod
APP_SECRET=your_random_secret_here

DATABASE_URL="mysql://user:password@localhost:3306/stripewebsite"

STRIPE_SECRET_KEY=sk_live_your_stripe_secret_key
STRIPE_PUBLIC_KEY=pk_live_your_stripe_public_key
```

### Cron Jobs

Set up the webhook retry cron job:

```bash
* * * * * /usr/bin/php /path/to/project/bin/console app:payments:process-client-failures
```

This retries failed webhook notifications every minute.

## Payment Flow

```
┌──────────┐     ┌──────────────┐     ┌────────────────┐     ┌────────┐
│ Merchant │────>│ Create Token │────>│ User Purchases │────>│ Stripe │
└──────────┘     └──────────────┘     └────────────────┘     └────────┘
                                                                  │
┌──────────┐     ┌──────────────┐     ┌────────────────┐          │
│ Complete │<────│   Webhook    │<────│ Payment Success│<─────────┘
└──────────┘     └──────────────┘     └────────────────┘
```

1. Merchant creates a purchase token via API
2. User is directed to `/purchase/{token}` to view payment details
3. User clicks "Checkout with Stripe" and completes payment
4. On success, webhook is sent to merchant's system
5. User is redirected to merchant's success URL

## API Documentation

### Authentication

All API requests require merchant authentication via headers:

```
X-Client-ID: {merchant_id}
X-Client-Secret: {merchant_secret}
```

### Create Purchase Token

```http
POST /api/v1/token
Content-Type: application/json

{
  "transactionID": "unique_transaction_id",
  "price": 999,
  "description": "Premium Upgrade",
  "successURL": "https://yoursite.com/success",
  "cancelURL": "https://yoursite.com/cancel",
  "failureURL": "https://yoursite.com/failure",
  "webhookURL": "https://yoursite.com/webhook"
}
```

**Response:**
```json
{
  "token": "a1b2c3d4e5f6...",
  "redirect": "https://payment-site.com/purchase/a1b2c3d4e5f6..."
}
```

### Verify Token

```http
POST /api/v1/verify
Content-Type: application/json

{
  "token": "a1b2c3d4e5f6..."
}
```

**Response:**
```json
{
  "isValid": true,
  "isPurchased": true,
  "isSuccess": true
}
```

### Webhook Payload

When payment completes, a POST request is sent to `webhookURL`:

```json
{
  "token": "a1b2c3d4e5f6...",
  "transactionID": "unique_transaction_id",
  "price": 999
}
```

## Project Structure

```
stripewebsite/
├── src/
│   ├── Controller/
│   │   ├── ApiController.php      # REST API endpoints
│   │   ├── PurchaseController.php # Payment flow
│   │   └── HomeController.php     # Homepage
│   ├── Entity/
│   │   ├── PurchaseToken.php      # Payment token entity
│   │   └── Merchant.php           # Merchant entity
│   ├── Services/
│   │   ├── StripeService.php      # Stripe API wrapper
│   │   └── WebhookService.php     # Outbound webhooks
│   └── Command/
│       └── ProcessClientFailuresCommand.php
├── templates/                      # Twig templates
├── config/                         # Symfony configuration
└── public/                         # Web root
```

## Security

- **Cryptographically Secure Tokens** - Uses `random_bytes()` for token generation
- **Password Hashing** - Merchant secrets are hashed with `password_hash()`
- **Parameterized Queries** - All database queries use Doctrine ORM
- **HTTPS Required** - All production deployments should use HTTPS

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the **GNU Affero General Public License v3.0** - see the [LICENSE](LICENSE) file for details.

[![AGPL v3](https://www.gnu.org/graphics/agplv3-with-text-162x68.png)](https://www.gnu.org/licenses/agpl-3.0)

## Links

- **Repository**: [github.com/blubskye/stripewebsite](https://github.com/blubskye/stripewebsite)
- **Stripe Documentation**: [stripe.com/docs](https://stripe.com/docs)

---

<div align="center">
Made with PHP and Stripe
</div>
