<?php
/**
 * Lever plugin for Craft CMS 4.x
 *
 * Craft + Lever.
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\lever\services;

use craft\errors\SiteNotFoundException;
use craft\helpers\App;
use GuzzleHttp\Exception\GuzzleException;
use workingconcept\lever\Lever;
use workingconcept\lever\models\LeverJobApplication;
use workingconcept\lever\models\LeverJob;
use workingconcept\lever\events\ApplyEvent;
use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;
use GuzzleHttp\Client;
use workingconcept\lever\models\Settings;
use yii\base\Exception;
use yii\helpers\Json;

/**
 * @author    Working Concept
 * @package   Lever
 * @since     1.0.0
 *
 * @property-read Client $client
 */
class LeverService extends Component
{
    /**
     * @event ApplyEvent Triggered before an application is validated.
     */
    public const EVENT_BEFORE_VALIDATE_APPLICATION = 'beforeValidateApplication';

    /**
     * @event ApplyEvent Triggered before an application is sent to Lever.
     */
    public const EVENT_BEFORE_SEND_APPLICATION = 'beforeSendApplication';

    /**
     * @event ApplyEvent Triggered after an application is sent to Lever.
     */
    public const EVENT_AFTER_SEND_APPLICATION = 'afterSendApplication';

    /**
     * @var Settings
     */
    public Settings $settings;

    /**
     * @var string
     */
    protected static string $apiBaseUrl = 'https://api.lever.co/v0/';

    /**
     * @var boolean
     */
    protected bool $isConfigured;

    /**
     * @var ?Client
     */
    private ?Client $_client = null;

    /**
     * Initializes the service.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();

        /**
         * @var Settings $settings
         */
        $settings = Lever::$plugin->getSettings();

        $this->settings = $settings;
    }

    /**
     * Returns a configured Guzzle client.
     *
     * @return Client
     * @throws \Exception if our API key is missing.
     */
    public function getClient(): Client
    {
        // See if we’ve got the stuff to do the things
        $this->isConfigured = ! empty($this->settings->apiKey) &&
            ! empty($this->settings->site);

        if ( ! $this->isConfigured) {
            throw new Exception('Lever plugin not configured.');
        }

        if ($this->_client === null) {
            $this->_client = new Client([
                'base_uri' => self::$apiBaseUrl,
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept' => 'application/json'
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
     * @throws \Exception|GuzzleException if our API key is missing.
     */
    public function getJobs(array $params = []): array
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

        if ( ! empty($params)) {
            $includedParams = [];

            foreach ($params as $key => $value) {
                if (in_array($key, $supportedParams)) {
                    $includedParams[$key] = $value;
                }
            }

            if (count($includedParams)) {
                $queryString = http_build_query($includedParams);
                $requestUrl .= '?' . $queryString;
            }
        }

        $response = $this->getClient()->get($requestUrl);

        $responseData = Json::decode($response->getBody());

        $jobs = [];

        foreach ($responseData as $jobData) {
            $jobs[] = new LeverJob($jobData);
        }

        return $jobs;
    }

    /**
     * Gets a specific job posting.
     * https://github.com/lever/postings-api/blob/master/README.md#get-a-specific-job-posting
     *
     * @param string $jobId  Lever job identifier
     *
     * @return false|LeverJob
     * @throws \Exception|GuzzleException if our API key is missing.
     */
    public function getJobById(string $jobId): bool|LeverJob
    {
        try {
            $response = $this->getClient()->get(sprintf(
                'postings/%s/%s',
                $this->settings->site,
                $jobId
            ));

            $responseData = Json::decode($response->getBody());

            return new LeverJob($responseData);
        } catch(\GuzzleHttp\Exception\RequestException $e) {
            if ($e->getCode() === 404) {
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
     * @param string $jobPostId Lever job identifier
     * @param LeverJobApplication $jobApplication Lever job application
     * @param bool $test Whether we want to post to our own controller here for testing
     *
     * @return boolean
     * @throws GuzzleException
     * @throws SiteNotFoundException
     * @throws \Exception
     */
    public function applyForJob(string $jobPostId, LeverJobApplication $jobApplication, bool $test = false): bool
    {
        $postUrl = sprintf('postings/%s/%s?key=%s',
            $this->settings->site,
            $jobPostId,
            App::parseEnv($this->settings->apiKey)
        );

        if ($test) {
            // reconfigure client for testing
            $this->_client = new Client([
                'base_uri' => UrlHelper::baseUrl(),
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept' => 'application/json'
                ],
                'verify' => false,
                'debug' => false
            ]);

            // https://site.local/actions/lever/apply/test
            $postUrl = UrlHelper::actionUrl('lever/apply/test');
        }

        $event = new ApplyEvent([ 'application' => $jobApplication ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_VALIDATE_APPLICATION)) {
            $this->trigger(self::EVENT_BEFORE_VALIDATE_APPLICATION, $event);
            $jobApplication = $event->application;
        }

        if ( ! $jobApplication->validate()) {
            Craft::info('Invalid job application.', 'lever');
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SEND_APPLICATION)) {
            $this->trigger(self::EVENT_BEFORE_SEND_APPLICATION, $event);
        }

        if ($event->isSpam) {
            Craft::info('Spammy job application ignored.', 'lever');

            // Pretend it’s fine so they feel good about themselves
            return true;
        }

        $response = $this->getClient()->post(
            $postUrl,
            [ 'multipart' => $jobApplication->toMultiPartPostData() ]
        );

        /**
         * Happy submission responses include an application ID.
         *
         * Here, we make sure it’s a 200 response *and* that Lever
         * was able to make a proper application out of it.
         */
        $responseData = Json::decode($response->getBody());
        $responseIsHealthy = $response->getStatusCode() === 200 &&
            isset($responseData->applicationId);

        if ($responseIsHealthy) {
            if ($this->hasEventHandlers(self::EVENT_AFTER_SEND_APPLICATION)) {
                $this->trigger(self::EVENT_AFTER_SEND_APPLICATION, $event);
            }

            return true;
        }

        Craft::info('Application could not be sent.', 'lever');
        return false;
    }
}
