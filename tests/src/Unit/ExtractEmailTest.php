<?php

declare(strict_types = 1);

namespace Drupal\Tests\openid_connect_azure_b2c\Unit;

use Drupal\openid_connect_azure_b2c\Plugin\OpenIDConnectClient\OpenIDConnectB2CClient;
use Drupal\Tests\UnitTestCase;

/**
 * Provides tests for the OpenID Connect b2c email.
 *
 * @coversDefaultClass \Drupal\openid_connect_azure_b2c\Plugin\OpenIDConnectClient
 * @group openid_connect_azure_b2c
 */
class ExtractEmailTest extends UnitTestCase {

  /**
   * Extract the email from a basic email claim.
   */
  public function testExtractFromEmailClaim(): void {
    $input = [
      "email" => "foo@example.com",
    ];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "foo@example.com");
  }

  /**
   * Extract email from the emails claim.
   *
   * B2C can send an emails claim which consists of various
   * emails known to the system. If it does this, use the
   * first.
   */
  public function testExtractFromFirstInEmailsClaim(): void {
    $input = [
      "emails" => [
        "foo@example.com",
        "bar@example.com",
      ],
    ];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "foo@example.com");
  }

  /**
   * Prefer the email claim to the emails claim.
   *
   * If both email and emails are sent, prefer the singular
   * email, as there's not actually any canonical ordering
   * in the emails claim.
   */
  public function testPreferEmailToEmails(): void {
    $input = [
      "email" => "other@example.net",
      "emails" => [
        "foo@example.com",
        "bar@example.com",
      ],
    ];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "other@example.net");
  }

  /**
   * Use email in the upstream token.
   *
   * If we have neither email or emails, but we have the
   * idp_access_token claim then use that. This claim contains
   * a JWT that came from the IdP that was used by B2C to validate
   * the login. Check for an email claim in that.
   */
  public function testExtractFromEmailInUpstreamIdpToken(): void {
    // Secret is 'a' - see jwt.io.
    $input = [
      "idp_access_token" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJlbWFpbCI6InVwc3RyZWFtQGV4YW1wbGUuY29tIn0.m9LZdnfe9yhFmnNm5pXmQhR9pLNMXKCps-EFLq4WcPQ",
    ];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "upstream@example.com");
  }

  /**
   * Use upn in the upstream token.
   *
   * Also consider a upn claim in that upstream token, which can
   * often be an email.
   */
  public function testExtractFromUpnInUpstreamIdpToken(): void {
    // Secret is 'a' - see jwt.io.
    $input = [
      "idp_access_token" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJ1cG4iOiJvdGhlckBleGFtcGxlLmNvbSJ9.zFUbWQ0axMOyT4nTpX14FYXW8mFh1t5gjtnhXOQq098",
    ];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "other@example.com");
  }

  /**
   * Return an empty string if no match.
   *
   * If there are no matching claims to consider, return an
   * empty string.
   */
  public function testMissingEmailDoesntError(): void {
    $input = [];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "");
  }

  /**
   * Only use the emails claim if it's non-empty.
   *
   * Finally, validate that the emails claim is only used
   * if it contains at least one email.
   */
  public function testEmptyAlternativeEmailsDoesntPreventUsingIdP(): void {
    // Secret is 'a' - see jwt.io.
    $input = [
      "emails" => [],
      "idp_access_token" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJ1cG4iOiJvdGhlckBleGFtcGxlLmNvbSJ9.zFUbWQ0axMOyT4nTpX14FYXW8mFh1t5gjtnhXOQq098",
    ];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "other@example.com");
  }

}
