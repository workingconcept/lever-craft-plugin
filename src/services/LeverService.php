<?php
/**
 * Lever plugin for Craft CMS 3.x
 *
 * Craft + Lever.
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\lever\services;

use workingconcept\lever\Lever;
use workingconcept\lever\models\LeverJobApplication;
use workingconcept\lever\models\LeverJob;
use workingconcept\lever\events\ApplyEvent;
use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;
use yii\web\UploadedFile;

/**
 * @author    Working Concept
 * @package   Lever
 * @since     1.0.0
 */
class LeverService extends Component
{
    // Constants
    // =========================================================================

    /**
     * Triggered before an application is sent to Lever.
     */
    const EVENT_BEFORE_SEND_APPLICATION = 'beforeSendApplication';

    /**
     * Triggered after an application is sent to Lever.
     */
    const EVENT_AFTER_SEND_APPLICATION = 'afterSendApplication';


    // Public Properties
    // =========================================================================

    /**
     * @var \workingconcept\lever\models\Settings
     */
    public $settings;

    /**
     * @var array Populated with error message string(s) if submission fails.
     */
    public $errors = [];

    /**
     * @var string
     */
    protected $apiBaseUrl = 'https://api.lever.co/v0/';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var boolean
     */
    protected $isConfigured;


    // Public Methods
    // =========================================================================

    /**
     * Initializes the service.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        // populate the settings
        $this->settings = Lever::$plugin->getSettings();

        // see if we've got the stuff to do the things
        $this->isConfigured = ! empty($this->settings->apiKey) && ! empty($this->settings->site);

        if ($this->isConfigured)
        {
            $this->client = new \GuzzleHttp\Client([
                'base_uri' => $this->apiBaseUrl,
                'headers' => [
                    'Content-Type' => 'applicaton/json; charset=utf-8', // set to multipart/form-data for post
                    'Accept'       => 'application/json'
                ],
                'verify' => false,
                'debug' => false
            ]);
        }
    }

    /**
     * Gets a list of job postings.
     * https://github.com/lever/postings-api/blob/master/README.md#get-a-list-of-job-postings
     *
     * @param array $params Key/value array of valid API query parameters.
     *                      [
     *                          'mode' => '',
     *                          'skip' => '',
     *                          'limit' => '',
     *                          'location' => '',
     *                          'commitment' => '',
     *                          'team' => '',
     *                          'department' => '',
     *                          'level' => '',
     *                          'group' => ''
     *                      ]
     *
     * @return array
     */
    public function getJobs($params = []): array
    {
        // TODO: collect paginated results

        $requestUrl = sprintf('postings/%s', $this->settings->site);

        $supportedParams = [
            'mode',
            'skip',
            'limit',
            'location',
            'commitment',
            'team',
            'department',
            'level',
            'group'
        ];

        if ( ! empty($params))
        {
            $includedParams = [];

            foreach ($params as $key => $value)
            {
                if (in_array($key, $supportedParams))
                {
                    $includedParams[$key] = $value;
                }
            }

            if (count($includedParams))
            {
                $queryString = http_build_query($includedParams);
                $requestUrl .= '?' . $queryString;
            }
        }

        $response = $this->client->get($requestUrl);
        $responseData = json_decode($response->getBody());

        $jobs = [];

        foreach ($responseData as $jobData)
        {
            $jobs[] = new LeverJob($jobData);
        }

        return $jobs;
    }

    /**
     * Gets a specific job posting.
     * https://github.com/lever/postings-api/blob/master/README.md#get-a-specific-job-posting
     *
     * @param  string  $jobId  Lever job identifier
     * 
     * @return mixed
     */

    public function getJobById($jobId)
    {
        try 
        {
            $response     = $this->client->get(sprintf('postings/%s/%s', $this->settings->site, $jobId));
            $responseData = json_decode($response->getBody());

            return new LeverJob($responseData);
        }
        catch(\GuzzleHttp\Exception\RequestException $e) 
        {
            if ($e->getCode() === 404)
            {
                // retired and invalid job postings should 404 peacefully
                return false;
            }

            throw $e;
        }
    }

    /**
     * Sends job posting to Lever.
     * https://github.com/lever/postings-api/blob/master/README.md#apply-to-a-job-posting
     *
     * @param int  $jobPostId  Lever job identifier
     * @param bool $test       Whether or not we want to post to our own controller here for testing
     * 
     * @return boolean
     * @throws
     */

    public function applyForJob($jobPostId, $test = false)
    {
        $request = Craft::$app->getRequest();
        $postUrl = sprintf('postings/%s/%s?key=%s',
            $this->settings->site,
            $jobPostId,
            $this->settings->apiKey
        );

        if ($test)
        {
            // reconfigure client for testing
            $this->client = new \GuzzleHttp\Client([
                'base_uri' => UrlHelper::baseUrl(),
                'headers' => [
                    'Content-Type' => 'applicaton/json; charset=utf-8', // set to multipart/form-data for post
                    'Accept'       => 'application/json'
                ],
                'verify' => false,
                'debug' => false
            ]);

            // https://site.local/actions/lever/apply/test
            $postUrl = UrlHelper::actionUrl('lever/apply/test');
        }

        $resumeIncluded = ! empty($_FILES['resume']['tmp_name'])
            && ! empty($_FILES['resume']['name']);

        if ( ! $application = new LeverJobApplication([
            'name'     => $request->getParam('name'),
            'email'    => $request->getParam('email'),
            'phone'    => $request->getParam('phone'),
            'org'      => $request->getParam('org'),
            'urls'     => $request->getParam('urls'),
            'comments' => $request->getParam('comments'),
            'ip'       => $request->getUserIP(),
            'silent'   => $this->settings->applySilently,
            'source'   => $this->settings->applicationSource,
        ]))
        {
            array_merge($this->errors, $application->getErrors());
            return false;
        }

        if ($resumeIncluded)
        {
            $application->resume = UploadedFile::getInstanceByName('resume');
        }

        if ( ! $application->validate())
        {
            $this->errors = array_merge($this->errors, $application->getErrors());
            return false;
        }

        $event = new ApplyEvent([ 'application' => $application ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SEND_APPLICATION))
        {
            $this->trigger(self::EVENT_BEFORE_SEND_APPLICATION, $event);
        }

        if ($event->isSpam)
        {
            Craft::info('Spammy job application ignored.', 'lever');

            // pretend it's fine so they feel good about themselves
            return true;
        }

        if ($response = $this->client->post($postUrl, [ 'multipart' => $application->toMultiPartPostData() ]))
        {
            if ($response->getStatusCode() === 200 && isset($response->getBody()->applicationId))
            {
                // all good!

                if ($this->hasEventHandlers(self::EVENT_AFTER_SEND_APPLICATION))
                {
                    $this->trigger(self::EVENT_AFTER_SEND_APPLICATION, $event);
                }

                return true;
            }
            else
            {
                $this->errors[] = Craft::t('lever', 'Your application could not be submitted.');
                return false;
            }
        }

        $this->errors[] = Craft::t('lever', 'There was a problem submitting your application.');
        return false;
    }
}