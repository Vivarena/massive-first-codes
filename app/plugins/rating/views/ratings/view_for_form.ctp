<?php
/**
 * View for the AJAX star rating plugin.
 *
 * @author Michael Schneidt <michael.schneidt@arcor.de>
 * @copyright Copyright 2009, Michael Schneidt
 * @license http://www.opensource.org/licenses/mit-license.php
 * @link http://bakery.cakephp.org/articles/view/ajax-star-rating-plugin-1
 * @version 2.4
 */
?>
 
<?php
  // decision to enable or disable the rating
  $enable = ($session->check(Configure::read('Rating.sessionUserId')) // logged in user or guest
               || (Configure::read('Rating.guest') && $session->check('Rating.guest_id')))
             && !Configure::read('Rating.disable') // plugin is enabled
             && (Configure::read('Rating.allowChange') // changing is allowed or it's the first rating
                 || (!Configure::read('Rating.allowChange') && $data['%RATING%'] == 0));

  // the images are displayed here before js initialization to avoid flickering.

  echo $rating->stars($model, $id, $data, $options, $enable);
  
  // format the statusText and write it back
  $text = $rating->format(Configure::read('Rating.statusText'), $data);
  Configure::write('Rating.statusText', $text);
?>

<div style="display : none" id="<?php echo $model.'_rating_'.$options['name'].'_'.$id.'_text'; ?>" class="<?php echo !empty($text) ? 'rating-text' : 'rating-notext'; ?>">
  <?php
    echo $text;
  ?>
</div>

<?php
  // initialize the rating element
  if (!Configure::read('Rating.disable')) {
    echo $javascript->codeBlock("ratingInitForForm('".$model.'_rating_'.$options['name'].'_'.$id."', "
                                           ."'".addslashes(json_encode($data))."',"
                                           ."'".addslashes(json_encode(Configure::read('Rating')))."',"
                                           ."'".addslashes(json_encode($options))."',"
                                           .intval($enable).");");
  }
?>
<div style="display : none">
<?php
echo $form->radio('value',
    $rating->options(),
    array('legend' => false,
        'div'=>false,
        'label'=>false,
        'value'=>false,
        'name' => 'data['.$model.']'.'[rating][value]',
        'id' => $model.'_rating_'.$options['name'].'_'.$id,
        ));

echo $form->input('name',
       array(
        'legend' => false,
        'div'=>false,
        'label'=>false,
        'value'=> $name,
        'name' => 'data['.$model.']'.'[rating][name]'
    ));

if (!empty($options['rating_id'])){
echo $form->input('rating_id',
    array(
        'type'=>'hidden',
        'div'=>false,
        'label'=>false,
        'value'=> (int)$options['rating_id'],
        'name' => 'data['.$model.']'.'[rating][id]'
    ));
}
?>
</div>
<?php
  // show flash message
  if (Configure::read('Rating.flash')) {
    $session->flash('rating');
  }

?>