<?php
interface PaymentsPlugin_IRequest
{
    public function doPost($data);
    public function setUrl($url);
    public function getError();
}