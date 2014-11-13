<?php
/**
 * Element for the AJAX star rating plugin.
 *
 * @author Michael Schneidt <michael.schneidt@arcor.de>
 * @copyright Copyright 2009, Michael Schneidt
 * @license http://www.opensource.org/licenses/mit-license.php
 * @link http://bakery.cakephp.org/articles/view/ajax-star-rating-plugin-1
 * @version 2.4
 */
?>

<?php
  // default name
  if (empty($name)) {
    $name = 'default';
  }
  
  // default config
  if (empty($config)) {
    $config = 'plugin_rating';
  }
    // default config
    if (empty($rating_init)) {
        $rating_init = 0;
    }
// default config
if (empty($rating_id)) {
    $rating_id = null;
}
?>

<div id="<?php echo $model.'_rating_'.$name.'_'.$id; ?>" class="rating">
  <?php
    echo $this->requestAction('rating/ratings/view_for_form/'.$model.'/'.$id.'/'.base64_encode(json_encode(array('name' => $name, 'config' => $config,'rating_init' => $rating_init,'rating_id' => $rating_id))), array('return'));
  ?>
</div>