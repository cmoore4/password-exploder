<?php
/**
 * Maintains some anonymous usage stats.  Contains no information about the passwords themselves.
 *
**/
class Logs extends \Phalcon\Mvc\Model{

	 public $id;
	 public $total_viewcount;

	 public function getSource(){
        return "logs";
    }

}
