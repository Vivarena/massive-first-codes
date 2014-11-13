<?php
/**
 * User: Vitaliy Kh.
 * Date: Feb 11, 2010
 * Time: 3:39:25 PM
 * To change this template use File | Settings | File Templates.
 */

class CouponsProduct extends AppModel
{
    public $name        = "CouponsProduct";
    public $belongsTo   = array("Product");

    public $actsAs      = array("Containable");
}
