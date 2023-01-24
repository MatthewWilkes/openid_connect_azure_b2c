<?php

namespace Drupal\openid_connect_azure_b2c\Plugin\OpenIDConnectClient;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;

/**
 * Azure B2C OpenID Connect client.
 *
 * Used to connect to Azure B2C.
 *
 * @OpenIDConnectClient(
 *   id = "b2c",
 *   label = @Translation("Azure B2C")
 * )
 */
class OpenIDConnectB2CClient extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'tenant' => '',
      'flow' => 'b2c_1_flow_name',
      'scopes' => ['openid', 'email', 'profile'],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['tenant'] = [
      '#title' => $this->t('Name of the B2C tenant'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['tenant'],
    ];
    $form['flow'] = [
      '#title' => $this->t('Name of the B2C flow'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['flow'],
    ];

    $form['scopes'] = [
      '#title' => $this->t('Scopes'),
      '#type' => 'textfield',
      '#description' => $this->t('Custom scopes, separated by spaces, for example: openid email'),
      '#default_value' => implode(' ', $this->configuration['scopes']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $form_state->getValues();
    if (!empty($configuration['scopes'])) {
      $this->setConfiguration(['scopes' => explode(' ', $configuration['scopes'])]);
    }

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getClientScopes(): ?array {
    return $this->configuration['scopes'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints() : array {
    return [
      'authorization' => 'https://' . $this->configuration['tenant'] . '.b2clogin.com/' . $this->configuration['tenant'] . '.onmicrosoft.com/oauth2/v2.0/authorize?p=' . $this->configuration['flow'],
      'token' => 'https://' . $this->configuration['tenant'] . '.b2clogin.com/' . $this->configuration['tenant'] . '.onmicrosoft.com/oauth2/v2.0/token?p=' . $this->configuration['flow'],
      'userinfo' => '',
      'end_session' => 'https://' . $this->configuration['tenant'] . '.b2clogin.com/' . $this->configuration['tenant'] . '.onmicrosoft.com/oauth2/v2.0/logout?p=' . $this->configuration['flow'],
    ];
  }

  /**
   * Parse the token from upstream.
   */
  protected static function parseToken(string $token): array {
    $parts = explode('.', $token, 3);
    if (count($parts) === 3) {
      $decoded = Json::decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])));
      if (is_array($decoded)) {
        return $decoded;
      }
    }
    return [];
  }

  /**
   * Extract the email from various options within the token.
   */
  public static function extractEmail(array $userinfo) : string {
    // The email can be in multiple places, try and extract it from each in turn.
    $email = '';
    if (array_key_exists('email', $userinfo)) {
      // Prefer the email claim if present.
      $email = $userinfo['email'];
    }
    elseif (array_key_exists('emails', $userinfo) && array_key_exists(0, $userinfo['emails'])) {
      // If not, and there are alternate emails, use the first one.
      $email = $userinfo['emails'][0];
    }
    elseif (array_key_exists('idp_access_token', $userinfo)) {
      // If neither are present, but we have a proxied IdP token, extract data from that.
      $idp_userinfo = OpenIDConnectB2CClient::parseToken($userinfo['idp_access_token']);
      if (array_key_exists('email', $idp_userinfo)) {
        // Prefer email from the upstream token.
        $email = $idp_userinfo['email'];
      }
      elseif (array_key_exists('upn', $idp_userinfo)) {
        // But settle for the unique name.
        $email = $idp_userinfo['upn'];
      }
    }
    return $email;
  }

}
