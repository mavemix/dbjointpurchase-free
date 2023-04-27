<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    DevBlinders <soporte@devblinders.com>
 * @copyright Copyright (c) DevBlinders
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */


 include_once('../../src/jointHandler.php');

 use mavemix\dbjointpurchase\JointHandler;

class DbJointPurchaseSaveModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        $this->ajax = true;
        parent::initContent();
    }


    public function displayAjax()
    {
        $action = Tools::getValue('action');
        $products = Tools::getValue('joints');
        $id_product = Tools::getValue('product');

        // Obtenemos productos ya seleccionados anteriormente (no nulos)
        $selected = array_filter(JointHandler::getJointsByProduct($id_product));
        foreach ($selected as $id) {
            $products[$id] = "checked";
        }
        
        if($action == 'charge') {
            foreach($products as $id => $value) {
                $prod = new Product($id,false,$this->context->language->id);
                $image = new Image(Product::getCover($id)['id_image']);
                $path = '/img/p/' . $image->getExistingImgPath() . '.' . $image->image_format;
                
                echo '<li><div class="checkbox_joint"><label><input class = "jointCheckbox" type="checkbox"';
                echo 'value="' . $id . '" '.$value.'><img class="dbjoint_img" src="'.$path.'">' . $prod->name;
                echo '</label></div></li>';
            }
        }
        elseif ($action = 'check') {
            $checked = Tools::getValue('checked') === "true";
            echo json_encode([JointHandler::setJoint(Tools::getValue('product'), Tools::getValue('value'), $checked)]);
        }

        
    }

}