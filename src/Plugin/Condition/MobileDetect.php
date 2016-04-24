<?php

/**
 * @file
 * Contains \Drupal\context_mobile_detect\Plugin\Condition\MobileDetect.
 */
define('MOBILE_DETECT_LIBRARY_PATH', '/sites/all/libraries/Mobile_Detect');

namespace Drupal\context_mobile_detect\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Provides a 'Mobile detect' condition to enable a condition based in module selected status.
 *
 * @Condition(
 *   id = "mobile_detect",
 *   label = @Translation("Mobile detect"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE , label = @Translation("node"))
 *   }
 * )
 *
 */
class MobileDetect extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition
    );
  }

  /**
   * Creates a new ExampleCondition instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = array('mobile' => 'Mobile', 'tablet' => 'Tablet', 'desktop' => 'Desktop');

    $form['mobile_detect'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Device Type'),
      '#default_value' => $this->configuration['mobile_detect'],
      '#options' => $options,
    );

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['mobile_detect'] = $form_state->getValue('mobile_detect');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('mobile_detect' => '') + parent::defaultConfiguration();
  }

  /**
   * Evaluates the condition and returns TRUE or FALSE accordingly.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate() {
    dsm($this->configuration['mobile_detect']);
    if (empty($this->configuration['mobile_detect']) && !$this->isNegated()) {
      return TRUE;
    }

    return $this->_context_mobile_detect_detect();
  }

  /**
   * Helper function for device types retrieving
   */
  public function _context_mobile_detect_devices_types() {
    $type = array();
    $data = array('iPhone', 'BlackBerry', 'HTC', 'Nexus', 'DellStreak', 'Motorola', 'Samsung', 'Sony', 'Asus', 'Palm', 'GenericPhone');
    foreach ($data as $type) {
      $types[strtolower($type)] = $type;
    }
    return $types;
  }

  public function _context_mobile_detect_detect($setcookie = FALSE) {
    $data = array(
      'device' => 3, // Set default value to Desktop
      'device_type' => 0, // Set default value to empty
    );

    if (!isset($_COOKIE['device']) || !is_numeric($_COOKIE['device']) || $_COOKIE['device'] == 0) {
      require_once MOBILE_DETECT_LIBRARY_PATH . '/Mobile_Detect.php';
      $detect = new Mobile_Detect();
      $data['device'] = ($detect->isMobile() ? ($detect->isTablet() ? 2 : 1) : 3);

      $types = _context_mobile_detect_devices_types();
      // Suppose that only one or none of functions will response TRUE
      // TODO: investigate it later and use some sort of sanitize to store data in COOKIEs
      foreach ($types as $key => $type) {
        $data['device_type'] = ($detect->{'is' . $type}()) ? $key : 0;
        if ($data['device_type']) {
          break;
        }
      }
      if ($setcookie) {
        $params = session_get_cookie_params();
        setcookie("device", $data['device'], REQUEST_TIME + 7200, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        setcookie("device_type", $data['device_type'], REQUEST_TIME + 7200, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
      }
    }
    else {
      $data['device'] = $_COOKIE['device'];
      $data['device_type'] = isset($_COOKIE['device_type']) ? $_COOKIE['device_type'] : $data['device_type'];
    }

    return $data;
  }

  /**
   * Provides a human readable summary of the condition's configuration.
   */
  public function summary() {
    $module = $this->getContextValue('mobile_detect');
    $modules = system_rebuild_module_data();

    $status = ($modules[$module]->status) ? t('enabled') : t('disabled');

    return t('The module @module is @status.', array('@module' => $module, '@status' => $status));
  }

}
