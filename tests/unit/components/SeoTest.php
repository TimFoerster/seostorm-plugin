<?php

namespace Initbiz\SeoStorm\Tests\Unit\Components;

use Cms\Classes\Page;
use Cms\Classes\Theme;
use Cms\Classes\Controller;
use Cms\Classes\ComponentManager;
use Initbiz\SeoStorm\Components\Seo;
use Initbiz\SeoStorm\Models\Settings;
use Initbiz\SeoStorm\Tests\Classes\StormedTestCase;
use Initbiz\SeoStorm\Tests\Classes\FakeStormedModel;
use Initbiz\SeoStorm\Tests\Classes\FakeModelDetailsComponent;

class SeoTest extends StormedTestCase
{

    public function setUp():void
    {
        parent::setUp();
        $componentManager = ComponentManager::instance();
        $componentManager->registerComponent(Seo::class, 'seo');
        $componentManager->registerComponent(FakeModelDetailsComponent::class, 'fakeModelDetails');
    }

    public function testGetTitle()
    {
        $theme = Theme::load('test');
        $page = Page::load($theme, 'empty.htm');
        $controller = new Controller($theme);
        $controller->runPage($page);
        $component = $controller->findComponentByName('seo');

        $this->assertEquals('Test page title', $component->getTitle());

        // Assert that meta_title has higher priority
        $page->settings['meta_title'] = 'Meta title';
        $controller->runPage($page);
        $component = $controller->findComponentByName('seo');

        $this->assertEquals('Meta title', $component->getTitle());

        // Check if title can get parsed from Twig variable

        $model = new FakeStormedModel();
        $model->name = 'test';
        $model->save();

        $model->seo_options = [
            'meta_title' => 'Test title',
        ];
        $model->save();

        // Assert that seo_options has even higher priority
        $page = Page::load($theme, 'with-fake-model.htm');
        $page->settings['meta_title'] = '{{ model.name }}';
        $result = $controller->runPage($page);
        $component = $controller->findComponentByName('seo');

        $settings = Settings::instance();
        $settings->enable_site_meta = true;

        $component->setSettings($settings);
        $result = $controller->runPage($page);

        $this->assertStringContainsString('<title>test</title>', $result);
    }
}
