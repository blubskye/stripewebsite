<div align="center">

# Stripe Payment Gateway

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-777BB4.svg)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-8.0-000000.svg)](https://symfony.com)
[![Node.js](https://img.shields.io/badge/Node.js-24%2B-339933.svg)](https://nodejs.org)
[![Redis](https://img.shields.io/badge/Redis-8.4%2B-DC382D.svg)](https://redis.io)
[![Stripe](https://img.shields.io/badge/Stripe-API-635BFF.svg)](https://stripe.com)

A Stripe payment processor and webhook handler built with Symfony. Provides a secure bridge between merchants and Stripe's payment infrastructure.

**Part of the NSFW Discord Directory ecosystem** - This payment gateway handles premium tier purchases for [nsfwdiscordme](https://github.com/blubskye/nsfwdiscordme). Both projects share the same MariaDB server.

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
| Backend | PHP 8.5+, Symfony 8.0 |
| Database | MariaDB 10.6+ with Doctrine ORM |
| Cache | Redis 8.4+ (async I/O) |
| Payments | Stripe PHP SDK |
| HTTP Client | Guzzle |
| Frontend | Node.js 24+, Webpack 5, Bootstrap 5, Twig |

## Requirements

- PHP 8.5 or higher
- MariaDB 10.6+
- Redis 8.4+ (for async I/O threading)
- Composer 2.9.2+ (for security blocking)
- Node.js 24+ with npm 11+
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

DATABASE_URL="mysql://user:password@localhost:3306/stripewebsite?serverVersion=mariadb-10.6"

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
│   ├── Security/
│   │   ├── RateLimiter.php        # Request rate limiting
│   │   └── UrlValidator.php       # URL validation (SSRF/redirect)
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

This application implements multiple layers of security to protect against common web vulnerabilities:

### Authentication & Authorization
- **Cryptographically Secure Tokens** - Uses `random_bytes()` for token generation
- **Password Hashing** - Merchant secrets are hashed with `password_hash()` (bcrypt)
- **Timing-Attack Protection** - Constant-time comparison using `hash_equals()` and dummy hash verification to prevent authentication timing attacks (CWE-208)

### Input Validation
- **Price Validation** - Enforces minimum ($0.50) and maximum ($10,000) price bounds to prevent manipulation (CWE-20)
- **URL Validation** - Validates redirect and webhook URLs against allowlists and blocks private/internal addresses
- **Parameterized Queries** - All database queries use Doctrine ORM to prevent SQL injection (CWE-89)

### Request Forgery Protection
- **CSRF Protection** - Payment forms include CSRF tokens validated server-side (CWE-352)
- **Open Redirect Prevention** - Redirect URLs validated against allowed domains (CWE-601)
- **SSRF Protection** - Webhook URLs validated to block internal networks, localhost, and cloud metadata endpoints (CWE-918)

### Rate Limiting
- **API Rate Limiting** - Token creation limited to 100 requests/hour per IP (CWE-799)
- **Authentication Rate Limiting** - Login attempts limited to 10 requests/minute per IP
- **Verification Rate Limiting** - Token verification limited to prevent enumeration attacks

### Infrastructure
- **HTTPS Required** - All production deployments should use HTTPS
- **Secure Headers** - Configure your web server with appropriate security headers

### Security Classes
| Class | Purpose |
|-------|---------|
| `UrlValidator` | Validates redirect/webhook URLs against SSRF and open redirect attacks |
| `RateLimiter` | Provides request rate limiting to prevent abuse |

## Performance Optimizations

This project includes several performance optimizations for production environments.

### Node.js 24 & Webpack Optimizations

The build system requires Node.js 24+ for optimal performance:

```bash
# Check Node.js version
node --version  # Should be v24.x.x

# Development build
npm run dev

# Production build (minified, no source maps, content hashing)
NODE_ENV=production npm run build
```

**Node.js 24 Features Used:**
- V8 13.6 engine with 15-30% performance improvements
- Enhanced async/await performance
- npm 11 with faster dependency resolution

**Webpack Build Optimizations:**
- Parallel minification using all CPU cores
- LightningCSS for faster CSS minification
- Filesystem caching for faster rebuilds
- ECMAScript 2024 output targeting modern browsers
- Tree shaking with `usedExports` and `sideEffects`
- Content hashing for cache busting

### Redis 8.4+ with Async I/O

Configure Redis 8.4+ for session storage and application caching:

```env
# .env.local
REDIS_URL=redis://localhost:6379
SESSION_HANDLER=redis://localhost:6379
```

**Redis Server Configuration** (`config/redis/redis-8.4.conf`):
```ini
# Enable async I/O threads (set to CPU core count, max 8)
io-threads 8
io-threads-do-reads yes
```

**Performance Improvements:**
- 37-112% throughput improvement with async I/O threads
- Persistent connections reduce connection overhead
- xxHash (xxh3) for faster key hashing in rate limiter

**Cache Pools:**
- **Session Storage** - Redis-backed sessions for scalability
- **Stripe API Caching** - 5-minute TTL cache for session lookups
- **Rate Limiting Cache** - 1-hour TTL for rate limit counters

### Database Optimizations

The following indexes are configured for query performance:

| Table | Index | Purpose |
|-------|-------|---------|
| `purchase_token` | `idx_token` | Token lookups |
| `purchase_token` | `idx_client_failure` | Failed webhook queries |
| `purchase_token` | `idx_date_created` | Expiration checks |
| `purchase_token` | `idx_stripe_id` | Stripe reconciliation |

After updating, run migrations:
```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

### Batch Processing

The `app:payments:process-client-failures` command uses batch flushing (50 records at a time) to reduce database overhead when retrying failed webhooks.

### PHP OPcache & JIT

For optimal performance, configure PHP OPcache in production:

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0

; PHP 8.4+ JIT
opcache.jit=tracing
opcache.jit_buffer_size=100M
```

### NPM Dependencies for Optimization

Install the required optimization packages:

```bash
npm install --save-dev terser-webpack-plugin css-minimizer-webpack-plugin lightningcss
```

### Composer 2.9.2+ Optimizations

The project uses Composer 2.9.2+ with security and performance enhancements:

```bash
# Update Composer to 2.9.2+
composer self-update

# Run security audit
composer audit

# Update with minimal changes (safer)
composer update --minimal-changes

# Update with patch-only restrictions
composer update --patch-only
```

**Security Features (Composer 2.9+):**
- `audit.block-insecure: true` - Blocks updates to vulnerable packages
- `audit.abandoned: report` - Reports abandoned packages
- Automatic security advisory checking during updates

**Performance Optimizations:**
- `optimize-autoloader: true` - Generates optimized class maps
- `classmap-authoritative: true` - Only loads from classmap (no filesystem checks)
- `apcu-autoloader: true` - Uses APCu for autoloader caching
- `platform-check: true` - Validates PHP version and extensions

### Infrastructure Summary

| Component | Version | Key Feature |
|-----------|---------|-------------|
| Node.js | 24+ | V8 13.6 with 15-30% perf gains |
| Redis | 8.4+ | Async I/O threads (37-112% throughput) |
| PHP | 8.5+ | JIT compilation |
| Composer | 2.9.2+ | Security blocking, optimized autoloader |
| Webpack | 5.97+ | Parallel processing |

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

## Related Projects

| Project | Description |
|---------|-------------|
| [nsfwdiscordme](https://github.com/blubskye/nsfwdiscordme) | Discord server directory - uses this gateway for premium purchases |

Both projects are designed to run together on the same server, sharing the same MariaDB instance with separate databases.

## Links

- **Repository**: [github.com/blubskye/stripewebsite](https://github.com/blubskye/stripewebsite)
- **Stripe Documentation**: [stripe.com/docs](https://stripe.com/docs)

---

<div align="center">
Made with PHP and Stripe
</div>
