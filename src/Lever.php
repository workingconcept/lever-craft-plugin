<?php
/**
 * Lever plugin for Craft CMS 4.x
 *
 * Craft + Lever.
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\lever;

use workingconcept\lever\services\LeverService;
use workingconcept\lever\variables\LeverVariable;
use workingconcept\lever\models\Settings;
use Craft;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

/**
 * Class Lever
 *
 * @author    Working Concept
 * @package   Lever
 * @since     1.0.0
 *
 * @property  LeverService $api
 */
class Lever extends \craft\base\Plugin
{
    /**
     * @var Lever
     */
    public static Lever $plugin;

    /**
	 * @inheritdoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'api' => LeverService::class,
        ]);

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function (Event $event) {
                $variable = $event->sender;
                $variable->set('lever', LeverVariable::class);
            }
        );

        Craft::info(
            Craft::t(
                'lever',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function defineTemplateComponent(): string
    {
        return LeverVariable::class;
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Settings
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'lever/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

}
