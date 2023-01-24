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
   * Test for the userPropertiesIgnore method.
   */
  public function testExtractFromEmailClaim(): void {
    $input = [
      "email" => "foo@example.com",
    ];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "foo@example.com");
  }

  /**
   *
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
   *
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
   *
   */
  public function testExtractFromEmailInUpstreamIdPToken(): void {
    // Secret is 'a' - see jwt.io.
    $input = [
      "idp_access_token" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJlbWFpbCI6InVwc3RyZWFtQGV4YW1wbGUuY29tIn0.m9LZdnfe9yhFmnNm5pXmQhR9pLNMXKCps-EFLq4WcPQ",
    ];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "upstream@example.com");
  }

  /**
   *
   */
  public function testExtractFromUPNInUpstreamIdPToken(): void {
    // Secret is 'a' - see jwt.io.
    $input = [
      "idp_access_token" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJ1cG4iOiJvdGhlckBleGFtcGxlLmNvbSJ9.zFUbWQ0axMOyT4nTpX14FYXW8mFh1t5gjtnhXOQq098",
    ];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "other@example.com");
  }

  /**
   *
   */
  public function testMissingEmailDoesntError(): void {
    $input = [];
    $email = OpenIDConnectB2CClient::extractEmail($input);
    $this->assertEquals($email, "");
  }

  /**
   *
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
