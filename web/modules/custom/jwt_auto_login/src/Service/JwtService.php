<?php

namespace Drupal\jwt_auto_login\Service;

require_once DRUPAL_ROOT . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Drupal\Core\Site\Settings;

/**
 * Provides JWT generation and decoding services for auto-login functionality.
 */
class JwtService {

  /**
   * The secret key used for signing and verifying JWT tokens.
   *
   * @var string
   */
  private $secret;

  /**
   * The allowed time leeway in seconds for validating token expiration.
   *
   * @var int
   */
  private $leeway = 60;

  /**
   * Constructs a new JwtService object.
   *
   * @throws \Exception
   *   Thrown when the JWT secret key is not set in settings.php.
   */
  public function __construct() {
    $this->secret = Settings::get('jwt_auto_login_secret');
    if (empty($this->secret)) {
      throw new \Exception('JWT secret not set in settings.php!');
    }

    JWT::$leeway = $this->leeway;
  }

  /**
   * Generates a new JWT token with the given payload and expiration.
   *
   * @param array $payload
   *   The data to encode into the JWT.
   * @param int $expireSeconds
   *   (optional) The number of seconds before the token expires. Defaults to 900.
   *
   * @return string
   *   The generated JWT token.
   */
  public function generateToken(array $payload, int $expireSeconds = 900): string {
    $issuedAt = time();
    $payload['iat'] = $issuedAt;
    $payload['exp'] = $issuedAt + $expireSeconds;

    return JWT::encode($payload, $this->secret, 'HS256');

  }

  /**
   * Decodes a given JWT token and returns its payload.
   *
   * @param string $token
   *   The JWT token to decode.
   *
   * @return object|null
   *   The decoded payload object, or NULL if decoding fails.
   */
  public function decodeToken(string $token) {
    try {
      return JWT::decode($token, new Key($this->secret, 'HS256'));
    }
    catch (\Exception $e) {
      \Drupal::logger('jwt_auto_login')->error('JWT decode failed: ' . $e->getMessage());
      return NULL;
    }
  }

}
