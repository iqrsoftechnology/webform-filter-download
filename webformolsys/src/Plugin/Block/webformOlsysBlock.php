<?php

namespace Drupal\webformOlsys\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Webform Olsys' Block.
 *
 * @Block(
 *   id = "webformolsys_block",
 *   admin_label = @Translation("Webform Olsys block"),
 *   category = @Translation("Webform Olsys Use"),
 * )
 */
class webformOlsysBlock extends BlockBase {
  
   /**
    * {@inheritdoc}
   */
   public function build() {
      
     $form = \Drupal::formBuilder()->getForm('Drupal\webformolsys\Form\WebCsvFilterForm');

     return $form;
   }

}