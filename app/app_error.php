<?php
class AppError extends ErrorHandler {


    public function cannotFoundFile($params) {

        $this->controller->set('msg', $params['msg']);

        $this->_outputMessage('cannot_found_file');
    }


}
