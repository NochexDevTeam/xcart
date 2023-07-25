<?php

namespace Nochex\Nochex\View\Checkout;

use XCart\Extender\Mapping\Extender;

/**
 * @Extender\Mixin
 */
abstract class Payment extends \XLite\View\Checkout\Payment
{
    /**
     * Get JS files
     *
     * @return array
     */
    public function getJSFiles()
    {
        $list = parent::getJSFiles();

        $method = \XLite\Core\Database::getRepo('XLite\Model\Payment\Method')->findOneBy(['service_name' => 'Nochex']);

        if ($method && $method->isEnabled()) {
           
        }

        return $list;
    }
}
