<?php

declare(strict_types=1);

namespace xenialdan\RLSync;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Internet;

class Loader extends PluginBase
{
    /** @var self */
    private static $instance;
    /**
     * @var mixed|null
     */
    public static $lon;
    public static $lat;
    /**
     * @var string
     */
    private $timeZone;
    private const APIKEY_WEATHER = 'e3d0f8f1197c589d2efc74ee43f6a023';
    public const
        NO_DOWNFALL = 0,
        LIGHT_DOWNFALL = 5000,
        MODERATE_DOWNFALL = 30000,
        HEAVY_DOWNFALL = 100000;

    /**
     * Returns an instance of the plugin
     * @return self
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function onLoad()
    {
        self::$instance = $this;
        //todo config stuff
        //TODO time stuff
        #$this->timeZone = Timezone::get();
    }

    public function onEnable(): void
    {
        //get location
        if ($response = Internet::getURL("http://ip-api.com/json") //If system timezone detection fails or timezone is an invalid value.
            and $ip_geolocation_data = json_decode($response, true)
            and $ip_geolocation_data['status'] !== 'fail'
            and ($lat = $ip_geolocation_data['lat'] ?? null) !== null
            and ($lon = $ip_geolocation_data['lon'] ?? null) !== null
        ) {
            self::$lon = $lon;
            self::$lat = $lat;
            $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function (): void {
                //get weather
                $response = Internet::getURL("api.openweathermap.org/data/2.5/weather?lat=" . Loader::$lat . "&lon=" . Loader::$lon . "&appid=" . self::APIKEY_WEATHER . "&units=metric");
                $weather_data = json_decode($response, true);
                var_dump($weather_data);
                $group = $weather_data["weather"][0]["main"] ?? "Clear";
                $id = (int)($weather_data["weather"][0]["id"] ?? 0);
                Loader::setWeather($group, $id);
            }), 20, 20 * 60 * 30);
        }
    }

    public function onDisable(): void
    {
    }

    public static function setWeather(string $weatherGroup, int $id): void
    {
        //https://openweathermap.org/weather-conditions
        $thunder = false;
        $moisture = self::NO_DOWNFALL;
        //Group 2xx: Thunderstorm
        if ($id >= 200 && $id < 300) {
            $thunder = true;
            switch ($id) {
                case 200:
                case 210:
                case 230://TODO validate
                    $moisture = self::LIGHT_DOWNFALL;
                    break;
                case 201:
                case 211:
                case 231://TODO validate
                    $moisture = self::MODERATE_DOWNFALL;
                    break;
                case 202:
                case 212:
                case 221:
                case 232://TODO validate
                    $moisture = self::HEAVY_DOWNFALL;
                    break;
            }
        } //Group 3xx: Drizzle
        else if ($id >= 300 && $id < 400) {
            //i am not sure if i would really count this to rain.
            $moisture = self::LIGHT_DOWNFALL;
        } //Group 5xx: Rain
        else if ($id >= 500 && $id < 600) {
            switch ($id) {
                case 500:
                case 520:
                case 511://freezing, unsure yet of category
                    $moisture = self::LIGHT_DOWNFALL;
                    break;
                case 501:
                case 521:
                    $moisture = self::MODERATE_DOWNFALL;
                    break;
                case 502:
                case 503://TODO check if moisture can be raised for more extreme weather effects
                case 504:// ^
                case 522:
                case 531:
                    $moisture = self::HEAVY_DOWNFALL;
                    break;
            }
        } //Group 6xx: Snow
        else if ($id >= 600 && $id < 700) {
            //very difficult - it would require changing the biome. For now, just simulate rain and hope you are in the correct biome
            switch ($id) {
                case 600:
                case 611://sleet, maybe hurt player? Or different particles?
                case 615://mixed with rain TODO check if this can be simulated in a simple way
                case 620://mostly rain TODO check if this can be simulated in a simple way
                    $moisture = self::LIGHT_DOWNFALL;
                    break;
                case 601:
                case 612:
                case 616://mixed with rain TODO check if this can be simulated in a simple way
                case 621://mostly rain TODO check if this can be simulated in a simple way
                    $moisture = self::MODERATE_DOWNFALL;
                    break;
                case 602:
                case 613:
                case 622://mostly rain TODO check if this can be simulated in a simple way
                    $moisture = self::HEAVY_DOWNFALL;
                    break;
            }
        } //Group 7xx: Atmosphere
        else if ($id >= 700 && $id < 800) {
            //some special and supernatural conditions that would require extra code to simulate
            //TODO decide based on Twitter poll if i should add these
            //Mist
            //Smoke
            //Haze
            //Dust (sand / dust whirls)
            //Fog
            //Sand
            //Dust (dust)
            //Ash
            //Squall
            //Tornado
        } //Group 800: Clear
        else if ($id === 800) {
            //already default values
            //we can't remove clouds, sadly
        } //Group 80x: Clouds
        else if ($id >= 801 && $id < 900) {
            //can't do anything to change clouds
        }
        var_dump($weatherGroup,$moisture,$thunder?"true":"false");
        foreach (Loader::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {

        }
    }
}