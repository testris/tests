<?php

use Helper\Device;


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void executeJS($script, $arguments = null)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class UiTester extends \Codeception\Actor
{
    use _generated\UiTesterActions;

    /**
     * Define custom actions here
     */
    public function amOnDevice($device)
    {
        $height = $this->getPageHeightOnDevice($device) ?? Device::getHeight($device);
        $this->resizeWindow(Device::getWidth($device), $height);
        $this->reloadPage();
        $this->scrollUpAndDown();
        $this->waitForAjaxStopped();
    }

    public function scrollUpAndDown()
    {
        $this->executeJS("window.scrollTo(0,document.body.scrollHeight);");
        $this->executeJS("window.scrollTo(0,0);");
    }

    public function hideElementsForScreenshot(array $excludeElements)
    {
        foreach ($excludeElements as $element) {
            $this->setVisibility($element, false);
        }
    }

    private function setVisibility($elementSelector, $isVisible)
    {
        $styleVisibility = $isVisible ? 'visible' : 'hidden';
        $this->executeJS('
            var elements = [];
            elements = document.querySelectorAll("' . $elementSelector . '");
            if( elements.length > 0 ) {
                for (var i = 0; i < elements.length; i++) {
                    elements[i].style.visibility = "' . $styleVisibility . '";
                }
            }
        ');
    }

    private function getPageHeightOnDevice($device)
    {
        $this->resizeWindow(Device::getWidth($device), 600);
        $this->scrollUpAndDown();
        $this->waitForPageLoaded();
        $windowBorders = 600;
        $height = $this->executeJS('return $( document ).height();') + $windowBorders;
        return $height;
    }

    public function waitForAjaxStopped($timeout = 30)
    {
        $this->waitForJS("if (window.jQuery) return jQuery.active == 0; else return true;", $timeout);
    }

    public function waitForPageLoaded($timeout = 30)
    {
        $this->waitForJS(
            'return document.readyState == "complete"',
            $timeout
        );
        $this->waitForAjaxStopped();
    }

}
