<?php

$sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dbjointpurchase_joints`';

if (Db::getInstance()->execute($sql) == false) {
    return false;
}
