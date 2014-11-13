<?php
/**
* Created by Slava Basko
* Email: basko.slava@gmail.com
* Date: 4/24/13
* Time: 3:09 PM
*/

$start_time = microtime(TRUE);

$arr = array();
//$arr = new ArrayObject(array());

for($i = 0; $i < 100000; $i++) {
//    $arr->append('string_'.$i);
    $arr[] = 'string_'.$i;
}

//echo $arr->count();
//$arr->asort();
echo count($arr);
asort($arr);

$end_time = microtime(TRUE);

echo $end_time - $start_time;