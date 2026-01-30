<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to map language codes from JSON to Drupal language codes.
 *
 * @MigrateProcessPlugin(
 *   id = "language_mapper"
 * )
 */
class LanguageMapper extends ProcessPluginBase {

  /**
   * Language mapping from JSON values to Drupal language codes.
   *
   * @var array
   */
  protected $languageMapping = [
    'EN_US' => 'en',
    'EN_GB' => 'en',
    'EN' => 'en',
    'DE_DE' => 'de',
    'DE' => 'de',
    'FR_FR' => 'fr',
    'FR' => 'fr',
    'ES_ES' => 'es',
    'ES' => 'es',
    'NL_NL' => 'nl',
    'NL' => 'nl',
    'BG_BG' => 'bg',
    'BG' => 'bg',
    'DA_DK' => 'da',
    'DA' => 'da',
    'FI_FI' => 'fi',
    'FI' => 'fi',
    'EL_GR' => 'el',
    'EL' => 'el',
    'IT_IT' => 'it',
    'IT' => 'it',
    'PL_PL' => 'pl',
    'PL' => 'pl',
    'PT_PT' => 'pt',
    'PT' => 'pt',
    'RO_RO' => 'ro',
    'RO' => 'ro',
    'SK_SK' => 'sk',
    'SK' => 'sk',
    'SL_SI' => 'sl',
    'SL' => 'sl',
    'CS_CZ' => 'cs',
    'CS' => 'cs',
    'HU_HU' => 'hu',
    'HU' => 'hu',
    'TR_TR' => 'tr',
    'TR' => 'tr',
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If no value provided, return default language
    if (empty($value)) {
      return 'en'; // Default to English
    }

    // Clean the value
    $language_code = trim($value);
    
    // Check if we have a direct mapping
    if (isset($this->languageMapping[$language_code])) {
      $mapped_code = $this->languageMapping[$language_code];
      \Drupal::logger('cms_migrations')->debug('Mapped language @original to @mapped', [
        '@original' => $language_code,
        '@mapped' => $mapped_code,
      ]);
      return $mapped_code;
    }

    // Try to find a partial match (e.g., "EN_US" -> "EN")
    $base_language = substr($language_code, 0, 2);
    if (isset($this->languageMapping[$base_language])) {
      $mapped_code = $this->languageMapping[$base_language];
      \Drupal::logger('cms_migrations')->debug('Mapped language @original to @mapped (partial match)', [
        '@original' => $language_code,
        '@mapped' => $mapped_code,
      ]);
      return $mapped_code;
    }

    // If no mapping found, log warning and return default
    \Drupal::logger('cms_migrations')->warning('No language mapping found for @language, using default', [
      '@language' => $language_code,
    ]);
    
    return 'en'; // Default to English
  }

}
