<?php

Yii::import('zii.widgets.CMenu');

/**
 * Smart Menu is an extension that aims to automatize the checkAccess()
 * process to define the visibility of your menu items.
 *
 * @author Sidney Lins <solucoes@wmaior.com>
 * @copyright Copyright &copy; 2011 Sidney Lins
 * @version 0.3.0
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
                $authItemName=$this->generateAuthItemNameFromItem($item);
                $params=$this->compoundParams($item);

                $allowedAccess = Yii::app()->user->checkAccess($authItemName, $params);
                $item['visible'] = $allowedAccess;

                $this->trace($item, $authItemName, $params, $allowedAccess);
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
     * Generate auth item name to be used in checkAccess() function if "authItemName"
     * is not defined in menu item.
     *
     * The generated auth item name is formed using module name (whether any),
     * controller id and action id, all of them are extracted of 'url' or 'submit'
     * options of menu item. If there is no module in 'url'|'submit', just controller
     * and action are used. If there is no controller too, the current controller
     * id will be used.
     *
     * @param mixed $item If not array (as '#' or 'http://...' menu items), it will
     * be returned with no changes. If array, the 'url' or 'submit' options will
     * be used. If there is no 'url' or 'submit', it will be returned with no changes.
     *
     * @return string The auth item name generated.
     */
    protected function generateAuthItemNameFromItem($item){
        if(isset($item['authItemName']))
            return $item['authItemName'];
        else
        {
            if(isset($item['url']) && is_array($item['url']))
                $url=$item['url'];
            elseif(isset($item['linkOptions']['submit']) && is_array($item['linkOptions']['submit']))
                $url=$item['linkOptions']['submit'];
            else
                return $item['url'];

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

    /**
     * Compound the $params to be sent to checkAccess() function.
     * The params are obtained from "authParams" option and if it is not setted,
     * YSM try to obtain them from "url" or "submit" options. If it also has no params,
     * $_GET will be used.
     *
     * @param mixed $item The menu item.
     * @return array The params.
     */
    protected function compoundParams($item) {
        if (isset($item['authParams']))
            return $item['authParams'];
        else
        {
            /* If item has an url option and it has additional params */
            if (isset($item['url']) && is_array($item['url']) && count($item['url']) > 1)
                return array_slice($item['url'], 1, null, true);
            /* Else if item has an submit option and it has addtionall params */
            elseif (isset($item['linkOptions']['submit']) && is_array($item['linkOptions']['submit']) && count($item['linkOptions']['submit']) > 1)
                return array_slice($item['linkOptions']['submit'], 1, null, true);
            else
                return $_GET;
        }
    }

    /**
     * Trace useful informations.
     *
     * @param mixed $item
     * @param string $authItemName
     * @param array $params
     * @param boolean $allowedAccess
     */
    protected function trace($item, $authItemName, $params, $allowedAccess) {
        $traceMessage = "Item {$item['label']} is " . ($allowedAccess ? '' : '*not* ') . "visible. ";
        $traceMessage.= "You have " . ($allowedAccess ? '' : 'no ') . "permissions to $authItemName with params:\n";

        foreach ($params as $name => $value)
            $traceMessage.="> $name=$value \n";

        Yii::trace($traceMessage, 'YiiSmartMenu');
    }
}
