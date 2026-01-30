<?php

namespace Drupal\jwt_auto_login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\jwt_auto_login\Service\JwtService;

class MarketLinksController extends ControllerBase {

  public function show() {
    $current_user = \Drupal::currentUser();
    if (!$current_user->isAuthenticated()) {
      return ['#markup' => 'User is not logged in.'];
    }

    $username = $current_user->getAccountName();
    $user_roles = array_unique($current_user->getRoles());

    $config = \Drupal::config('jwt_auto_login.settings');
    $role_map = json_decode($config->get('role_mappings') ?? '[]', TRUE);

    $jwt_service = new JwtService();
    $session_id = \Drupal::service('session')->getId();

    $scheme_host = \Drupal::request()->getSchemeAndHttpHost();
    $base_path = trim(\Drupal::request()->getBasePath(), '/');
    $current_site = $scheme_host . ($base_path ? '/' . $base_path : '');
    $site_name = \Drupal::config('system.site')->get('name') ?? 'This Site';

    $market_links = [];

    //  Add current site link
    $payload = [
      'username' => $username,
      'roles' => $user_roles,
      'site' => $current_site,
      'session_id' => $session_id,
    ];
    $token = $jwt_service->generateToken($payload, 900);

    $market_links[] = [
      '#type' => 'link',
      '#title' => $site_name,
      '#url' => Url::fromUri($current_site . '/auto-login?token=' . $token),
      '#attributes' => [
        'target' => '_blank',
        'class' => ['current-site'],
       ],
    ];

    //  HQ sites
    foreach ($user_roles as $role) {
      if (!empty($role_map['hq_sites'][$role]['sites'])) {
        $target_role = $role_map['hq_sites'][$role]['target_role'];
        foreach ($role_map['hq_sites'][$role]['sites'] as $site_url => $market_name) {
          $site_url = rtrim($scheme_host, '/') . '/' . $site_url;
          $payload = [
            'username' => $username,
            'roles' => [$target_role],
            'site' => $site_url,
            'session_id' => $session_id,
          ];
          $token = $jwt_service->generateToken($payload, 900);

          $market_links[] = [
            '#type' => 'link',
            '#title' => $market_name,
            '#url' => Url::fromUri($site_url . '/auto-login?token=' . $token),
            '#attributes' => ['target' => '_blank'],
          ];
        }
      }
    }

    //  Market user-specific sites
    if (!empty($role_map['market_sites'][$username]['roles'])) {
      foreach ($role_map['market_sites'][$username]['roles'] as $role => $role_data) {
        foreach ($role_data['sites'] as $site_url => $market_name) {
          $site_url = rtrim($scheme_host, '/') . '/' . $site_url;
          $payload = [
            'username' => $username,
            'roles' => [$role],
            'site' => $site_url,
            'session_id' => $session_id,
          ];
          $token = $jwt_service->generateToken($payload, 900);

          $market_links[] = [
            '#type' => 'link',
            '#title' => $market_name,
            '#url' => Url::fromUri($site_url . '/auto-login?token=' . $token),
            '#attributes' => ['target' => '_blank'],
          ];
        }
      }
    }

    return [
      '#theme' => 'item_list',
      '#items' => $market_links,
      '#cache' => ['max-age' => 0],
    ];
  }

}
