<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Tests\integration\Blade\traits\AssertBladeView;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;

    class AppAliasTest extends BladeTestCase
    {



        /** @test */
        public function nested_views_can_be_rendered_relative_to_the_views_directory()
        {

            $view = TestApp::view('nested.view');

            $this->assertViewContent('FOO', $view);

        }

        /** @test */
        public function the_first_available_view_can_be_created()
        {

            $first = TestApp::view(['bogus1', 'bogus2', 'foo']);

            $this->assertViewContent('FOO', $first);


        }




    }