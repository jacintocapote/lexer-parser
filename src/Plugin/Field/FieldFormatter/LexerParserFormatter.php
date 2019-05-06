<?php

namespace Drupal\lexer_parser\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\lexer_parser\LexerParserService;

/**
 * Plugin implementation of the 'lexer_parser' formatter.
 *
 * @FieldFormatter(
 *   id = "lexer_parser",
 *   label = @Translation("Lexer Parser"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class LexerParserFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The lexer parser service.
   *
   * @var \Drupal\Lexer_parser\lexerParser
   */
  protected $lexerParser;

  /**
   * Construct a LexerParserFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Lexer_parser\LexerParserService $lexerParserManager
   *   The entity manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LexerParserService $lexerParserManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->lexerParser = $lexerParserManager;
  }

  /**
   * Add infor for dependency Injection.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('lexer_parser.lexerparser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays string after process with Lexer & Parser.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = [
        '#theme' => 'lexer_parser_formatter',
        '#attached' => ['library' => ['lexer_parser/lexer-parser']],
        '#original' => $item->value,
        '#output' => $this->lexerParser->parserString($item->value),
        '#tree' => $this->lexerParser->parserTree($item->value),
      ];
    }

    return $element;
  }

}
