<?php
/**
 * Lever plugin for Craft CMS 3.x
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\lever\models;

use craft\base\Model;

/**
 * Lever Job
 * https://github.com/lever/postings-api/blob/master/README.md#api-methods
 */

class LeverJob extends Model
{
    /**
     * @var string Unique job posting ID
     */
    public string $id;

    /**
     * @var string Job posting name
     */
    public string $text;

    /**
     * @var object Object with location, commitment, team, and department
     */
    public object $categories;

    /**
     * @var string An ISO 3166-1 alpha-2 code for a country / territory (or null to indicate an unknown country).
     *             This is not filterable. Note: It will be released as part of the offcycle release,
     *             progressive waved rollout starting in September, 2022
     */
    public string $country;

    /**
     * @var string Job description (as styled HTML).
     */
    public string $description;

    /**
     * @var string Job description (as plaintext).
     */
    public string $descriptionPlain;

    /**
     * @var string Extra lists (such as requirements, benefits, etc.) from the job posting.
     *             This is a list of `{text:NAME, content:"unstyled HTML of list elements"}`
     */
    public string $lists;

    /**
     * @var string Optional closing content for the job posting (as styled HTML). This may be an empty string.
     */
    public string $additional;

    /**
     * @var string Optional closing content for the job posting (as plaintext). This may be an empty string.
     */
    public string $additionalPlain;

    /**
     * @var string A URL which points to Lever's hosted job posting page.
     *             [Example](https://jobs.lever.co/leverdemo/5ac21346-8e0c-4494-8e7a-3eb92ff77902)
     */
    public string $hostedUrl;

    /**
     * @var string A URL which points to Lever's hosted application form to apply to the job posting.
     *             [Example](https://jobs.lever.co/leverdemo/5ac21346-8e0c-4494-8e7a-3eb92ff77902/apply)
     */
    public string $applyUrl;

    /**
     * @var string
     */
    public string $createdAt;

    /**
     * @var string Describes the primary workplace environment for a job posting. May be one of `unspecified`,
     *             `on-site`, `remote`, or `hybrid`. Not filterable.
     *             Note: to be released in waved rollouts starting October, 2022.
     */
    public string $workplaceType;

}
