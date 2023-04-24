<?php 

$sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dbjointpurchase_joints` (
    `id_product` int(11) NOT NULL,
    `id_joint1` int(11) NOT NULL ,
    `id_joint2` int(11) NOT NULL ,
    `id_joint3` int(11) NOT NULL ,
    PRIMARY KEY (`id_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

if (Db::getInstance()->execute($sql) == false) {
    return false;
}