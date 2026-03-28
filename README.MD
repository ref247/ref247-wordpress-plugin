# Ref247 Affiliate Tracking

Ref247 Affiliate Tracking is a WordPress plugin that integrates with the Ref247.io affiliate management platform. It enables site owners to build and manage an affiliate program directly from their WordPress dashboard.

## Key Features

- **Real-time Affiliate Dashboard** – View organization statistics, commissions, and affiliate trends at a glance
- **Commission Tracking** – Automatic commission calculation and management for referrals
- **WooCommerce Integration** – Seamlessly track e-commerce purchases as referral actions
- **Multi-Form Support** – Integrate with WPForms, Gravity Forms, Contact Form 7, and more
- **Affiliate Signup Page** – Auto-generated signup page for affiliate registration
- **Commission Rules** – Flexible commission structures managed in the Ref247.io dashboard
- **Analytics & Charts** – Visual insights into commission trends and referral performance
- **Multi-Currency Support** – Track commissions across different currencies

## Build plugin
```
docker compose up composer
zip -r ref247-affiliate-tracking.zip src/ assets/js/ vendor/ composer.json composer.lock readme.txt ref247.php
```