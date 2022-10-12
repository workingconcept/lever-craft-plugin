<?php
/**
 * Lever plugin for Craft CMS 4.x
 *
 * Craft + Lever.
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\lever\variables;

use GuzzleHttp\Exception\GuzzleException;
use workingconcept\lever\Lever;
use workingconcept\lever\models\LeverJob;

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
     * @return array
     * @throws GuzzleException
     */
    public function jobs(array $params = []): array
    {
        return Lever::$plugin->api->getJobs($params);
    }

    /**
     * Get a single job by ID.
     *
     * @param $id
     * @return bool|LeverJob
     * @throws GuzzleException
     */
    public function job($id): bool|LeverJob
    {
        return Lever::$plugin->api->getJobById($id);
    }

}
