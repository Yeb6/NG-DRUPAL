<?php

namespace Drupal\fs_article_tools\FormAlter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeInterface;

/**
 * Handles conditional form alterations for fs_article nodes.
 */
class NodeFormAlter {

  use StringTranslationTrait;

  /**
   * NodeFormAlter constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The translation service.
   */
  public function __construct(TranslationInterface $stringTranslation) {
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * Alters the node form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID.
   */
  public function alter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $node = $form_state->getFormObject()->getEntity();
  
    if ($node instanceof NodeInterface && $node->bundle() === 'fs_article') {
      $article_type_selector = ':input[name="field_article_type"]';
  
      // Fields for specific article types.
      $fields_for_ews_article = [
        'field_attachments',
        'field_create_section',
        'field_brand_classification',
        'field_supplementary_topics',
      ];
  
      $fields_for_home = [
        'field_home_image',
        'field_release_date',
      ];
  
      // Collect all conditional fields
      $all_conditional_fields = array_unique(array_merge(
        $fields_for_ews_article,
        $fields_for_home,
        [
          'field_fs_id',
          'field_source_id',
          'field_hierarchy',
          'field_last_update',
          'field_project_name',
        ]
      ));
  
      // Set all conditional fields to hidden by default
      foreach ($all_conditional_fields as $field_name) {
        if (isset($form[$field_name])) {
          $form[$field_name]['#states'] = [
            'visible' => [
              // This default rule ensures it's hidden unless overridden below.
              [$article_type_selector => ['value' => '__never_match__']],
            ],
          ];
        }
      }
  
      // Show fields for 'ews_article'
      foreach ($fields_for_ews_article as $field_name) {
        if (isset($form[$field_name])) {
          $form[$field_name]['#states']['visible'] = [
            [$article_type_selector => ['value' => 'ews_article']],
          ];
        }
      }
  
      // Show fields for 'home'
      foreach ($fields_for_home as $field_name) {
        if (isset($form[$field_name])) {
          $form[$field_name]['#states']['visible'] = [
            [$article_type_selector => ['value' => 'home']],
          ];
        }
      }
  
      // Add custom validation if needed
      $form['#validate'][] = [$this, 'validate'];
    }
  }
  
  

  /**
   * Custom validation for fs_article node form.
   */
  public function validate(array &$form, FormStateInterface $form_state): void {
    $article_type = $form_state->getValue(['field_article_type', 0, 'value']);

    if (empty($article_type)) {
      $form_state->setErrorByName('field_article_type', $this->t('Please select at least one @label.', ['@label' => 'Article Type']));
      return;
    }

    if ($article_type === 'ews_article') {
      $this->validateEwsArticleRestrictions($form_state);
    }

    if ($article_type === 'chapter' || $article_type == 'ews_news') {
      $this->validateChapterRestrictions($form_state);
    }
  }

  /**
   * Validates that certain category values appear only once in EWS Article.
   */
  protected function validateEwsArticleRestrictions(FormStateInterface $form_state): void {
    $items = $form_state->getValue('field_create_section') ?? [];

    $restricted_values = [
      'principle',
      'requirement',
      'procedure',
      'guideline_practical_example',
    ];

    $value_counts = [];

    foreach ($items as $item) {
      $value = $item['subform']['field_category_selection'][0]['value'] ?? NULL;

      if (in_array($value, $restricted_values)) {
        $value_counts[$value] = ($value_counts[$value] ?? 0) + 1;
      }
    }

    foreach ($value_counts as $value => $count) {
      if ($count > 1) {
        $form_state->setErrorByName(
          'field_create_section',
          $this->t('The value "@value" can only be used once in Create Section.', ['@value' => $value])
        );
      }
    }
  }

  /**
   * Validates that specific fields are empty when article type is Chapter.
   */
  protected function validateChapterRestrictions(FormStateInterface $form_state): void {
    $fields_to_check = [
      'field_create_section' => 'Create Section',
      'field_brand_classification' => 'Brand Classification',
      'field_supplementary_topics' => 'Supplementary Topic',
      'field_attachments' => 'Attachment',
    ];

    foreach ($fields_to_check as $field_name => $label) {
      $value = $form_state->getValue($field_name);
      switch ($field_name) {
        case 'field_create_section':
          if (
            !empty($value[0]['subform']['field_category_selection']) ||
            !empty($value[0]['subform']['field_content'][0]['value'])
          ) {
            $form_state->setErrorByName($field_name, $this->t('@label must be empty when Article Type is Chapter or eWS News.', ['@label' => $label]));
          }
          break;

        case 'field_attachments':
          if (!empty($value['selection'])) {
            $form_state->setErrorByName($field_name, $this->t('@label must be empty when Article Type is Chapter or eWS News.', ['@label' => $label]));
          }
          break;

        case 'field_supplementary_topics':
          if ($value[0]['target_id'] != NULL) {
            $form_state->setErrorByName($field_name, $this->t('@label must be empty when Article Type is Chapter or eWS News.', ['@label' => $label]));
          }
          break;

        default:
          if (!empty($value)) {
            $form_state->setErrorByName($field_name, $this->t('@label must be empty when Article Type is Chapter or eWS News.', ['@label' => $label]));
          }
          break;
      }
    }
  }

}
