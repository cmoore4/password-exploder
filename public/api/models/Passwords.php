<?php

class Passwords extends \Phalcon\Mvc\Model{

    // We must declare these fields explicitly in order to use them on the beforeValidate field
    public $emails;
    public $ip_restrictions;

    public function getSource(){
        return "passwords";
    }

    public function getSequenceName()
    {
        return "pwx_global_seq";
    }

    public function initialize(){

    }


    /**
     * Emails come via JSON as arrays.  They are stored in the DB as a single comma-separated field.
     * I could have used a separate table, but it seemed needlessly complicated for this simple use case.
    **/
    public function getEmails(){
        if(is_array($this->emails)){
            return $this->emails;
        }
        else if($this->emails){
            return $this->emails = explode(',', $this->emails);
        }
        return array();
    }

    public function setEmails($emails){
        if(is_array($emails)){
            $this->emails = implode(',', $emails);
        } else if(strlen($emails)){
            $this->emails = $emails;
        } else {
            $this->emails = null;
        }
    }

    /**
     * This is use to set up a Password model from the JSON sent by the app.
     * It uses the RawValue fields where appropriate, and sets the id to a UUID.
    **/
    public static function createFromRequestBody($req){
        $password = new Passwords();

        $password->id = Passwords::generateUUID();
        $password->password = $req->password;
        $password->count = new \Phalcon\Db\RawValue('default');
        $password->created = new \Phalcon\Db\RawValue('default');
        $password->viewcount = new \Phalcon\Db\RawValue('default');
        $password->username = $req->user;
        $password->note = $req->note;
        $password->expiration = $req->expireDate;
        $password->maxviews = $req->viewLimit;
        $password->ip_restrictions = $req->ipRestrictions;
        $password->account_id = $req->accountId;
        $password->lock_to_account = $req->useAcctPassword;
        $password->setEmails($req->notifications);

        return $password;
    }

    public function validation(){

        $now = new DateTime();
        if( $this->expiration < $now->getTimestamp() ){

            $message = new \Phalcon\Mvc\Model\Message(
                "Password expiration is in the past.  Please set it to a time in the future.",
                "expiration",
                "InvalidExpirationDate"
            );
            $this->appendMessage($message);
            return false;
        }

        return true;
    }

    public function beforeSave(){
        // TODO: Move to setter
        // Convert arrays to comma separated lists
        // We could use a separate table, but its needlessly complicated for our use case

        if(is_array($this->ip_restrictions)){
            $this->ip_restrictions = implode(',', $this->ip_restrictions);
        }
    }

    /**
     * Generates a single UUID from Postgres
    **/
    public static function generateUUID(){
        $idSql = "SELECT uuid_generate_v4()";
        $id = \Phalcon\DI::getDefault()->getDb()->fetchOne($idSql);
        $id = $id[0];

        return $id;
    }

    public function afterCreate(){

        // After a record is created, we send an email to the specified recipients
        error_log(print_r($this->getEmails(), true));
        if(count($this->getEmails())){
            $now = new DateTime();
            $now = $now->getTimestamp();
            $minutes = round(abs($this->expiration - $now) / 60);


            $message = "<h1>You've been sent an exploding password!</h1>";
            $message .= "<p>The password will expire $minutes minutes after this message is sent.</p>";
            $message .= '<p>To retrieve your password, <a href="' .
                        $this->getDI()->getConfig()->site->baseurl .
                        'passwords/' . $this->id . '">click here.</a></p>';

            $mail = Swift_Message::newInstance('You\'ve been sent an expiring password')
                ->setFrom(array($this->getDI()->getConfig()->smtp->user => 'Password Exploder'))
                ->setTo($this->getEmails())
                ->setBody($message, 'text/html');
            $mailer = Swift_Mailer::newInstance($this->getDI()->getSmtp());
            $mailer->send($mail);
        }

        // Record this entry in the logging table
        $log = new Logs();
        $log->id = $this->count;
        $log->has_username = (strlen($this->username)) ? true : false;
        $log->has_note = (strlen($this->note)) ? true : false;
        $log->has_maxviews = ($this->maxviews) ? true : false;
        $log->has_ip_restrictions = false;
        $log->has_account = false;
        $log->notification_count = count($this->getEmails());
        $log->total_viewcount = 1;
        $log->save();

    }

