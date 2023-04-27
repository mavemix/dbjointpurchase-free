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
        $results = array_filter(\Db::getInstance()->getRow($sql));
        
        if (!$results) {
            return false;
        }

        if(!$front) {
            return $results;
        }

        $sql = "SELECT od.product_id, count(od.product_id) as total, p.price, p.id_category_default
        FROM " . _DB_PREFIX_ . "order_detail od
        LEFT JOIN " . _DB_PREFIX_ . "product p ON od.product_id = p.id_product
        LEFT JOIN " . _DB_PREFIX_ . "product_shop ps ON od.product_id = ps.id_product
        " . \Shop::addSqlAssociation('product', 'p') . "
        " . \Product::sqlStock('p', 0) . "
        WHERE ps.active = 1 
            AND p.available_for_order = 1  
            AND p.visibility != 'none' 
            AND p.price > 0
            AND (stock.out_of_stock = 1 OR stock.quantity > 0)";
    if (!empty($excludes)) {
        $sql .= " AND od.product_id NOT IN (" . $excludes . ")";
    }
    $sql .= " GROUP BY od.product_id 
        HAVING COUNT(*) > 1 
        ORDER BY total DESC
        LIMIT 3";
    $results = \Db::getInstance()->ExecuteS($sql);
    if (count($results) >= 1) {
        foreach ($results as $key => $row) {
            $products[$key][] = array(
                'id_product' => $row['product_id'],
                'price' => $row['price'],
            );
        }
        return $products;
    }
    return false;

            
    }

    /**
     * 
     */
    public static function setJoint($id_product, $id_joint, $status)
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

        return self::popJoint($id_product, $joints, $id_joint);

    }

    /**
     * Inserta un nuevo joint al producto (Si no hay hueco, retorna false)
     */
    public static function pushJoint($id_product, $joints, $id_joint)
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
     */
    public static function popJoint($id_product, $joints, $id_joint)
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