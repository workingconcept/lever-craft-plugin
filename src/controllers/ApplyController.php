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

use craft\web\UrlManager;
use workingconcept\lever\Lever;
use workingconcept\lever\models\LeverJobApplication;
use Craft;
use craft\web\Controller;
use craft\helpers\Json;
use workingconcept\lever\models\Settings;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class ApplyController extends Controller
{
    /**
     * @inheritdoc
     */
    public $allowAnonymous = true;

    /**
     * Posts a job application.
     *
     * @return Response|null
     * @throws BadRequestHttpException|\craft\errors\MissingComponentException
     */
    public function actionIndex(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        if ($jobId = $request->getParam('jobId')) {
            $resumeIncluded = ! empty($_FILES['resume']['tmp_name'])
                && ! empty($_FILES['resume']['name']);

            /** @var Settings $settings */
            $settings = Lever::$plugin->getSettings();

            $application = new LeverJobApplication([
                'name'     => $request->getParam('name'),
                'email'    => $request->getParam('email'),
                'phone'    => $request->getParam('phone'),
                'org'      => $request->getParam('org'),
                'urls'     => $request->getParam('urls'),
                'comments' => $request->getParam('comments'),
                'ip'       => $request->getUserIP(),
                'silent'   => $settings->applySilently,
                'source'   => $settings->applicationSource,
            ]);

            if ($resumeIncluded) {
                $application->resume = UploadedFile::getInstanceByName('resume');
            }

            if (Lever::$plugin->api->applyForJob($jobId, $application)) {
                if ($request->getAcceptsJson()) {
                    return $this->asJson(['success' => true]);
                }

                Craft::$app->getSession()->setNotice('Application submitted.');

                return $this->redirectToPostedUrl();
            }

            if ($request->getAcceptsJson()) {
                return $this->asJson(['errors' => $application->getErrors()]);
            }

            Craft::$app->getSession()->setError(Craft::t(
                'lever',
                'There was a problem with the application. Please check the form and try again!'
            ));

            /** @var UrlManager $urlManager */
            $urlManager = Craft::$app->getUrlManager();

            $urlManager->setRouteParams([
                'variables' => ['application' => $application]
            ]);

            return null;
        }

        Craft::$app->getSession()->setError(Craft::t(
            'lever',
            'Job ID missing.'
        ));

        return null;
    }

    /**
     * Capture a test post and save its data as a JSON file for troubleshooting.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionTest(): Response
    {
        $this->requirePostRequest();

        $jobId = Craft::$app->getRequest()->getParam('jobId');
        $application = new LeverJobApplication();

        if (Lever::$plugin->api->applyForJob($jobId, $application)) {
            $filename = 'lever-application-test-' . time() . '.json';
            $data = Json::encode(Craft::$app->getRequest()->post());

            file_put_contents($filename, $data);

            return $this->asJson(Craft::$app->getRequest()->post());
        }

        return $this->asJson(Lever::$plugin->api->errors);
    }
}