    public function afterFetch(){
        // TODO: move this to getters/setters

        //Convert the strings to arrays


        if($this->ip_restrictions){
            $this->ip_restrictions = explode(',', $this->ip_restrictions);
        }

    }

    public function displayFields(){
        $return = $this->toArray();
        unset($return['emails'], $return['ip_restrictions'], $return['count']);

        return $return;
    }



    /*
     * ip_in_range.php - Function to determine if an IP is located in a
     *                   specific range as specified via several alternative
     *                   formats.
     *
     * Network ranges can be specified as:
     * 1. Wildcard format:     1.2.3.*
     * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
     * 3. Start-End IP format: 1.2.3.0-1.2.3.255
     *
     * Return value BOOLEAN : ip_in_range($ip, $range);
     *
     * Copyright 2008: Paul Gregg <pgregg@pgregg.com>
     * 10 January 2008
     * Version: 1.2
     *
     * Source website: http://www.pgregg.com/projects/php/ip_in_range/
     * Version 1.2
     *
     * This software is Donationware - if you feel you have benefited from
     * the use of this tool then please consider a donation. The value of
     * which is entirely left up to your discretion.
     * http://www.pgregg.com/donate/
     *
     * Please do not remove this header, or source attibution from this file.
     */
    private function decbin32 ($dec) {
        return str_pad(decbin($dec), 32, '0', STR_PAD_LEFT);
    }

    // ip_in_range
    // This function takes 2 arguments, an IP address and a "range" in several
    // different formats.
    // Network ranges can be specified as:
    // 1. Wildcard format:     1.2.3.*
    // 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
    // 3. Start-End IP format: 1.2.3.0-1.2.3.255
    // The function will return true if the supplied IP is within the range.
    // Note little validation is done on the range inputs - it expects you to
    // use one of the above 3 formats.
    private function ip_in_range($ip, $range) {
      if (strpos($range, '/') !== false) {
        // $range is in IP/NETMASK format
        list($range, $netmask) = explode('/', $range, 2);
        if (strpos($netmask, '.') !== false) {
          // $netmask is a 255.255.0.0 format
          $netmask = str_replace('*', '0', $netmask);
          $netmask_dec = ip2long($netmask);
          return ( (ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec) );
        } else {
          // $netmask is a CIDR size block
          // fix the range argument
          $x = explode('.', $range);
          while(count($x)<4) $x[] = '0';
          list($a,$b,$c,$d) = $x;
          $range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b,empty($c)?'0':$c,empty($d)?'0':$d);
          $range_dec = ip2long($range);
          $ip_dec = ip2long($ip);

          # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
          #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

          # Strategy 2 - Use math to create it
          $wildcard_dec = pow(2, (32-$netmask)) - 1;
          $netmask_dec = ~ $wildcard_dec;

          return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
        }
      } else {
        // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
        if (strpos($range, '*') !==false) { // a.b.*.* format
          // Just convert to A-B format by setting * to 0 for A and 255 for B
          $lower = str_replace('*', '0', $range);
          $upper = str_replace('*', '255', $range);
          $range = "$lower-$upper";
        }

        if (strpos($range, '-')!==false) { // A-B format
          list($lower, $upper) = explode('-', $range, 2);
          $lower_dec = (float)sprintf("%u",ip2long($lower));
          $upper_dec = (float)sprintf("%u",ip2long($upper));
          $ip_dec = (float)sprintf("%u",ip2long($ip));
          return ( ($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec) );
        }


        if($ip == $range){ // Single ip address
            return true;
        }

        // TODO: Validate
        //echo 'Range argument is not in 1.2.3.4/24 or 1.2.3.4/255.255.255.0 format';
        return false;
      }

    }

    public function isAllowedIP($ip){

        if(strlen($this->ip_restrictions)){
            return $this->ip_in_range($ip, $this->ip_restrictions);

        } else if (is_array($this->ip_restrictions) && count($this->ip_restrictions)){
            $allowed = false;

            foreach($this->ip_restrictions as $range){
                if($this->ip_in_range($ip, $range)){
                    $allowed = true;
                    break;
                }
            }
            return $allowed;

        } else {
            return true;
        }


    }
}
