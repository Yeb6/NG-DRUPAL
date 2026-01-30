<?php

namespace Drupal\fs_article_tools\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\node\NodeInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Theme\ThemeInitialization;

class ThemeSubscriber implements EventSubscriberInterface {

  protected $themeManager;
  protected $themeInitialization;

  public function __construct(ThemeManagerInterface $theme_manager, ThemeInitialization $theme_initialization) {
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['setThemeForFsArticle', 30],
    ];
  }

  public function setThemeForFsArticle(RequestEvent $event) {
    $request = $event->getRequest();
    $route_match = \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();

    $node = $route_match->getParameter('node') ?: $request->attributes->get('node_preview');

    if ($node instanceof NodeInterface && $node->bundle() === 'fs_article' &&
        in_array($route_name, ['entity.node.canonical', 'entity.node.preview'])) {
      $active_theme = $this->themeInitialization->initTheme('fs_theme');
      $this->themeManager->setActiveTheme($active_theme);
    }
  }
}
