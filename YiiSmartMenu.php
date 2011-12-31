<?php

Yii::import('zii.widgets.CMenu');

/**
 * Smart Menu is an extension that aims to automatize the checkAccess()
 * process to define the visibility of your menu items.
 *
 * @author Sidney Lins <solucoes@wmaior.com>
 * @copyright Copyright &copy; 2011 Sidney Lins
 * @version 0.2.1
 * @license New BSD Licence
 */
class YiiSmartMenu extends CMenu
{
    /**
     * Defines what separator will be used to concat {module}, {controller} and
     * {authItemName}. Defaults to . (dot).
     *
     * @var string
     */
    public $partItemSeparator = ".";
    /**
     * Defines whether to capitalize the first letter of {module}, {controller}
     * and {authItemName} before to concat them. Defaults to true.
     *
     * @var boolean
     */
    public $upperCaseFirstLetter = true;

    public function init() {
        $this->items = $this->filterItems($this->items);
        return parent::init();
    }

    protected function filterItems($items){
        foreach($items as $pos=>$item)
        {
            if(!isset($item['visible']))
            {
                if(!isset($item['authItemName']))
                    $authItemName=$this->generateAuthItemNameFromItem($item);
                else
                    $authItemName=$item['authItemName'];

                if(!isset($item['authParams']))
                    $params=$_GET;
                else
                    $params=$item['authParams'];

                $allowedAccess = Yii::app()->user->checkAccess($authItemName, $params);
                $item['visible'] = $allowedAccess;

                Yii::trace("Item {$item['label']} is ".($allowedAccess?'':'*not* ')."visible. You have no permissions to $authItemName");
            }

            if(isset($item['items']) && $item['visible'])
                $item['items']=$this->filterItems($item['items']);

            $items[$pos]=$item;
        }
        return $items;
    }

    protected function generateAuthItemNameFromItem($menu){
        if(isset($menu['url']) && is_array($menu['url']))
            $url=$menu['url'];
        elseif(isset($menu['linkOptions']['submit']) && is_array($menu['linkOptions']['submit']))
            $url=$menu['linkOptions']['submit'];
        else
            return $menu['url'];

        $templateParts=array();

        $module = $this->getController()->getModule() ? ($this->getController()->getModule()->getId()) : false;
        $controller = $this->getController()->id;
        $authItemName = trim($url[0], '/');

        if($this->upperCaseFirstLetter)
        {
            $module = ucfirst($module);
            $controller = ucfirst($controller);
            $authItemName = ucfirst($authItemName);
        }

        if (strpos($authItemName, '/') !== false) {
            $parts = explode('/', $authItemName);

            if($this->upperCaseFirstLetter){
                foreach ($parts as $i => $part)
                    $parts[$i] = ucfirst($part);
            }

            $numOfParts=count($parts);
            if($numOfParts>2)
                $templateParts['{module}']=$parts[$numOfParts-3];

            $templateParts['{controller}']=$parts[$numOfParts-2];
            $templateParts['{action}']=$parts[$numOfParts-1];
        }
        else
        {
            if($module)
                $templateParts['{module}']=$module;

            $templateParts['{controller}']=$controller;
            $templateParts['{action}']=$authItemName;
        }

        return implode($this->partItemSeparator, $templateParts);
    }
}
