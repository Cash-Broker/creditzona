<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAnalyticsConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_config_exposes_google_measurement_id_when_set(): void
    {
        config()->set('services.google_analytics.measurement_id', 'G-1MYXGJYWZ9');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('googleMeasurementId', false);
        $response->assertSee('G-1MYXGJYWZ9', false);
    }

    public function test_app_config_omits_analytics_block_when_measurement_id_is_empty(): void
    {
        config()->set('services.google_analytics.measurement_id', null);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('googleMeasurementId', false);
    }

    public function test_app_config_omits_analytics_block_when_measurement_id_is_whitespace_only(): void
    {
        config()->set('services.google_analytics.measurement_id', '   ');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('googleMeasurementId', false);
    }

    public function test_app_config_exposes_google_ads_id_when_set(): void
    {
        config()->set('services.google_analytics.measurement_id', null);
        config()->set('services.google_analytics.ads_id', 'AW-17854641886');
        config()->set('services.google_analytics.ads_conversion_label', '2TJhCJiz--cbEN7t4MFC');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('googleAdsId', false);
        $response->assertSee('AW-17854641886', false);
        $response->assertSee('googleAdsConversionLabel', false);
        $response->assertSee('2TJhCJiz--cbEN7t4MFC', false);
    }

    public function test_app_config_omits_ads_keys_when_blank(): void
    {
        config()->set('services.google_analytics.measurement_id', 'G-1MYXGJYWZ9');
        config()->set('services.google_analytics.ads_id', null);
        config()->set('services.google_analytics.ads_conversion_label', '');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('googleMeasurementId', false);
        $response->assertDontSee('googleAdsId', false);
        $response->assertDontSee('googleAdsConversionLabel', false);
    }

    public function test_app_config_omits_full_analytics_block_when_everything_is_blank(): void
    {
        config()->set('services.google_analytics.google_tag_id', null);
        config()->set('services.google_analytics.measurement_id', null);
        config()->set('services.google_analytics.ads_id', null);
        config()->set('services.google_analytics.ads_conversion_label', null);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('googleTagId', false);
        $response->assertDontSee('googleMeasurementId', false);
        $response->assertDontSee('googleAdsId', false);
        $response->assertDontSee('"analytics":', false);
    }

    public function test_app_config_exposes_both_measurement_and_ads_ids_when_both_set(): void
    {
        config()->set('services.google_analytics.measurement_id', 'G-1MYXGJYWZ9');
        config()->set('services.google_analytics.ads_id', 'AW-17854641886');
        config()->set('services.google_analytics.ads_conversion_label', '2TJhCJiz--cbEN7t4MFC');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('G-1MYXGJYWZ9', false);
        $response->assertSee('AW-17854641886', false);
        $response->assertSee('2TJhCJiz--cbEN7t4MFC', false);
    }

    public function test_app_config_exposes_google_tag_id_when_set(): void
    {
        config()->set('services.google_analytics.google_tag_id', 'GT-5TPPGVW4');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('googleTagId', false);
        $response->assertSee('GT-5TPPGVW4', false);
    }

    public function test_app_config_omits_google_tag_id_when_whitespace_only(): void
    {
        config()->set('services.google_analytics.google_tag_id', '   ');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('googleTagId', false);
    }

    public function test_app_config_exposes_google_tag_id_alongside_destinations(): void
    {
        config()->set('services.google_analytics.google_tag_id', 'GT-5TPPGVW4');
        config()->set('services.google_analytics.measurement_id', 'G-1MYXGJYWZ9');
        config()->set('services.google_analytics.ads_id', 'AW-17854641886');
        config()->set('services.google_analytics.ads_conversion_label', '2TJhCJiz--cbEN7t4MFC');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('GT-5TPPGVW4', false);
        $response->assertSee('G-1MYXGJYWZ9', false);
        $response->assertSee('AW-17854641886', false);
        $response->assertSee('2TJhCJiz--cbEN7t4MFC', false);
    }
}
