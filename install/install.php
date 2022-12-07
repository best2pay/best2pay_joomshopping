<?php
/**
* @version      1.0.0
* @author       Best2Pay
* @package      Jshopping Best2Pay
* @copyright    Copyright (C) 2022 webdesigner-profi.de. All rights reserved.
* @license      GNU/GPL
*/

defined('_JEXEC') or die();

class com_best2payInstallerScript{
    private $com_jshopping_pm_path = JPATH_SITE . '/components/com_jshopping/payments';
    private $com_best2pay_pm_path = JPATH_SITE . '/components/com_jshopping/payments/pm_best2pay';
    private $com_best2pay_path = JPATH_ADMINISTRATOR . '/components/com_best2pay';

    public function install($parent){

        JFactory::getLanguage()->load('com_best2pay');
        
        if(!file_exists($this->com_jshopping_pm_path)){
            echo '<p>' . JText::_('JSHOP_CFG_BEST2PAY_JSH_NO_INSTALLED') . '</p>';
            return false;
        }
        if(!file_exists($this->com_best2pay_pm_path)){
            if(!@mkdir($this->com_best2pay_pm_path, 0755)){
                echo '<p>' . JText::_('JSHOP_CFG_BEST2PAY_CREATE_DIR_ERROR') . " " . $this->com_best2pay_pm_path . '</p>';
                return false;
            }
        }
        $filenames = ['adminparamsform.php', 'paymentform.php', 'pm_best2pay.php'];
        $errors = '';
        foreach($filenames as $filename){
            if(!@copy($this->com_best2pay_path . '/' . $filename, $this->com_best2pay_pm_path . '/' . $filename))
                $errors .= JText::_('JSHOP_CFG_BEST2PAY_COPY_ERROR') . " " . $filename . "\n";
        }
        if($errors){
            echo '<p>' . $errors . '</p>';
            return false;
        }
        return true;
    }

    public function uninstall($parent){
        if(file_exists($this->com_best2pay_pm_path)){
            $files = glob($this->com_best2pay_pm_path . "/*.*");
            foreach($files as $file_path){
                if(file_exists($file_path)) unlink($file_path);
            }
            return rmdir($this->com_best2pay_pm_path);
        }
        return true;
    }
}