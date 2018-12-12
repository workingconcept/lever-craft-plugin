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

use Craft;

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
     */
    public function job($id)
    {
        return Lever::$plugin->api->getJobById($id);
    }

    /**
     * Send a job application per $_POST data.
     *
     * @param $id
     *
     * @return mixed
     */
    public function applyForJob($id)
    {
        return Lever::$plugin->api->applyForJob($id);
    }

    /**
     * Get any errors, most likely from trying to submit a job application.
     *
     * @return mixed
     */
    public function errors()
    {
        return Lever::$plugin->api->getErrors();
    }

}
