<?php
/**
 * Lever plugin for Craft CMS 3.x
 *
 * Craft + Lever.
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\lever\models;

use workingconcept\lever\Lever;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    /**
     * @var string Lever API key
     */
    public $apiKey = '';

    /**
     * @var string Lever site slug
     */
    public $site = '';

    /**
     * @var string Source value to be sent with every application from this site
     */
    public $applicationSource = 'Craft CMS';

    /**
     * @var bool Whether or not candidate should receive an email upon application
     */
    public $applySilently = true;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['apiKey', 'site', 'applicationSource'], 'string'],
            [['applySilently'], 'boolean'],
            [['apiKey', 'site', 'applicationSource', 'applySilently'], 'required'],
        ];
    }

}
