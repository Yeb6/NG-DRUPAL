<?php

declare(strict_types=1);

namespace Drupal\moderation_status_sync\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Service to alter the pull form based on active languages.
 */
final class PullFormAlter {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Constructs a new PullFormAlter object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(LanguageManagerInterface $languageManager) {
    $this->languageManager = $languageManager;
  }

  /**
   * Alters the pull form to filter channel options by language.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID.
   */
  public function alterForm(array &$form, FormStateInterface $form_state, string $form_id): void {
    $current_languages = $this->languageManager->getLanguages();
    $current_langcodes = array_keys($current_languages);

    if (isset($form['channel_wrapper']['channel']['#options'])) {
      $options = $form['channel_wrapper']['channel']['#options'];

      $filtered = array_filter($options, function ($value, $key) use ($current_langcodes) {
        // Include if key ends with any of the language codes.
        foreach ($current_langcodes as $langcode) {
          if (str_ends_with($key, "_$langcode")) {
            return true;
          }
        }

        return false;
      }, ARRAY_FILTER_USE_BOTH);

      $form['channel_wrapper']['channel']['#options'] = !empty($filtered) ? $filtered : [];
    }
  }

}
