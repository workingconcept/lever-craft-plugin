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
    // Properties
    // =========================================================================

    /**
     * @var string Unique job posting ID
     */
    public $id;

    /**
     * @var string Job posting name
     */
    public $text;

    /**
     * @var object Object with location, commitment, team, and department
     */
    public $categories;

    /**
     * @var string Job description (as styled HTML).
     */
    public $description;

    /**
     * @var string Job description (as plaintext).
     */
    public $descriptionPlain;

    /**
     * @var string Extra lists (such as requirements, benefits, etc.) from the job posting.
     *             This is a list of `{text:NAME, content:"unstyled HTML of list elements"}`
     */
    public $lists;

    /**
     * @var string Optional closing content for the job posting (as styled HTML). This may be an empty string.
     */
    public $additional;

    /**
     * @var string Optional closing content for the job posting (as plaintext). This may be an empty string.
     */
    public $additionalPlain;

    /**
     * @var string A URL which points to Lever's hosted job posting page.
     *             [Example](https://jobs.lever.co/leverdemo/5ac21346-8e0c-4494-8e7a-3eb92ff77902)
     */
    public $hostedUrl;

    /**
     * @var string A URL which points to Lever's hosted application form to apply to the job posting.
     *             [Example](https://jobs.lever.co/leverdemo/5ac21346-8e0c-4494-8e7a-3eb92ff77902/apply)
     */
    public $applyUrl;

    /**
     * @var
     */
    public $createdAt;


    // Public Methods
    // =========================================================================


}
