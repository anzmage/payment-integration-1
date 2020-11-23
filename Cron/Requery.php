<?php

/**
 * @author      anzmage<anzmage@anzmage.com>
 * @copyright   Copyright © 2019 Anzmage. All rights reserved.
 */

namespace Anzmage\Ipay88\Cron;

use Anzmage\Ipay88\Helper\Requery as RequeryHelper;

/**
 * Class Requery
 *
 * @pacakge Anzmage\Ipay88\Cron
 */

class Requery
{
    /**
     * @var helperData
     */

    protected $helperData;

    /**
     * Constructor
     *
     * @param Data $helperData
     */
  
    public function __construct(
        RequeryHelper $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * Execute the Cron
     *
     * @return void
     */

    public function execute()
    {
        try {
            $this->helperData->doRequery();
        } catch (\Exception $x) {
            $this->helperData->klog($x->getMessage());
        }
    }
}
