<?php
/**
 * Lever plugin for Craft CMS 3.x
 *
 * Craft + Lever.
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\lever\controllers;

use Craft;
use workingconcept\lever\Lever;
use craft\web\Controller;
use yii\web\Response;

class ApplyController extends Controller
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    /**
     * Posts a job application.
     *
     * @return Response|null
     * @throws
     */
    public function actionIndex()
    {
        $this->requirePostRequest();

        if ($jobId = Craft::$app->getRequest()->getParam('jobId'))
        {
            if (Lever::$plugin->api->applyForJob($jobId))
            {
                return $this->redirectToPostedUrl();
            }
            else
            {
                Craft::$app->getSession()->setError(Craft::t('lever', 'Failed to submit job application. Please try again!'));
            }
        }
        else
        {
            Craft::$app->getSession()->setError(Craft::t('lever', 'Job ID missing.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Capture a test post and save its data as a JSON file for troubleshooting.
     *
     * @return Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionTest()
    {
        $this->requirePostRequest();

        if (Lever::$plugin->api->applyForJob(Craft::$app->getRequest()->getParam('jobId')))
        {
            file_put_contents('lever-application-test-' . time() . '.json', json_encode(Craft::$app->getRequest()->post()));

            return $this->asJson(Craft::$app->getRequest()->post());
        }

        return $this->asJson(Lever::$plugin->api->errors);
    }
}
