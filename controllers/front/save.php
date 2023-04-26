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

        // Obtenemos productos ya seleccionados anteriormente
        $selected = $this->getJointsByProduct($id_product);
        foreach ($selected as $id) {
            $products[$id] = "checked";
        }
        
        if($action == 'charge') {
            foreach($products as $id => $value) {
                $prod = new Product($id,false,$this->context->language->id);
                echo '<li><div class="checkbox_joint"><label><input class = "jointCheckbox" type="checkbox"';
                echo 'value="' . $id . '" '.$value.'>' . $prod->name;
                echo '</label></div></li>';
            }
        }
        elseif ($action = 'check') {
            $checked = Tools::getValue('checked') === "true";
            echo json_encode([$this->setJoint(Tools::getValue('product'), Tools::getValue('value'), $checked)]);
        }

        
    }


    /**
     * Dado un producto, obtiene los productos adheridos manualmente por el usuario
     * 
     * @param $id_product   El id de producto a consultar
     * 
     * @return array|false  El registro actual en BD, o false si no existiese
     */
    public function getJointsByProduct($id_product)
    {
        $sql = "SELECT id_joint1, id_joint2, id_joint3 FROM `" . _DB_PREFIX_ . "dbjointpurchase_joints` WHERE id_product = ".$id_product;
        $results = Db::getInstance()->getRow($sql);
            if (!$results) {
                return false;
            }
            
            return $results;
    }

    /**
     * 
     */
    public function setJoint($id_product, $id_joint, $status)
    {
        $joints = $this->getJointsByProduct($id_product);
        

        if(!$joints && !$status) {
            return true;
        }

        if(!$joints) {
            $sql = "INSERT INTO `" . _DB_PREFIX_ . "dbjointpurchase_joints` 
                    (`id_product`, `id_joint1`)
                    VALUES ('".$id_product."', '".$id_joint."')";
            if (Db::getInstance()->execute($sql) == false) {
                return false;
            }
            return true;
        }
   
        // Ya existe registro para el producto dado, hay que actualizarlo

        // Nuevo joint
        
        if(!in_array($id_joint, $joints) ) {
            if(!$status) {
                return true;
            }
            return $this->pushJoint($joints, $id_joint);
        }
        
        // Joint existente
        if($status) {
            return true;
        }

        return $this->popJoint($id_product, $joints, $id_joint);

    }

    /**
     * Inserta un nuevo joint al producto (Si no hay hueco, retorna false)
     */
    private function pushJoint($joints, $id_joint)
    {
        foreach($joints as $index => $joint) {
            if(empty($joint)) {
                $sql = "UPDATE `" . _DB_PREFIX_ . "dbjointpurchase_joints` SET `".$index."`  = '".$id_joint."'";
                return Db::getInstance()->execute($sql);
            }
        }
        return false;
    }

    /**
     * Extrae un joint del producto (Si se queda vacío, eliminamos el producto en la tabla de joints)
     */
    private function popJoint($id_product, $joints, $id_joint)
    {
        $last = true;

        foreach($joints as $index => $joint) {
            if( ($joint == $id_joint) && $last ) {
                $sql = "DELETE FROM `" . _DB_PREFIX_ . "dbjointpurchase_joints` 
                        WHERE `" . _DB_PREFIX_ . "dbjointpurchase_joints`.`id_product` = ".$id_product;
                return Db::getInstance()->execute($sql);
            }
            if($joint == $id_joint) {
                $sql = "UPDATE `" . _DB_PREFIX_ . "dbjointpurchase_joints` SET `".$index."`  = ''";
                return Db::getInstance()->execute($sql);
            }
            $last = false;
        }
        return false;
    }
}