<?php
namespace Drupal\webformolsys\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
Use Drupal\taxonomy\Entity\Term;
Use Drupal\file\Entity\File;

use Drupal\user\Entity\User;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\RowCountException;
use Drupal\Component\Utility\Html ;
use Drupal\Core\Database\Query\TableSortExtender;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\Core\Database\Database;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\field\Entity\FieldConfig;

class WebfrmController extends ControllerBase
{
  public function webDataList() {
    $webfrm_id = \Drupal::request()->query->get('webid');
    
    $rq_fields = isset($_REQUEST) ? $_REQUEST : '';
       
    $parent_fields = \Drupal::request()->query->all();

    $start_date = isset($parent_fields['start_date']) ? $parent_fields['start_date'] : date("Y-m-d", strtotime(' -1 day'));
    $end_date = isset($parent_fields['end_date']) ? $parent_fields['end_date'] : date('Y-m-d');
    
    $webform = \Drupal\webform\Entity\Webform::load($webfrm_id);
    $renderedField = '';
    $output = [] ;
    if ($webform->hasSubmissions()) 
    {
      $query = \Drupal::entityQuery('webform_submission');
     if($start_date != '' && $end_date != '') {
        $query->condition('created', strtotime($start_date.' 00:00:09'), '>='); //UNIX_TIMESTAMP('$start_date 23:59:59')
        $query->condition('created', strtotime($end_date.' 23:59:59'), '<='); //
      }
      $query->condition('webform_id', $webfrm_id);
      $result = $query->execute();
      
      $submission_data = [];
      
      foreach ($result as $item)
      {
        // $sb_time = \Drupal::database(); 
        // $sb_qry= $sb_time->select('webform_submission', 'n'); 
        // $sb_qry->fields('n', array('created'));
        // $sb_qry->condition('n.sid', $item);
        // $sb_time = $sb_qry->execute()->fetchField();
        // $sub_time = date('d-m-Y', $sb_time);
        
        $submission = \Drupal\webform\Entity\WebformSubmission::load($item); // SiD
        
       // $submission_data[] = array_merge($submission->getData() , array('submissionTime' => $sub_time));
       
        $submission_data[$item] = array_merge($submission->getData(), array('view' => ''));
      }
      
      $field_order = $this->webformfields($webfrm_id);
      $headers[] = array('data' => $this->t('S.No.'));
      
      foreach ($field_order as $k => $headerlbl)
      {
        if( in_array($k, $rq_fields) ) {
          $headers[] = array('data' => $this->t($headerlbl));
        }
      }
      //$headers[] = array('data' => $this->t('Submission Time'));
      $headers[] = array('data' => $this->t('View'));
    
      $data_list = array();
      $output = [];
      $i = 1;
      
      //$field_order['submissionTime'] = 'Submission';
      $field_order['view'] = 'View';
      $rq_fields['view'] = 'view';
      
      foreach ($submission_data as $key => $row)
      { 
        $data_row = array();
        $data_row[] = $i;
        
        //if($start_date != '' && strtotime('d-m-Y', $start_date) >= $row['submissionTime']) {
        if($start_date != '' && $end_date != '') {
          foreach ($field_order as $ky => $data_v) 
          {
            if( in_array($ky, $rq_fields) ) {
              if($ky == 'view') {
                  $row['view'] = '<a href="/admin/structure/webform/manage/order_free_quran/submission/'.$key .'" >View</a>';
              }
              if(is_array($row[$ky])) {
                  $row[$ky] = $row[$ky][0];
              }
              $data_row[] =  $this->t($row[$ky]);
            }
          }
        } else {
          foreach ($field_order as $ky => $data_v) 
          {
            if( in_array($ky, $rq_fields) ) {
              $row['view'] = $view;
              if(is_array($row[$ky])) {
                  $row[$ky] = $row[$ky][0];
              }
              $data_row[] =  $this->t($row[$ky]);
            }
          }
        }
        $output[] = $data_row;
        $i++;
      }
    }
       
    $total = count($output);
    
    $lk = 0;
    $link = '';
    if(!empty($parent_fields)) {
      foreach ($parent_fields as $key => $value) {
        if($value != '') {
          if($lk == 0) {
            $link .= $key . '=' . $value;
          } else {
            $link .= '&' . $key . '=' . $value;
          }
        }
        $lk++;
      }
    
      $csvdown = '<a href="/download-web-csv-data?'.$link.'" class="btn btn-danger" style="float: right;margin-top: 6px;margin-right: 8px;"> <i class="fa fa-download" aria-hidden="true"> Download CSV</i> </a>';
       
      $build['detials'] = array(
        '#markup' => '<h3>'.ucwords(str_replace('_', ' ', $webfrm_id)). ' Form Data List ('.$total.') </h3> <span class="csvdown" style="float:right;">'.$csvdown.'</span>'
      );
    }

		$build['table'] = array(
			'#type' => 'table',
			'#header' => $headers,
			'#rows' => $output,
			'#empty' => t('No records found'),
			'#attributes' => array(
				'id' => 'webform-list-all',
				'class' => array(
					'webform'
				),
			)
		);
    return $build;
  }

    
  public function webformfields($form_id) 
  {
    if($form_id != '') {
      $entity = \Drupal::entityTypeManager()->getStorage('webform')->load($form_id);
      $form = $entity->getSubmissionForm();
      $elements = $form['elements'];
      
      $field_s = [];
      if(!empty($elements)) 
      {
        foreach ($elements as $k => $field) {
          if(!empty($elements[$k]['#title']) && strlen($elements[$k]['#title']) > 2) {
            $field_s[$k] = $elements[$k]['#title'];
          }
        }
      }
    }
    return $field_s;
  }
 
