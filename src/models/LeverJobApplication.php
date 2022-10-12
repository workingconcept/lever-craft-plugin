<?php
/**
 * Lever plugin for Craft CMS 4.x
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
    /**
     * @var string Candidate's name
     */
    public string $name;

    /**
     * @var string Email address. Requires an "@" symbol. Candidate records will be merged when email addresses match.
     */
    public string $email;

    /**
     * @var \yii\web\UploadedFile Resume data. Only in `multipart/form-data` mode. Should be a file.
     */
    public \yii\web\UploadedFile $resume;

    /**
     * @var string Phone number
     */
    public string $phone;

    /**
     * @var string Current company / organization
     */
    public string $org;

    /**
     * @var array URLs for sites (Github, Twitter, LinkedIn, Dribbble, etc).
     *            Should be a JSON object like {"GitHub":"https://github.com/"} for JSON,
     *            or urls[GitHub]=https://github.com/ for multipart/form-data
     */
    public array $urls;

    /**
     * @var string Additional information from the candidate
     */
    public string $comments;

    /**
     * @var bool Disables confirmation email sent to candidates upon application.
     *           API accepts values of `true`, `false`, `"true"` or `"false"`.
     */
    public bool $silent;

    /**
     * @var string Adds a source tag to candidate (e.g. 'LinkedIn')
     */
    public string $source;

    /**
     * @var string IP application was submitted from, used for detecting country for compliance reasons
     *             (e.g. `"184.23.195.146"`)
     */
    public string $ip;

    /**
     * @var array Indicate whether candidate is open to being contacted about future opportunities
     *           (e.g. "consent":{"marketing":true} for JSON or consent[marketing]=true for multipart/form-data)
     */
    public array $consent;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['name', 'email', 'phone', 'org', 'comments', 'source', 'ip'], 'string'],
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

        foreach ($fields as $field) {
            if ($this->{$field} !== null && ! empty($this->{$field})) {

				if ($streamElement = $this->formatForPost($field, $this->{$field})) {
					$streamElements = array_merge($streamElements, $streamElement);
				}
            }
        }

        if ($this->resume !== null) {
            // Send the resumé file resource
            $streamElements[] = [
                'name' => 'resume',
				'filename' => $this->resume->name,
                'contents' => fopen($this->resume->tempName, 'r')
            ];
        }

        return $streamElements;
    }

    /**
	 * Prep field as post data. (Stringy booleans + flattened arrays.)
     *
     * @param string $name
     * @param mixed $value
     *
     * @return ?array
     */
    private function formatForPost(string $name, mixed $value): ?array
    {
		if (empty($value)) {
			return null;
		}

		if (is_bool($value) || $value === '1' || $value === '0') {
            return [
				[
					'name' => $name,
					'contents' => $value ? 'true' : 'false'
				]
			];
        }

		if (is_array($value)) {
			$items = [];

			foreach ($value as $k => $v) {
				$items[] = [
					'name' => $name . '[' . $k . ']',
					'contents' => $v
				];
			}

			return $items;
		}

		return [
			[
				'name' => $name,
				'contents' => $value
			]
		];
    }

}
