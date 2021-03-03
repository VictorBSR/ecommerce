<?php

namespace Hcode;

use Rain\Tpl;

class Mailer {

    const USERNAME = "cursophp7hcode@gmail.com";
    const PASSWORD = "SENHA";
    const NAME_FROM = "Hcode Store";

    private $mail;

    public function __construct($toAddress, $toName, $subject, $tplName, $data = array()) {

        $config = array(
		    "base_url"      => null,
            "tpl_dir"       => $_SERVER['DOCUMENT_ROOT']."/views/email/",
		    "cache_dir"     => $_SERVER['DOCUMENT_ROOT']."/views-cache/",
		    "debug"         => false
        );

        Tpl::configure( $config );

        // create the Tpl object
        $tpl = new Tpl;

        foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
        }

        $html = $tpl->draw($tplName, true);

        $this->mail = new \PHPMailer;

        $this->mail->isSMTP();

        $this->mail->SMTPDebug = 0;
        $this->mail->Debugoutput = 'html';
        //$mail->Host='smtp.gmail.com';
        //$mail->Host = "smtp.live.com";
        $this->mail->Host = "smtp.office365.com";
        $this->mail->Port=587;
        $this->mail->SMTPSecure = 'tls';
        //$mail->SMTPSecure = 'ssl';
        $this->mail->SMTPAuth=true;
        //$mail->SMTPAuth=false;
        $this->mail->Username= Mailer::USERNAME;
        $this->mail->Password=Mailer::PASSWORD; 
        $this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);
        //$mail->addReplyTo('replyto@examplo.com','First Last');
        $this->mail->addAddress($toAddress, $toName);
        $this->mail->Subject = $subject;
        $this->mail->msgHTML($html);
        $this->mail->AltBody='Mensagem de corpo de email';
        //$mail->addAttachment('images/phpmailer_mini.png');

    }

    public function send() {

        return $this->mail->send();
    }
}


?>