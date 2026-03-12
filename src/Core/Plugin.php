<?php

namespace Ref247\Core;

use Ref247\Admin\AdminPage;
use Ref247\Admin\Settings;
use Ref247\Tracking\ReferralTracker;
use Ref247\Shortcodes\SignupShortcode;
use Ref247\Integrations\WooCommerceIntegration;
use Ref247\Integrations\ContactForm7Integration;
use Ref247\Integrations\WPFormsIntegration;
use Ref247\Integrations\GravityFormsIntegration;

class Plugin
{
    public function run()
    {
        new ReferralTracker();
        new AdminPage();
        new Settings();
        new SignupShortcode();
        new WooCommerceIntegration();
        new ContactForm7Integration();
        new WPFormsIntegration();
        new GravityFormsIntegration();
    }
}