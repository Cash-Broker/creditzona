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
}
