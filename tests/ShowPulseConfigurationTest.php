<?php

namespace App\Test;

use App\Commands\ShowPulseConfigurationResponse;
use PHPUnit\Framework\TestCase;

/**
 * Summary of ShowPulseConfigurationTest
 * 
 * @covers ShowPulseConfiguration
 */
final class ShowPulseConfigurationTest extends TestCase
{
    private $rawJson;

    public function setUp(): void
    {
        $this->rawJson = "{\"show_id\":\"testingTheShowId\", \"host\":\"https://rhtservices.net\",\"token\":\"thisIsATestTokenWithSomeExtraCharacters\"}";
    }

    public function testGetShowId(): void
    {
        $configuration = new ShowPulseConfigurationResponse($this->rawJson);

        $this->assertEquals("testingTheShowId", $configuration->getShowId());
    }

    public function testGetWebsiteUrl(): void
    {
        $configuration = new ShowPulseConfigurationResponse($this->rawJson);

        $this->assertEquals("https://rhtservices.net", $configuration->getWebsiteUrl());
    }

    public function testTokenAsHeader(): void
    {
        $configuration = new ShowPulseConfigurationResponse($this->rawJson);

        $this->assertIsArray($configuration->getTokenAsHeader());
        $this->assertContains("Authorization: Bearer thisIsATestTokenWithSomeExtraCharacters",$configuration->getTokenAsHeader());
    }
}