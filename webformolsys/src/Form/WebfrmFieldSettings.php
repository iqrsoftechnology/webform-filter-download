<?php
/**
 * @file
 * Contains \Drupal\webformolsys\Form\WebfrmFieldSettings
 */

namespace Drupal\webformolsys\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\Core\Database\Query;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\RowCountException;
use Drupal\Core\Database\Query\SelectInterface;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;


use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

use Drupal\webformolsys\Controller\WebfrmController;


/**
 * Configure example settings for this site.
 */
class WebfrmFieldSettings extends ConfigFormBase {

	/** 
	* Config settings.
	*
	* @var string
	*/
	const SETTINGS = 'WebsetField.settings';

	/** 
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'WebfrmFieldSettings_settings';
	}

	/** 
	* {@inheritdoc}
	*/
	protected function getEditableConfigNames() {
		return [
			static::SETTINGS,
		];
	}

	/** 
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
    //
		$webform_ids = \Drupal::entityQuery('webform')
      ->execute();
    $webform = ['sl' => 'Select'];
    $webform_fields = [];
   
    if( !empty($webform_ids) ) 
    {
      foreach($webform_ids as $k => $web) {
        if(strpos($web, 'template') === false) {
          $webform[$web] = ucwords(str_replace('_', ' ', $web));
        }
      }
    }
    
    // $entity = \Drupal::entityTypeManager()->getStorage('webform')->load('order_free_quran');
    // $form = $entity->getSubmissionForm();
    // $elements = $form['elements'];
   // print '<pre>' . print_r($elements, true) .'</pre>';
     
    $form['webform_list'] = [
			'#type' => 'select',
			'#title' => t('Select Webform :'),
			'#options' => $webform,
			'#ajax' => [
					'callback' => [$this, 'webformsettingfields'], 
					'event' => 'change',
						'method' => 'html',
						'wrapper' => 'webform-settings-field',
						'progress' => [
							'type' => 'throbber',
							 'message' => NULL,
						],
					],
			//'#default_value' => isset($webid) ? $webid : $sl,
      
		];

    $form['webform_set_Fields'] = array(
      '#title' => t('Choose Fields : (Ctrl+Click)'),
      '#type' => 'select',
      '#options' => $webform_fields,
      '#attributes' => ["id" => 'webform-settings-field'],
      '#multiple' => TRUE,
      '#validated' => TRUE
    );

    $form['form_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Slected Form Fields For Ordering'),
      '#required' => TRUE
    ];
		return parent::buildForm($form, $form_state);
	}

/** 
  * {@inheritdoc}
  */
	public function webformsettingfields(array &$form, FormStateInterface $form_state) {
		
		$mc_class_pr = '';
		$mn_class_name = '';
		$name_field = $form_state->getValue('webform_list');
   
    /***********Use Controller*************/
    $contentController = new WebfrmController();
    $headerlist = $contentController->webformfields($name_field);
    /**********************************/

		$entity = \Drupal::entityTypeManager()->getStorage('webform')->load($name_field);
    $form = $entity->getSubmissionForm();
    $elements = $form['elements'];
    
    $renderedField = '';
    if (!empty($elements)) 
    {
      foreach($elements as $f_k => $f_v)
      {
        if(!empty($headerlist[$f_k])) {
          $renderedField .= "<option value='".$f_k."' >".ucwords(str_replace('_', ' ', $headerlist[$f_k]))."</option>";
        }
      }
    }else {
        $renderedField['no'] = 'No';
    }
    
    //
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#webform-settings-field', $renderedField));
    return $response;
  } 
  
  
	/** 
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		// Retrieve the configuration.
		// Set the submitted configuration setting.
    $name_field = $form_state->getValue('webform_list');
    
		$this->configFactory->getEditable(static::SETTINGS)
		->set('webform_list_'.$name_field, $form_state->getValue('webform_list'))
		->set('form_fields_'.$name_field, $form_state->getValue('form_fields'))
    ->save();

		parent::submitForm($form, $form_state);
	}
}