<?php

namespace Wizz\ApiClientHelpers\Services\Experiments;

use \Illuminate\Http\Request;

interface DefaultExperimentManagerInterface
{
    public function run(Request $request, array $experimentInfo, string $type);
}
