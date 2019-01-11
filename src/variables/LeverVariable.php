<?php
/**
 * Lever plugin for Craft CMS 3.x
 *
 * Craft + Lever.
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\lever\variables;

use workingconcept\lever\Lever;

/**
 * @author    Working Concept
 * @package   Lever
 * @since     1.0.0
 */
class LeverVariable
{
    /**
     * Get a list of jobs.
     *
     * @param array $params Valid URL parameters for search.
     *
     * @return mixed
     * @throws
     */
    public function jobs($params = [])
    {
        return Lever::$plugin->api->getJobs($params);
    }

    /**
     * Get a single job by ID.
     *
     * @param $id
     * @return mixed
     * @throws
     */
    public function job($id)
    {
        return Lever::$plugin->api->getJobById($id);
    }

}
