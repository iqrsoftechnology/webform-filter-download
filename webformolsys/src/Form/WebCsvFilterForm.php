<?php
/**
 * @file
 * Contains \Drupal\webformolsys\Form\WebCsvFilterForm.
 */
namespace Drupal\webformolsys\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\EntityQueryInterface;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Query;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\RowCountException;
use Drupal\Core\Database\Query\SelectInterface;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterfac;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

Use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\UrlHelper;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

use Drupal\webformolsys\Controller\WebfrmController;


class WebCsvFilterForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'WebCsvFilterForm_form';
    }
		
		/**
     * {@inheritdoc}
     */
	public function buildForm(array $form, FormStateInterface $form_state)
	{
		
		$path = \Drupal::request()->getpathInfo();
		$arg  = explode('/',$path);
		$webid = isset($_REQUEST['webid']) ? $_REQUEST['webid'] : '';
		$start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : date("Y-m-d", strtotime(' -1 day'));
		$end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : date('Y-m-d');


    $rq_fields = isset($_REQUEST) ? $_REQUEST : '';
    
		$webform_ids = \Drupal::entityQuery('webform')
      ->execute();
    $webform = ['sl' => 'Select'];
    $webform_fields = [];
    if( !empty($webform_ids) ) {
      foreach($webform_ids as $k => $web) {
        if(strpos($web, 'template') === false) {
          $webform[$web] = ucwords(str_replace('_', ' ', $web));
        }
      }
    }
    
    /***********Use Controller*************/
    $contentController = new WebfrmController();
    $headerlist = $contentController->webformfields($webid);
    
    
    $form['#tree'] = TRUE;
    $form['webform_list'] = [
			'#type' => 'select',
			'#title' => t('Select Webform :'),
			'#options' => $webform,
			'#ajax' => [
					'callback' => [$this, 'webformFields'], 
					'event' => 'change',
						'method' => 'html',
						'wrapper' => 'webform-update',
						'progress' => [
							'type' => 'throbber',
							 'message' => NULL,
						],
					],
			'#default_value' => isset($webid) ? $webid : '',
      '#required' => true
		];
     
    if(!empty($rq_fields)) {
      $default_v = [];
      foreach($headerlist as $kh => $vh) {
        if(in_array($kh, $rq_fields)) {
          $default_v[$kh] = $kh;
        }
      }
      $form['webformFields'] = array(
          '#title' => t('Choose Fields :'),
          '#type' => 'select',
          '#options' => $headerlist,
          '#attributes' => ["id" => 'webform-update'],
          '#multiple' => TRUE,
          '#validated' => TRUE,
          '#default_value' => $default_v
      );
      
    } else {
      $form['webformFields'] = array(
          '#title' => t('Choose Fields :'),
          '#type' => 'select',
          '#options' => $webform_fields,
          '#attributes' => ["id" => 'webform-update'],
          '#multiple' => TRUE,
          '#validated' => TRUE
      );
    }
    
    $form['start_date'] = [
      '#type' => 'date',
      '#date_format' => 'Y-m-d',
      '#title' => t('Start Date:'),
      '#default_value' => $start_date,
			'#datepicker_options' => array('maxDate' => 30),
			'#date_year_range' => '-8:+0',
			'#validated' => TRUE
    ];

    $form['end_date'] = [
      '#type' => 'date',
      '#date_format' => 'Y-m-d',
      '#title' => t('End Date:'),
      '#default_value' => $end_date,
			'#datepicker_options' => array('maxDate' => 30),
			'#date_year_range' => '-8:+0',
			'#validated' => TRUE
    ];
      
      
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
			'#attributes' => ["id" => 'save-btn'],
    ];
    
		return $form;
	}

/**
  * {@inheritdoc}
  */
	public function webformFields(array &$form, FormStateInterface $form_state) {
		
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
        if(!empty($elements[$f_k]['#title']) && strlen($elements[$f_k]['#title']) > 2) {
          $renderedField .= "<option value='".$f_k."' >".$elements[$f_k]['#title']."</option>";
        }
      }
    }else {
        $renderedField['no'] = 'No';
    }
    
    //
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#webform-update', $renderedField));
    return $response;
  } 
  
  /**
   * Validate the title and the checkbox of the form
   * 
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * 
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $formid = $form_state->getValue('webform_list');
    
    $start_date = $form_state->getValue('start_date');
		$end_date = $form_state->getValue('end_date');
 
    if ($start_date != '' && $end_date == '' || $end_date != '' && $start_date == '' || $end_date == '' && $start_date == ''){
      // Set an error for the form element with a key of "accept".
      $form_state->setErrorByName('start_date', $this->t('Please Check Start / End Date.'));
    } 
    
    if ($formid == 'sl'){
      // Set an error for the form element with a key of "accept".
      $form_state->setErrorByName('webform_list', $this->t('Please select Webform'));
    }

  }
/**
 * {@inheritdoc}
*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$webid = $form_state->getValue('webform_list');
		$webformFields = $form_state->getValue('webformFields');
		$start_date = $form_state->getValue('start_date');
		$end_date = $form_state->getValue('end_date');
    
			$path = '/web-filter-data-list';
      $path_param = [
					'webid' => $webid,
					'start_date' => $start_date,
					'end_date' => $end_date,
					'submissionTime' => 'submissionTime',
			];
      foreach ($webformFields as $key => $value) {
        if($value != ''){
          $path_param[$key] = $value;
        }
      }
      
			$url = Url::fromUserInput($path, ['query' => $path_param]);
			$form_state->setRedirectUrl($url);
			return;
	}
	
}
?>