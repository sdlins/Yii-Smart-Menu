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

    /**
     * Filter recursively the menu items received setting visibility true or
     * false according to result of the checkAccess() function.
     *
     * @param array $items The menu items being filtered.
     * @return array The menu items with visibility defined by checkAccess().
     */
    protected function filterItems(array $items){
        foreach($items as $pos=>$item)
        {
            if(!isset($item['visible']))
            {
                /**
                 * Generate auth item name if the option 'authItemName' of menu item
                 * is not defined.
                 */
                if(!isset($item['authItemName']))
                    $authItemName=$this->generateAuthItemNameFromItem($item);
                else
                    $authItemName=$item['authItemName'];

                /**
                 * Use $_GET as params if the option 'authParams' of menu item is
                 * not defined.
                 */
                if(!isset($item['authParams']))
                    $params=$_GET;
                else
                    $params=$item['authParams'];

                $allowedAccess = Yii::app()->user->checkAccess($authItemName, $params);
                $item['visible'] = $allowedAccess;

                Yii::trace("Item {$item['label']} is ".($allowedAccess?'':'*not* ')."visible. You have no permissions to $authItemName");
            }

            /**
             * If current item is visible and has sub items, loops recursively
             * on them.
             */
            if(isset($item['items']) && $item['visible'])
                $item['items']=$this->filterItems($item['items']);

            $items[$pos]=$item;
        }
        return $items;
    }

    /**
     * Generate auth item name to be used in checkAccess() function.
     * The generated auth item name is formed using module name (whether any),
     * controller id and action id, all of them are extracted of 'url' or 'submit'
     * options of menu item. If there is no module in 'url'|'submit', just controller
     * and action are used. If there is no controller too, the current controller
     * id will be used.
     *
     * @param mixed $menu If not array (as '#' or 'http://...' menu items), it will
     * be returned with no changes. If array, the 'url' or 'submit' options will
     * be used. If there is no 'url' or 'submit', it will be returned with no changes.
     *
     * @return string The auth item name generated.
     */
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
