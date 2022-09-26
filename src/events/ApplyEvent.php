<?php
/**
 * Lever plugin for Craft CMS 3.x
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\lever\events;

use workingconcept\lever\models\LeverJobApplication;
use yii\base\Event;

/**
 * Job application event.
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */
class ApplyEvent extends Event
{
    /**
     * @var LeverJobApplication The job application to be submitted.
     */
    public LeverJobApplication $application;

    /**
     * @var bool Whether the application is junk that should not be sent to Lever.
     */

    public bool $isSpam = false;

}
