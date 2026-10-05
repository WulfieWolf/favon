<?php

namespace Tests\Unit;

use App\Services\Imports\BayernAtkisMapper;
use PHPUnit\Framework\TestCase;

class BayernAtkisMapperTest extends TestCase
{
    public function test_it_maps_supported_atkis_functions_to_camperwolf_types(): void
    {
        $mapper = new BayernAtkisMapper();

        $cases = [
            ['adv:AX_Platz', '5310', 'parking'],
            ['adv:AX_Platz', '5320', 'rest-area'],
            ['adv:AX_Platz', '5330', 'rest-area'],
            ['adv:AX_Platz', '5370', 'motorhome-pitch'],
            ['adv:AX_SportFreizeitUndErholungsflaeche', '4330', 'campground'],
        ];

        foreach ($cases as [$featureType, $function, $expected]) {
            self::assertTrue($mapper->supports($featureType, $function));

            $mapped = $mapper->map([
                'external_id' => $featureType.'-'.$function,
                'feature_type' => $featureType,
                'function' => $function,
                'name' => 'Testplatz',
                'latitude' => 48.1,
                'longitude' => 11.5,
            ]);

            self::assertSame($expected, $mapped['place']['suggested_place_type']);
        }
    }

    public function test_it_uses_second_name_only_as_name_fallback(): void
    {
        $mapped = (new BayernAtkisMapper())->map([
            'external_id' => 'DEBY-test',
            'feature_type' => 'adv:AX_Platz',
            'function' => '5370',
            'name' => null,
            'second_names' => ['Wohnmobilplatz am See'],
            'latitude' => 48.1,
            'longitude' => 11.5,
        ]);

        self::assertSame('Wohnmobilplatz am See', $mapped['place']['name']);
        self::assertSame('zweitname', $mapped['source_name_origin']);
        self::assertSame('motorhome-pitch', $mapped['place']['suggested_place_type']);
        self::assertSame('cc-by-4.0', $mapped['license']['code']);
    }

    public function test_it_maps_active_records_as_operating(): void
    {
        $mapped = (new BayernAtkisMapper())->map([
            'external_id' => 'DEBY-active',
            'feature_type' => 'adv:AX_Platz',
            'function' => '5370',
            'name' => 'Aktiver Stellplatz',
            'condition' => null,
            'latitude' => 48.1,
            'longitude' => 11.5,
        ]);

        self::assertSame('open', $mapped['place']['opening_status']);
    }

    public function test_it_maps_atkis_condition_2100_as_permanently_closed(): void
    {
        $mapped = (new BayernAtkisMapper())->map([
            'external_id' => 'DEBY-closed',
            'feature_type' => 'adv:AX_Platz',
            'function' => '5370',
            'name' => 'Stillgelegter Stellplatz',
            'condition' => '2100',
            'latitude' => 48.1,
            'longitude' => 11.5,
        ]);

        self::assertSame('permanently_closed', $mapped['place']['opening_status']);
        self::assertSame('2100', $mapped['source_properties']['condition']);
    }

    public function test_it_does_not_support_irrelevant_atkis_functions(): void
    {
        $mapper = new BayernAtkisMapper();

        self::assertFalse($mapper->supports('adv:AX_Platz', '5350'));
        self::assertFalse($mapper->supports('adv:AX_Platz', '5360'));
        self::assertFalse($mapper->supports('adv:AX_SportFreizeitUndErholungsflaeche', '4470'));
    }
}
