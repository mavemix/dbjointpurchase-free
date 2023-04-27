<?php declare( strict_types = 1 );

namespace mavemix\dbjointpurchase;



class JointHandler 
{
    /**
     * Dado un producto, obtiene los productos adheridos manualmente por el usuario
     * 
     * @param $id_product   El id de producto a consultar
     * @param $front        Indica si el restultado debe mostrarse en el formato usado en el front para construir los productos
     * 
     * @return array|false  El registro actual en BD, o false si no existiese
     */
    public static function getJointsByProduct($id_product, $front = false)
    {
        $sql = "SELECT id_joint1, id_joint2, id_joint3 FROM `" . _DB_PREFIX_ . "dbjointpurchase_joints` WHERE id_product = ".$id_product;
        
        $results = \Db::getInstance()->getRow($sql);

        $front ?? $results = array_filter($results);
        
        if (!$results) {
            return false;
        }

        if(!$front) {
            return $results;
        }

        $sql = "SELECT p.id_product, p.price, p.id_category_default
        FROM " . _DB_PREFIX_ . "product p
        LEFT JOIN " . _DB_PREFIX_ . "product_shop ps ON p.id_product = ps.id_product
        " . \Shop::addSqlAssociation('product', 'p') . "
        " . \Product::sqlStock('p', 0) . "
        WHERE p.id_product IN (".implode(",",$results).")
            AND ps.active = 1 
            AND p.available_for_order = 1  
            AND p.visibility != 'none' 
            AND p.price > 0
            AND (stock.out_of_stock = 1 OR stock.quantity > 0)
        ";

        $products = [];
        
        $results = \Db::getInstance()->ExecuteS($sql);
        if (count($results) >= 1) {
            foreach ($results as $key => $row) {
                $products[$key][] = array(
                    'id_product' => $row['id_product'],
                    'price' => $row['price'],
                );
            }
            return $products;
        }
        return false;

            
    }

    /**
     * Establece el estado de un joint del producto y lo crea/elimina/modifica en BD, si procede.
     * Si el producto se queda sin joints, borra el registro en BD
     * 
     * @param int $id_product
     * @param int $id_joint
     * @param bool $status
     * 
     * @return bool Devuelve false si ha habido error
     */
    public static function setJoint($id_product, $id_joint, $status):bool
    {
        $joints = self::getJointsByProduct($id_product);
        
        if(!$joints && !$status) {
            return true;
        }

        if(!$joints) {
            $sql = "INSERT INTO `" . _DB_PREFIX_ . "dbjointpurchase_joints` 
                    (`id_product`, `id_joint1`)
                    VALUES ('".$id_product."', '".$id_joint."')";
            if (\Db::getInstance()->execute($sql) == false) {
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
            return self::pushJoint($id_product, $joints, $id_joint);
        }
        
        // Joint existente
        if($status) {
            return true;
        }

        // Eliminar joint
        return self::popJoint($id_product, $joints, $id_joint);

    }

    /**
     * Inserta un nuevo joint al producto (Si no hay hueco, retorna false)
     * 
     * @param int $id_product
     * @param array $joints
     * @param bool $id_joint
     * 
     * @return bool Devuelve false si ha habido error en BD
     */
    public static function pushJoint($id_product, $joints, $id_joint):bool
    {
        foreach($joints as $index => $joint) {
            if($joint == 0) {
                $sql = "UPDATE `" . _DB_PREFIX_ . "dbjointpurchase_joints` SET `".$index."`  = ".$id_joint." WHERE `id_product`= ".$id_product;
                return \Db::getInstance()->execute($sql);
            }
        }
        return false;
    }

    /**
     * Extrae un joint del producto (Si se queda vacío, eliminamos el producto en la tabla de joints)
     * 
     * @param int $id_product
     * @param array $joints
     * @param bool $id_joint
     * 
     * @return bool
     */
    public static function popJoint($id_product, $joints, $id_joint):bool
    {
        foreach($joints as $index => $joint) {
            if( ($joint == $id_joint) && (count(array_filter($joints))==1) ) {
                $sql = "DELETE FROM `" . _DB_PREFIX_ . "dbjointpurchase_joints` 
                        WHERE `" . _DB_PREFIX_ . "dbjointpurchase_joints`.`id_product` = ".$id_product;
                return \Db::getInstance()->execute($sql);
            }
            if($joint == $id_joint) {
                $sql = "UPDATE `" . _DB_PREFIX_ . "dbjointpurchase_joints` SET `".$index."`  = 0";
                return \Db::getInstance()->execute($sql);
            }
        }
        return false;
    }
}