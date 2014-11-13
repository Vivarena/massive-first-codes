<?php
/**
 * User: Mike
 * Date: 10.11.2011
 * Time: 15:07:36
 */
class SavingSortingBehavior extends ModelBehavior {

    /**
     * Saving sorting as tree table
     * @param $model
     * @param $params - values in json format
     * @return bool
     */
    public function saveSort($model, $params)
    {

        if (isset($params) && !empty($params)) {
            $modelName = $model->name;
            $successfully = true;
            $items = json_decode($params);
            $model->Behaviors->detach('Tree');
            foreach ($items as $item)
            {
                $item = (array)$item;
                $model->id = $item['id'];
                $item['parent_id'] = ($item['parent_id'] == 'root') ? null : $item['parent_id'];
                if (!$model->save($item)) $successfully = false;
            }
            return $successfully;
        }

        return false;


    }

}