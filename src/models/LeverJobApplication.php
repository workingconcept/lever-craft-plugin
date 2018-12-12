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
 * Lever Job Application
 * https://github.com/lever/postings-api/blob/master/README.md#api-methods
 */

class LeverJobApplication extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string Candidate's name
     */
    public $name;

    /**
     * @var string Email address. Requires an "@" symbol. Candidate records will be merged when email addresses match.
     */
    public $email;

    /**
     * @var yii\web\UploadedFile Resume data. Only in `multipart/form-data` mode. Should be a file.
     */
    public $resume;

    /**
     * @var string Phone number
     */
    public $phone;

    /**
     * @var string Current company / organization
     */
    public $org;

    /**
     * @var array URLs for sites (Github, Twitter, LinkedIn, Dribbble, etc).
     *            Should be a JSON object like {"GitHub":"https://github.com/"} for JSON,
     *            or urls[GitHub]=https://github.com/ for multipart/form-data
     */
    public $urls;

    /**
     * @var string Additional information from the candidate
     */
    public $comments;

    /**
     * @var bool Disables confirmation email sent to candidates upon application.
     *           API accepts values of `true`, `false`, `"true"` or `"false"`.
     */
    public $silent;

    /**
     * @var string Adds a source tag to candidate (e.g. 'LinkedIn')
     */
    public $source;

    /**
     * @var string IP application was submitted from, used for detecting country for compliance reasons
     *             (e.g. `"184.23.195.146"`)
     */
    public $ip;

    /**
     * @var array Indicate whether candidate is open to being contacted about future opportunities
     *           (e.g. "consent":{"marketing":true} for JSON or consent[marketing]=true for multipart/form-data)
     */
    public $consent;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'email', 'phone', 'org', 'comments', 'source', 'ip'], 'string'],
            [['urls', 'consent'], 'array'],
            [['silent'], 'boolean'],
            ['resume', 'file'],
            [['name', 'email'], 'required'],
        ];
    }

    /**
     * Return an array that represents the model cleanly for MultipartStream.
     * @return array
     */
    public function toMultiPartPostData(): array
    {
        $streamElements = [];
        $fields = [
            'name',
            'email',
            'phone',
            'org',
            'urls',
            'comments',
            'source',
            'ip',
            'silent',
            'consent'
        ];

        foreach ($fields as $field)
        {
            if ($this->{$field} !== null)
            {
                $streamElements[] = [
                    'name'     => $field,
                    'contents' => $this->formatForPost($this->{$field})
                ];
            }
        }

        if ($this->resume !== null)
        {
            // send the resume file resource
            $streamElements[] = [
                'name'     => 'resume',
                'contents' => fopen($this->resume->tempName, 'r')
            ];
        }

        return $streamElements;
    }

    /**
     * Make booleans stringy.
     *
     * @param $var
     *
     * @return string
     */
    private function formatForPost($var)
    {
        if (is_bool($var) || $var === '1' || $var === '0')
        {
            return $var ? 'true' : 'false';
        }

        return $var;
    }

}
