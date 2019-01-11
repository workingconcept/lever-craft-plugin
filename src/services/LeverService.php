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
use GuzzleHttp\Client;
use yii\base\Exception;

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
     * @event ApplyEvent Triggered before an application is validated.
     */
    const EVENT_BEFORE_VALIDATE_APPLICATION = 'beforeValidateApplication';

    /**
     * @event ApplyEvent Triggered before an application is sent to Lever.
     */
    const EVENT_BEFORE_SEND_APPLICATION = 'beforeSendApplication';

    /**
     * @event ApplyEvent Triggered after an application is sent to Lever.
     */
    const EVENT_AFTER_SEND_APPLICATION = 'afterSendApplication';


    // Public Properties
    // =========================================================================

    /**
     * @var \workingconcept\lever\models\Settings
     */
    public $settings;

    /**
     * @var string
     */
    protected static $apiBaseUrl = 'https://api.lever.co/v0/';

    /**
     * @var boolean
     */
    protected $isConfigured;


    // Private Properties
    // =========================================================================

    /**
     * @var \GuzzleHttp\Client
     */
    private $_client;


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
    }

    /**
     * Returns a configured Guzzle client.
     *
     * @return Client
     * @throws \Exception if our API key is missing.
     */
    public function getClient(): Client
    {
        // see if we've got the stuff to do the things
        $this->isConfigured = ! empty($this->settings->apiKey) &&
            ! empty($this->settings->site);

        if ( ! $this->isConfigured)
        {
            throw new Exception('Lever plugin not configured.');
        }

        if ($this->_client === null)
        {
            $this->_client = new Client([
                'base_uri' => self::$apiBaseUrl,
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept'       => 'application/json'
                ],
                'verify' => false,
                'debug' => false
            ]);
        }

        return $this->_client;
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
     * @throws \Exception if our API key is missing.
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

        $response = $this->getClient()->get($requestUrl);
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
     * @throws \Exception if our API key is missing.
     */
    public function getJobById($jobId)
    {
        try 
        {
            $response = $this->getClient()->get(sprintf(
                'postings/%s/%s',
                $this->settings->site,
                $jobId
            ));

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
     * @param int                  $jobPostId       Lever job identifier
     * @param LeverJobApplication  $jobApplication  Lever job identifier
     * @param bool                 $test            Whether or not we want to post to our own controller here for testing
     * 
     * @return boolean
     * @throws
     */
    public function applyForJob($jobPostId, $jobApplication, $test = false)
    {
        $postUrl = sprintf('postings/%s/%s?key=%s',
            $this->settings->site,
            $jobPostId,
            $this->settings->apiKey
        );

        if ($test)
        {
            // reconfigure client for testing
            $this->_client = new Client([
                'base_uri' => UrlHelper::baseUrl(),
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept'       => 'application/json'
                ],
                'verify' => false,
                'debug' => false
            ]);

            // https://site.local/actions/lever/apply/test
            $postUrl = UrlHelper::actionUrl('lever/apply/test');
        }

        $event = new ApplyEvent([ 'application' => $jobApplication ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_VALIDATE_APPLICATION))
        {
            $this->trigger(self::EVENT_BEFORE_VALIDATE_APPLICATION, $event);
            $jobApplication = $event->application;
        }

        if ( ! $jobApplication->validate())
        {
            Craft::info('Invalid job application.', 'lever');
            return false;
        }

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

        if ($response = $this->getClient()->post(
            $postUrl,
            [ 'multipart' => $jobApplication->toMultiPartPostData() ]
        ))
        {
            $responseIsHealthy = $response->getStatusCode() === 200 &&
                isset($response->getBody()->applicationId);

            if ($responseIsHealthy)
            {
                if ($this->hasEventHandlers(self::EVENT_AFTER_SEND_APPLICATION))
                {
                    $this->trigger(self::EVENT_AFTER_SEND_APPLICATION, $event);
                }

                return true;
            }

            Craft::info('Application may not have been submitted.', 'lever');
            return false;
        }

        Craft::info('Application could not be sent.', 'lever');
        return false;
    }
}