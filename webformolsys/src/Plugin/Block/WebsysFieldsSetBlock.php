<?php
namespace Drupal\openlogicwb\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\node\Entity\Node;


use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'Web Setting Fields Setting list ' Block.
 *
 * @Block(
 *   id = "websetting_info_panel",
 *   admin_label = @Translation("Web setting Fields Setting list Block"),
 *   category = @Translation("Web setting Fields Setting list"),
 * )
 */
 
class WebsettimgSetBlock extends BlockBase {
		/**
   * {@inheritdoc}
   */
  public function build() {
    return [
			'#type' => 'markup',
      '#markup' => $this->webset_fields_list_block(),
			'#cache' => [
            'max-age' => 0,
          ]
    ];
  }
	
	public function webset_fields_list_block() {
		$account = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $config = \Drupal::config('WebsetField.settings');
    
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
    
    $header = array(
      array(
				'data' => $this->t('S.No.')
			),
      array(
				'data' => $this->t('Form Name')
			),
      array(
				'data' => $this->t('Fields')
			),
      array(
				'data' => $this->t('Update')
			)
    );
    $output = [];
    $i = 1;
    if(!empty($webform)) {
      foreach ($webform as $k => $fv) {
        if($fv != 'Select') {
          $fields = '';
          $update = '-';
          if(!empty($config->get('form_fields_'.$k))) {
            $fields = $this->t($config->get('form_fields_'.$k));
          }
          $output[] = array($i, $fv, $fields, $update);
          $i++;
        }
      }
    }
    $build['view_dt'] = array(
      '#markup' => $this->t('<h2>Setting List</h2>')
    );
		$build['table'] = array(
			'#type' => 'table',
			'#header' => $header,
			'#rows' => $output,
			'#empty' => t('No records found'),
			'#attributes' => array(
				'id' => 'webform-list-all',
				'class' => array(
					'webform'
				),
			)
		);
		//
    return render($build);
	}
}
?>