<?php
require ('config/config.php');

function changeAttributeType($attributeCode, $newType)
{
    $allowedType = ['text', 'textarea'];
    try {
        $connexion = new PDO(DSN, USER, PASSWORD);
        $row = $connexion->query('SELECT * FROM `eav_attribute` WHERE attribute_code = "' . $attributeCode . '"')->fetch();
        $actualType = $row['frontend_input'];
        $id = $row['attribute_id'];
        if ($actualType !== $newType && in_array($newType, $allowedType, true) && $row['source_model'] === null) {

            if ($newType === 'text') {
                $backend = 'varchar';
            }
            if ($newType === 'textarea') {
                $backend = 'text';
            }

            if ($actualType === 'select') {
                $connexion->exec(
                    'INSERT INTO catalog_product_entity_' . $backend . ' (attribute_id, store_id, value, entity_id ) 
SELECT eao.attribute_id, eaov.store_id, eaov.value, cpei.entity_id FROM eav_attribute_option_value as eaov
JOIN eav_attribute_option as eao on eaov.option_id = eao.option_id 
JOIN catalog_product_entity_int as cpei ON cpei.attribute_id = eao.attribute_id 
AND cpei.value = eao.option_id AND cpei.store_id = eaov.store_id
where eao.attribute_id =' . $id);

                $connexion->exec('DELETE FROM catalog_product_entity_int WHERE attribute_id = ' . $id);
                $connexion->exec('DELETE eaov FROM eav_attribute_option_value as eaov
JOIN eav_attribute_option as eao on eaov.option_id = eao.option_id WHERE eao.attribute_id =' . $id);
                $connexion->exec('DELETE FROM eav_attribute_option WHERE attribute_id =' . $id);
            }

            if ($actualType === 'text') {
                $connexion->exec(
                    'INSERT INTO catalog_product_entity_' . $backend . ' (attribute_id, store_id, value, entity_id ) 
SELECT attribute_id, store_id, value, entity_id FROM catalog_product_entity_varchar 
where eao.attribute_id =' . $id);

                $connexion->exec('DELETE FROM catalog_product_entity_varchar WHERE attribute_id = ' . $id);
            }

            if ($actualType === 'multiselect') {

                $array = $connexion->query(
                    'SELECT * FROM catalog_product_entity_varchar WHERE attribute_id = ' . $id)->fetchAll();

                foreach ($array as $values) {
                    $options = explode(',', $values[3]);
                    foreach ($options as $select) {
                        $value = $connexion->query('SELECT value FROM eav_attribute_option_value 
                        WHERE option_id =' . $select)->fetch();
                        $connexion->exec('INSERT INTO catalog_product_entity_' . $backend . ' 
                        (attribute_id, store_id, value, entity_id)
                        VALUES ' . $values['attribute_id'] . ',' . $values['store_id'] . ',' . $value['value'] . ',' . $values['entity_id']);
                    }
                    $connexion->exec('DELETE FROM catalog_product_entity_varchar WHERE attribute_id = ' . $id . ' AND value =' . $values['value']);
                }

                $connexion->exec('UPDATE eav_attribute SET backend_type = "' . $backend . '" , frontend_input = "' . $newType . '" WHERE attribute_id =' . $id);
            }
        }
    } catch (PDOException $e) {
        echo 'Connexion échouée : ' . $e->getMessage();
    }

}

shell_exec('php bin/magento cache:flush');
shell_exec('php bin/magento indexer:reindex');