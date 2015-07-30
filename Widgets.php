<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\HumanitarianResponse;

use Piwik\View;
use Piwik\WidgetsList;
use Piwik\Common;
use Piwik\Piwik;

/**
 * This class allows you to add your own widgets to the Piwik platform. In case you want to remove widgets from another
 * plugin please have a look at the "configureWidgetsList()" method.
 * To configure a widget simply call the corresponding methods as described in the API-Reference:
 * http://developer.piwik.org/api-reference/Piwik/Plugin\Widgets
 */
class Widgets extends \Piwik\Plugin\Widgets
{
    /**
     * Here you can define the category the widget belongs to. You can reuse any existing widget category or define
     * your own category.
     * @var string
     */
    protected $category = 'HumanitarianResponse_HumanitarianResponse';

    /**
     * Here you can add one or multiple widgets. You can add a widget by calling the method "addWidget()" and pass the
     * name of the widget as well as a method name that should be called to render the widget. The method can be
     * defined either directly here in this widget class or in the controller in case you want to reuse the same action
     * for instance in the menu etc.
     */
    protected function init()
    {
       //$this->addWidget('Space Summary', $method = 'spaceSummary');
      // $this->addWidget('Example Widget 2',    $method = 'myExampleWidget', $params = array('myparam' => 'myvalue'));
    }

    /**
     * This method renders a widget as defined in "init()". It's on you how to generate the content of the
     * widget. As long as you return a string everything is fine. You can use for instance a "Piwik\View" to render a
     * twig template. In such a case don't forget to create a twig template (eg. myViewTemplate.twig) in the
     * "templates" directory of your plugin.
     *
     * @return string
     */
    public function spaceSummary()
    {
        // Make sure there is an idSite URL parameter
        $idSite = Common::getRequestVar('idSite', null, 'int');

        // Make sure the user has at least view access for the specified site. This is useful if you want to display data that is related to the specified site.
        Piwik::checkUserHasViewAccess($idSite);

        $space_id = Common::getRequestVar('space_id', null, 'int');
        $space_type = Common::getRequestVar('space_type', 'operation', 'string');

        

        // $view = new View('@HumanitarianResponse/myViewTemplate');
        // return $view->render();

        return $space_id;
    }

    /**
     * Here you can remove any widgets defined by any plugin.
     *
     * @param WidgetsList $widgetsList
     */
    public function configureWidgetsList(WidgetsList $widgetsList)
    {
        // $widgetsList->remove('NameOfWidgetCategory'); // will remove all widgets having this category
        // $widgetsList->remove('NameOfWidgetCategory', 'Widget name'); // will only remove a specific widget
    }
}