  /**
	* Webform data download.
	*/
	public function webCsvDown() {
		 
		$path = \Drupal::request()->getpathInfo();
		$arg  = explode('/',$path);
    
    $parent_fields = \Drupal::request()->query->all();
    
    $webfrm_id = isset($parent_fields['webid']) ? $parent_fields['webid'] : 'order_free_quran';
    $field_order = $this->webformfields($webfrm_id);
    
    $headers[] = 'S.No.';
    foreach ($field_order as $k => $headerlbl)
    {
      if( in_array($k, $parent_fields) ) {
        $headers[] = $headerlbl;
      }
    }
    //$headers[] = 'Submission Time';
    
    $webform = \Drupal\webform\Entity\Webform::load($webfrm_id);
    $output = [] ;
   if ($webform->hasSubmissions()) 
    {
      $query = \Drupal::entityQuery('webform_submission')
        ->condition('webform_id', $webfrm_id);
      $result = $query->execute();
      
      $submission_data = [];
      
      foreach ($result as $item)
      {
        $sb_time = \Drupal::database(); 
        $sb_qry= $sb_time->select('webform_submission', 'n'); 
        $sb_qry->fields('n', array('created'));
        $sb_qry->condition('n.sid', $item);
        $sb_time = $sb_qry->execute()->fetchField();
        $sub_time = date('d-m-Y', $sb_time);
        
        $submission = \Drupal\webform\Entity\WebformSubmission::load($item); // SiD

        $submission_data[] = array_merge($submission->getData() , array('submissionTime' => $sub_time));
      }
      
     
      $data_list = array();
      $final_rs = [];
      $i = 1;
      
     // $field_order['submissionTime'] = 'Submission';
      foreach ($submission_data as $key => $row)
      { 
        $data_row = array();
        $data_row[] = $i;
        foreach ($field_order as $ky => $data_v) {
          if( in_array($ky, $parent_fields) ) {
            if(is_array($row[$ky])) {
              $row[$ky] = $row[$ky][0];
            }
            $data_row[] =  $this->t($row[$ky]);
          }
        }
        $final_rs[] = $data_row;
        $i++;
      }
    }
    
    $studentData = [];
    $delimiter = ",";
    $filename = ucwords(str_replace('_', '-', $webfrm_id)).'-csv-'.date('d-m-Y').'.csv';

    $fopen = fopen('php://memory', 'w');
    
		fputcsv($fopen, $headers, $delimiter);
		
		$i = count($final_rs) - 1;
		if ( count($final_rs) > 0 )
		{
			foreach ( $final_rs as $ks => $data )
			{
        $NewData = [];         
        if( count($data) ) 
        {
          $di = 0;
          foreach ($data as $vl) {
            $NewData[] = str_replace(',', '-', trim($data[$di]));
            $di++;
          }
        }

				fputcsv($fopen, $NewData, $delimiter);
				$i--;
			}
		}
    
    
		fseek($fopen, 0);
    
		//set headers to download file rather than displayed
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '";');
    
		//records all file pointer
		fpassthru($fopen);
		exit;
    
	}
  
}
?>