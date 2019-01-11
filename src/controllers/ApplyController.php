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

use workingconcept\lever\Lever;
use workingconcept\lever\models\LeverJobApplication;
use Craft;
use craft\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

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

        $request = Craft::$app->getRequest();

        if ($jobId = $request->getParam('jobId'))
        {
            $resumeIncluded = ! empty($_FILES['resume']['tmp_name'])
                && ! empty($_FILES['resume']['name']);

            $application = new LeverJobApplication([
                'name'     => $request->getParam('name'),
                'email'    => $request->getParam('email'),
                'phone'    => $request->getParam('phone'),
                'org'      => $request->getParam('org'),
                'urls'     => $request->getParam('urls'),
                'comments' => $request->getParam('comments'),
                'ip'       => $request->getUserIP(),
                'silent'   => Lever::$plugin->getSettings()->applySilently,
                'source'   => Lever::$plugin->getSettings()->applicationSource,
            ]);

            if ($resumeIncluded)
            {
                $application->resume = UploadedFile::getInstanceByName('resume');
            }

            if (Lever::$plugin->api->applyForJob($jobId, $application))
            {
                if ($request->getAcceptsJson())
                {
                    return $this->asJson(['success' => true]);
                }

                Craft::$app->getSession()->setNotice('Application submitted.');

                return $this->redirectToPostedUrl();
            }

            if ($request->getAcceptsJson())
            {
                return $this->asJson(['errors' => $application->getErrors()]);
            }

            Craft::$app->getSession()->setError(Craft::t(
                'lever',
                'There was a problem with the application. Please check the form and try again!'
            ));

            Craft::$app->getUrlManager()->setRouteParams([
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
