<?php
require ('config/config.php');

try {
    $connexion = new PDO(DSN, USER, PASSWORD);
    $query = 'SELECT attribute_id FROM eav_attribute WHERE frontend_input="select" OR frontend_input="multiselect" GROUP BY attribute_id';
    $allAttributes  = $connexion->query($query)->fetchAll();

    foreach ($allAttributes as $attributeId) {
        $query = $connexion->query('SELECT eaov.value, eaov.store_id, COUNT(eaov.value_id) AS count FROM eav_attribute_option_value AS eaov
    INNER JOIN eav_attribute_option as eao ON eaov.option_id=eao.option_id
    WHERE eao.attribute_id='.$attributeId[0].' GROUP BY eaov.value, eaov.store_id')->fetchAll();
        if ($connexion->query('SELECT * FROM `catalog_product_entity_int` WHERE `attribute_id` = ' . $attributeId[0])->fetch()) {
            foreach ($query as $row){
                if($row['count']>1) {
                    $duplicateIds = $connexion->query('SELECT option_id FROM eav_attribute_option_value WHERE `value`="' . $row['value'] . '"')->fetchAll();
                    foreach ($duplicateIds as $key => $id) {
                        if ($key > 0){
                            $connexion->exec('UPDATE catalog_product_entity_int SET value = '.$duplicateIds[0][0].' WHERE value ='.$id[0]);
                            $connexion->exec('DELETE FROM eav_attribute_option_value WHERE option_id ='.$id[0]);
                        }
                    }
                }
            }
        }
        if ($connexion->query('SELECT * FROM `catalog_product_entity_varchar` WHERE `attribute_id` = '.$attributeId[0])->fetch()){
            //todo multiselect
            foreach ($query as $row){
                if($row['count']>1) {
                    echo "double";
                }
            }
        }
    }
} catch (PDOException $e) {
    echo 'Connexion échouée : ' . $e->getMessage();
}
