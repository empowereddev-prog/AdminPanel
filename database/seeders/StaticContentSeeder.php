<?php

namespace Database\Seeders;

use App\Models\StaticContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaticContentSeeder extends Seeder
{
    protected function getStaticContentData()
    {
        $images = [
            'sticky-notes' => asset('assets/images/sticky-notes.png'),
            'layers' => asset('assets/images/layers.png'),
        ];

        return [
            'english' => [
                'about-us' => [
                    'title' => 'About us',
                    'content' => '<div class="panel right-panel">
                        <h2 data-aos="zoom-out-down" data-aos-duration="1000">
                            AE Note is a beautiful, and simple note-taking app to capture,
                            write, and organize your life.
                        </h2>
                        <ul>
                            <li data-aos="fade-up" data-aos-duration="1000">
                                <h4>
                                    <img src="' . $images['sticky-notes'] . '" alt="Docs" />Work
                                    Efficiently
                                </h4>
                                <p>
                                    Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                                    Nunc dignissim, diam ac porta lacinia, nunc justo vulputate.
                                </p>
                            </li>
                            <li data-aos="fade-up" data-aos-duration="2000">
                                <h4>
                                    <img src="' . $images['layers'] . '" alt="Docs" />Stay
                                    Organised
                                </h4>
                                <p>
                                    Enjoy a user-friendly interface designed to help you find
                                    what you need quickly and efficiently.
                                </p>
                            </li>
                        </ul>
                    </div>'
                ],
                'terms-condition' => [
                    'title' => 'Terms and Condition',
                    'content' => 'Please review our terms and conditions to understand the rules and guidelines for using our service.'
                ],
                'privacy-policy' => [
                    'title' => 'Privacy Policy',
                    'content' => 'We value your privacy and are committed to protecting your personal information.'
                ],
            ],
            'simplified_chinese' => [
                'about-us' => [
                    'title' => 'About us',
                    'content' => '<div class="panel right-panel">
                        <h2 data-aos="zoom-out-down" data-aos-duration="1000">
                            AE Note is a beautiful, and simple note-taking app to capture,
                            write, and organize your life.
                        </h2>
                        <ul>
                            <li data-aos="fade-up" data-aos-duration="1000">
                                <h4>
                                    <img src="' . $images['sticky-notes'] . '" alt="Docs" />Work
                                    Efficiently
                                </h4>
                                <p>
                                    Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                                    Nunc dignissim, diam ac porta lacinia, nunc justo vulputate.
                                </p>
                            </li>
                            <li data-aos="fade-up" data-aos-duration="2000">
                                <h4>
                                    <img src="' . $images['layers'] . '" alt="Docs" />Stay
                                    Organised
                                </h4>
                                <p>
                                    Enjoy a user-friendly interface designed to help you find
                                    what you need quickly and efficiently.
                                </p>
                            </li>
                        </ul>
                    </div>'
                ],
                'terms-condition' => [
                    'title' => 'Terms and Condition',
                    'content' => 'Please review our terms and conditions to understand the rules and guidelines for using our service.'
                ],
                'privacy-policy' => [
                    'title' => 'Privacy Policy',
                    'content' => 'We value your privacy and are committed to protecting your personal information.'
                ],
            ],
            'traditional_chinese' => [
                'about-us' => [
                    'title' => 'About us',
                    'content' => '<div class="panel right-panel">
                        <h2 data-aos="zoom-out-down" data-aos-duration="1000">
                            AE Note is a beautiful, and simple note-taking app to capture,
                            write, and organize your life.
                        </h2>
                        <ul>
                            <li data-aos="fade-up" data-aos-duration="1000">
                                <h4>
                                    <img src="' . $images['sticky-notes'] . '" alt="Docs" />Work
                                    Efficiently
                                </h4>
                                <p>
                                    Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                                    Nunc dignissim, diam ac porta lacinia, nunc justo vulputate.
                                </p>
                            </li>
                            <li data-aos="fade-up" data-aos-duration="2000">
                                <h4>
                                    <img src="' . $images['layers'] . '" alt="Docs" />Stay
                                    Organised
                                </h4>
                                <p>
                                    Enjoy a user-friendly interface designed to help you find
                                    what you need quickly and efficiently.
                                </p>
                            </li>
                        </ul>
                    </div>'
                ],
                'terms-condition' => [
                    'title' => 'Terms and Condition',
                    'content' => 'Please review our terms and conditions to understand the rules and guidelines for using our service.'
                ],
                'privacy-policy' => [
                    'title' => 'Privacy Policy',
                    'content' => 'We value your privacy and are committed to protecting your personal information.'
                ],
            ],
        ];
    }


    public function run(): void
    {
        $data = $this->getStaticContentData();

        DB::transaction(function () use ($data) {

            $langs = ['english', 'simplified_chinese', 'traditional_chinese'];
            $slugs = ['about-us', 'terms-condition', 'privacy-policy'];


            // Create new entries
            foreach ($slugs as $slug) {
                foreach ($langs as $lang) {
                    if (!StaticContent::where('slug', $slug)->where('language', $lang)->exists()) {
                        StaticContent::create([
                            'slug' => $slug,
                            'language' => $lang,
                            'title' => $data[$lang][$slug]['title'] ?? 'Default Title',
                            'content' => $data[$lang][$slug]['content'] ?? 'Default Content',
                        ]);
                    }
                }
            }
        });
    }
}
