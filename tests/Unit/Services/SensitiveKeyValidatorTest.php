<?php

namespace Tests\Unit\Services;

use App\Services\SensitiveKeyValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SensitiveKeyValidatorTest extends TestCase
{
    private SensitiveKeyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new SensitiveKeyValidator();
    }

    #[Test]
    public function it_detects_smtp_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('smtp_password'));
        $this->assertTrue($this->validator->isSensitive('smtp_host'));
    }

    #[Test]
    public function it_detects_aws_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('aws_access_key'));
        $this->assertTrue($this->validator->isSensitive('aws_secret'));
    }

    #[Test]
    public function it_detects_password_in_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('db_password'));
        $this->assertTrue($this->validator->isSensitive('user_password_hash'));
    }

    #[Test]
    public function it_detects_secret_in_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('app_secret'));
        $this->assertTrue($this->validator->isSensitive('api_secret_key'));
    }

    #[Test]
    public function it_detects_token_in_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('access_token'));
        $this->assertTrue($this->validator->isSensitive('refresh_token'));
    }

    #[Test]
    public function it_detects_credential_in_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('service_credentials'));
    }

    #[Test]
    public function it_detects_sendgrid_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('sendgrid_api_key'));
    }

    #[Test]
    public function it_detects_mailgun_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('mailgun_api_key'));
    }

    #[Test]
    public function it_detects_twilio_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('twilio_account_sid'));
    }

    #[Test]
    public function it_detects_stripe_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('stripe_api_key'));
    }

    #[Test]
    public function it_detects_slack_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('slack_webhook_url'));
    }

    #[Test]
    public function it_detects_github_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('github_token'));
    }

    #[Test]
    public function it_detects_openai_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('openai_api_key'));
    }

    #[Test]
    public function it_detects_mailchimp_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('mailchimp_api_key'));
    }

    #[Test]
    public function it_detects_facebook_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('fb_app_secret'));
        $this->assertTrue($this->validator->isSensitive('facebook_access_token'));
    }

    #[Test]
    public function it_detects_google_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('google_oauth_client_secret'));
    }

    #[Test]
    public function it_detects_jwt_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('jwt_secret'));
    }

    #[Test]
    public function it_detects_private_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('private_key'));
    }

    #[Test]
    public function it_detects_encryption_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('encryption_key'));
    }

    #[Test]
    public function it_detects_paypal_sensitive_key(): void
    {
        $this->assertTrue($this->validator->isSensitive('paypal_client_secret'));
    }

    #[Test]
    public function it_returns_false_for_non_sensitive_keys(): void
    {
        $this->assertFalse($this->validator->isSensitive('site_name'));
        $this->assertFalse($this->validator->isSensitive('company_address'));
        $this->assertFalse($this->validator->isSensitive('contact_email'));
        $this->assertFalse($this->validator->isSensitive('footer_text'));
    }

    #[Test]
    public function it_is_case_insensitive(): void
    {
        $this->assertTrue($this->validator->isSensitive('SMTP_PASSWORD'));
        $this->assertTrue($this->validator->isSensitive('Aws_Access_Key'));
        $this->assertTrue($this->validator->isSensitive('PASSWORD'));
    }
}
