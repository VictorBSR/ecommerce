<?php

namespace Hcode;
use Rain\Tpl;
use \Hcode\Page;

class PageAdmin extends Page {

    public function __construct($opts = array(), $tpl_dir = "/views/admin/")
    {
        parent::__construct($opts, $tpl_dir);
    }
}



?>