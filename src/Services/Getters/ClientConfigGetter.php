<?php

namespace Wizz\ApiClientHelpers\Services\Getters;

use Wizz\ApiClientHelpers\Helpers\CacheHelper;

use Exception;
use Cache;

class ClientConfigGetter 
{
  public function __construct(int $app_id = null) {
    if (!$app_id) {
      throw new \Exception("Missing Argument 'app_id'");
    }
    $this->app_id = $app_id;
  }

  public function getConfigs()
  {
    return $this->getFromCacheOrRequest();
  }

  public function requestConfigs()
  {
    $url = CacheHelper::conf('secret_url') . "/client/configs?site_id=" . $this->app_id;

    $json_response = file_get_contents($url, false, stream_context_create([
      'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
      ]
    ]));

    $configs = json_decode($json_response, true);

    return $configs['data'];
  }

  public function getFromCacheOrRequest()
  {
    $key = "config_data_for_application_id_{$this->app_id}";

    $cached_data = Cache::get($key);

    if (!is_null($cached_data)) {
      return $cached_data;
    }

    $new_data = $this->requestConfigs();

    Cache::put($key, $new_data, 60 * 24 * 30);

    return $new_data;
  }

  public function getExperimentsInfo()
  {
    $application_configs = $this->getConfigs();
    if ( !array_key_exists('experiments', $application_configs) ) {
      return [];
    }
    return $application_configs['experiments'];
  }

  public function getExperimentConfig(string $experiment_name = '')
  {
    if (!$experiment_name) {
      throw new \Exception("Missing argument 'experiment_name'");
    }
    $experiments_info = $this->getExperimentsInfo();

    $experiment_config = array_get($experiments_info, $experiment_name, [ 'enabled' => false ]);
    return $experiment_config;
  }
}
