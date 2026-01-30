<?php

namespace Drupal\jwt_auto_login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Drupal\jwt_auto_login\Service\JwtService;

class AutoLoginController extends ControllerBase {

  public function autoLogin(Request $request) {
    $token = $request->query->get('token');
    if (!$token) {
      return ['#markup' => 'No token provided'];
    }

    $jwt_service = new JwtService();
    $decoded = $jwt_service->decodeToken($token);

    if (!$decoded) {
      return new RedirectResponse('/admin/dashboard');
    }

    $decoded = (array) $decoded;
    $username = $decoded['username'];
    $roles = $decoded['roles'] ?? []; // now it's always an array
    $expected_site = $decoded['site'] ?? NULL;

    $scheme_host = \Drupal::request()->getSchemeAndHttpHost();
    $base_path = trim(\Drupal::request()->getBasePath(), '/');
    $current_site = $scheme_host . ($base_path ? '/' . $base_path : '');

    if ($expected_site && $expected_site !== $current_site) {
      return ['#markup' => 'Site mismatch'];
    }

    //  Load or create the user
    $user = user_load_by_name($username);
    if (!$user) {
      $user = User::create([
        'name' => $username,
        'status' => 1,
      ]);
    }

    //  Clear all roles except 'authenticated' before assigning
    foreach ($user->getRoles() as $existing_role) {
      if ($existing_role !== 'authenticated') {
        $user->removeRole($existing_role);
      }
    }

    //  Add new roles safely (avoid duplicates and length overflow)
    foreach ($roles as $role) {
      if (!empty($role) && $role !== 'authenticated' && !$user->hasRole($role)) {
        $user->addRole($role);
      }
    }

    $user->save();

    //  Log in user
    user_login_finalize($user);
    return new RedirectResponse('/admin/dashboard');
  }

}
